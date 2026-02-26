<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

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
        'date',
        'due_date',
        'status',
        'subtotal',
        'tax',
        'total_amount',
        'notes',
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
}
