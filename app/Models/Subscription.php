<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
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
            case 'monthly':
                $nextDate->addMonth();
                break;
            case 'quarterly':
                $nextDate->addMonths(3);
                break;
            case 'semi-annually':
                $nextDate->addMonths(6);
                break;
            case 'annually':
                $nextDate->addYear();
                break;
        }

        // Set to the specific billing day
        $nextDate->day($this->billing_day);

        return $nextDate;
    }
}
