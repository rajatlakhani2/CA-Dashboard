<?php

namespace App\Http\Controllers;

use App\Models\ServiceDue;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function index(Request $request)
    {
        // Fetch all dues with relationships
        $query = ServiceDue::with(['clientService.client', 'clientService.service']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $dues = $query->get();

        // Format for FullCalendar
        $events = $dues->map(function ($due) {
            $clientName = $due->clientService->client->name ?? 'Unknown Client';
            $serviceName = $due->clientService->service->name ?? 'Service';

            $color = '#3b82f6'; // Blue (Pending)
            if ($due->status === 'Completed') $color = '#22c55e'; // Green
            if ($due->status === 'Overdue') $color = '#ef4444'; // Red

            return [
                'id' => $due->id,
                'title' => "{$clientName} - {$serviceName}",
                'start' => $due->due_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'status' => $due->status,
                    'client' => $clientName
                ]
            ];
        });

        return view('compliance.index', compact('events'));
    }
}
