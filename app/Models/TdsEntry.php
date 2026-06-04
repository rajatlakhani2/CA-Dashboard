<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TdsEntry extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToOrganization;

    protected $fillable = [
        'invoice_id',
        'tds_rate',
        'tds_amount',
        'certificate_received',
        'certificate_date',
        'certificate_number',
        'notes',
    ];

    protected $casts = [
        'certificate_date' => 'date',
        'certificate_received' => 'boolean',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
