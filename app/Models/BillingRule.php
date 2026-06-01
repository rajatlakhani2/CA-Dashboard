<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRule extends Model
{
    public const TYPE_FIXED_FEE = 'fixed_fee';
    public const TYPE_USE_DUE_AMOUNT = 'use_due_amount';

    protected $fillable = [
        'name',
        'service_id',
        'client_id',
        'rule_type',
        'fixed_amount',
        'use_due_amount',
        'auto_draft_invoice',
        'is_active',
    ];

    protected $casts = [
        'fixed_amount' => 'decimal:2',
        'use_due_amount' => 'boolean',
        'auto_draft_invoice' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
