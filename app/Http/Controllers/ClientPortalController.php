<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPortalToken;
use App\Models\DocumentIngestion;
use App\Models\Invoice;
use App\Models\ServiceDue;
use App\Services\Intelligence\DocumentFieldGuesser;
use App\Support\InvoicePaymentLinkBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientPortalController extends Controller
{
    public function issueLink(Client $client)
    {
        $this->authorize('view', $client);
        abort_unless(auth()->user()?->managesFirmModules(), 403);

        $issued = ClientPortalToken::issueForClient($client);
        $url = route('portal.home', ['token' => $issued['plain']]);

        return back()->with([
            'success' => 'Portal link created (valid 30 days). Copy and send to the client.',
            'portal_url' => $url,
            'portal_expires_at' => $issued['model']->expires_at->format('d M Y'),
        ]);
    }

    public function home(Request $request, string $token)
    {
        $portalToken = ClientPortalToken::findValid($token);
        if (! $portalToken) {
            return response()->view('portal.invalid', [], 403);
        }

        $client = $portalToken->client;
        $paymentBuilder = app(InvoicePaymentLinkBuilder::class);

        $invoices = Invoice::query()
            ->where('client_id', $client->id)
            ->whereIn('status', Invoice::OPEN_STATUSES)
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->orderByDesc('date')
            ->get()
            ->map(function (Invoice $invoice) use ($paymentBuilder) {
                if (! $invoice->payment_url) {
                    $invoice->payment_url = $paymentBuilder->build($invoice);
                    if ($invoice->payment_url) {
                        $invoice->saveQuietly();
                    }
                }
                $invoice->setAttribute('qr_url', $paymentBuilder->qrImageUrl($invoice->payment_url));

                return $invoice;
            });

        $dues = ServiceDue::query()
            ->whereIn('service_dues.status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->with('clientService.service')
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        return view('portal.home', compact('client', 'token', 'invoices', 'dues'));
    }

    public function upload(Request $request, string $token)
    {
        $portalToken = ClientPortalToken::findValid($token);
        if (! $portalToken) {
            return response()->view('portal.invalid', [], 403);
        }

        $request->validate([
            'document' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'notes' => 'nullable|string|max:500',
        ]);

        $client = $portalToken->client;
        $file = $request->file('document');
        $path = $file->store('document-ingestions/' . $client->id, 'local');
        $guessed = app(DocumentFieldGuesser::class)->fromFilename($file->getClientOriginalName());
        $guessed['portal_notes'] = $request->input('notes');

        DocumentIngestion::create([
            'client_id' => $client->id,
            'uploaded_by' => null,
            'source' => DocumentIngestion::SOURCE_PORTAL,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'status' => DocumentIngestion::STATUS_PENDING,
            'extracted_fields' => $guessed,
        ]);

        return redirect()
            ->route('portal.home', ['token' => $token])
            ->with('success', 'Document uploaded. Our team will review it shortly.');
    }
}
