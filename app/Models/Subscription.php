<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_CANCELLED,
    ];

    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_SEMI_ANNUALLY = 'semi-annually';
    public const FREQUENCY_ANNUALLY = 'annually';

    protected $fillable = [
        'client_id',
        'service_id',
        'name',
        'amount',
        'frequency',
        'billing_day',
        'start_date',
        'end_date',
        'last_billed_at',
        'next_billing_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'billing_day' => 'integer',
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_billed_at' => 'date',
        'next_billing_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function calculateNextBillingDate()
    {
        $lastDate = $this->last_billed_at ?: $this->start_date;
        $nextDate = clone $lastDate;

        switch ($this->frequency) {
            case self::FREQUENCY_MONTHLY:
                $nextDate->addMonth();
                break;
            case self::FREQUENCY_QUARTERLY:
                $nextDate->addMonths(3);
                break;
            case self::FREQUENCY_SEMI_ANNUALLY:
                $nextDate->addMonths(6);
                break;
            case self::FREQUENCY_ANNUALLY:
                $nextDate->addYear();
                break;
        }

        $billingDay = (int) $this->billing_day;
        if ($billingDay >= 1 && $billingDay <= 28) {
            $nextDate->day($billingDay);
        }

        return $nextDate;
    }
}
