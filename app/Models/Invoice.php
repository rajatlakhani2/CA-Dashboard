<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_PAID = 'Paid';
    public const STATUS_OVERDUE = 'Overdue';
    public const STATUS_PARTIALLY_PAID = 'Partially Paid';
    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_PARTIALLY_PAID,
        self::STATUS_CANCELLED,
    ];

    public const OPEN_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OVERDUE,
        self::STATUS_PARTIALLY_PAID,
    ];

    public const PAYABLE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OVERDUE,
        self::STATUS_PARTIALLY_PAID,
    ];

    public static function selectableStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_PARTIALLY_PAID,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount', 'invoice_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'client_id',
        'invoice_number',
        'reference_number',
        'work_period',
        'project_name',
        'date',
        'due_date',
        'status',
        'subtotal',
        'tax',
        'cgst',
        'sgst',
        'igst',
        'total_amount',
        'place_of_supply',
        'reverse_charge',
        'financial_year',
        'notes',
        'payment_url',
        'branch_id',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function isInterState(): bool
    {
        $firmState = Setting::get('firm_state_code', '');
        return $firmState && $this->place_of_supply && $firmState !== $this->place_of_supply;
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return (float) $this->total_amount - $this->amountPaid();
    }
}
