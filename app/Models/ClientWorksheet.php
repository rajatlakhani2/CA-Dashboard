<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientWorksheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'description',
        'amount',
        'date',
        'is_billed',
        'invoice_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_billed' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
