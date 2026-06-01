<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalRenewal extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_PAID = 'Paid';

    protected $fillable = [
        'title',
        'category',
        'due_date',
        'amount',
        'frequency',
        'status',
        'notes',
        'user_id',
        'client_id',
        'document_path'
    ];

    protected $casts = [
        'due_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
