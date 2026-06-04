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

        $allDues = ServiceDue::query();
        $stats = [
            'pending' => (clone $allDues)->where('status', ServiceDue::STATUS_PENDING)->count(),
            'overdue' => (clone $allDues)->where('status', ServiceDue::STATUS_OVERDUE)->count(),
            'completed' => (clone $allDues)->where('status', ServiceDue::STATUS_COMPLETED)->count(),
        ];

        $dues = $query->get();

        // Format for FullCalendar
        $events = $dues->map(function ($due) {
            $clientName = $due->clientService->client->name ?? 'Unknown Client';
            $serviceName = $due->clientService->service->name ?? 'Service';

            $color = '#3b82f6'; // Blue (Pending)
            if ($due->status === ServiceDue::STATUS_COMPLETED) $color = '#22c55e'; // Green
            if ($due->status === ServiceDue::STATUS_OVERDUE) $color = '#ef4444'; // Red

            return [
                'id' => 'due_' . $due->id,
                'title' => "{$clientName} - {$serviceName}",
                'start' => $due->due_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'editable' => $due->status !== ServiceDue::STATUS_COMPLETED,
                'extendedProps' => [
                    'type' => 'due',
                    'db_id' => $due->id,
                    'status' => $due->status,
                    'client' => $clientName,
                    'client_name' => $clientName,
                    'details' => $serviceName,
                ]
            ];
        })->values()->all();

        return view('compliance.index', compact('events', 'stats'));
    }
}
