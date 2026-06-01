<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceDue extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_OVERDUE = 'Overdue';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_EXTENDED = 'Extended';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_OVERDUE,
        self::STATUS_COMPLETED,
        self::STATUS_EXTENDED,
    ];

    public const BILLING_STATUS_PENDING = 'Pending';
    public const BILLING_STATUS_UNBILLED = 'Unbilled';
    public const BILLING_STATUS_BILLED = 'Billed';
    public const BILLING_STATUS_NON_BILLABLE = 'Non-Billable';

    protected $fillable = [
        'client_service_id',
        'due_date',
        'status',
        'completed_at',
        'completed_by',
        'remarks',
        'billing_status',
        'billing_amount',
        'invoice_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    public function clientService()
    {
        return $this->belongsTo(ClientService::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
