<?php

namespace App\Http\Controllers;

use App\Models\FirmAlert;
use App\Services\Intelligence\AnomalyScanner;
use Illuminate\Http\Request;

class FirmAlertController extends Controller
{
    public function dismiss(FirmAlert $firmAlert)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);

        $firmAlert->dismiss();

        return back()->with('success', 'Alert dismissed.');
    }

    public function scan(AnomalyScanner $scanner)
    {
        abort_unless(auth()->user()?->isPartner(), 403);

        $result = $scanner->scan();

        return back()->with(
            'success',
            sprintf('Scan complete. %d active alerts; %d stale alerts cleared.', $result['created'], $result['resolved'])
        );
    }
}
