<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientServiceDocumentCheck extends Model
{
    protected $fillable = [
        'client_service_id',
        'service_document_requirement_id',
        'is_received',
        'received_at',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'is_received' => 'boolean',
        'received_at' => 'datetime',
    ];

    public function clientService(): BelongsTo
    {
        return $this->belongsTo(ClientService::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ServiceDocumentRequirement::class, 'service_document_requirement_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
