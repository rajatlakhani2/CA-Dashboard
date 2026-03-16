<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'receipt_number',
        'amount',
        'payment_date',
        'payment_mode',
        'reference_number',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Generate next receipt number
     */
    public static function nextReceiptNumber(): string
    {
        $maxId = self::max('id') ?? 0;
        return 'REC-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);
    }
}
