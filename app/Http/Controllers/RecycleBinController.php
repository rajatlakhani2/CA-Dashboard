<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Task;
use App\Models\Invoice;
use App\Models\ServiceDue;

class RecycleBinController extends Controller
{
    public function index()
    {
        $trashedClients = Client::onlyTrashed()->get()->map(function ($item) {
            $item->type = 'Client';
            $item->display_name = $item->name;
            return $item;
        });

        $trashedTasks = Task::onlyTrashed()->get()->map(function ($item) {
            $item->type = 'Task';
            $item->display_name = $item->title;
            return $item;
        });

        $trashedInvoices = Invoice::onlyTrashed()->get()->map(function ($item) {
            $item->type = 'Invoice';
            $item->display_name = $item->invoice_number;
            return $item;
        });

        $trashedDues = ServiceDue::onlyTrashed()->with(['clientService.client', 'clientService.service'])->get()->map(function ($item) {
            $item->type = 'Service Due';
            $item->display_name = ($item->clientService->client->name ?? 'Unknown') . ' - ' . ($item->clientService->service->name ?? 'Unknown');
            return $item;
        });

        $allItems = $trashedClients->concat($trashedTasks)->concat($trashedInvoices)->concat($trashedDues);

        return view('recycle-bin.index', compact('allItems'));
    }

    public function restore($type, $id)
    {
        $model = $this->getModel($type, $id);
        if ($model) {
            $model->restore();
            return redirect()->route('recycle-bin.index')->with('success', "$type restored successfully.");
        }
        return redirect()->route('recycle-bin.index')->with('error', 'Item not found.');
    }

    public function forceDelete($type, $id)
    {
        $model = $this->getModel($type, $id);
        if ($model) {
            $model->forceDelete();
            return redirect()->route('recycle-bin.index')->with('success', "$type permanently deleted.");
        }
        return redirect()->route('recycle-bin.index')->with('error', 'Item not found.');
    }

    private function getModel($type, $id)
    {
        switch ($type) {
            case 'Client':
                return Client::onlyTrashed()->find($id);
            case 'Task':
                return Task::onlyTrashed()->find($id);
            case 'Invoice':
                return Invoice::onlyTrashed()->find($id);
            case 'Service Due':
                return ServiceDue::onlyTrashed()->find($id);
            default:
                return null;
        }
    }
}
