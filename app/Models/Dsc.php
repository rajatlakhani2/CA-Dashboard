<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dsc extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'holder_name',
        'class_type',
        'provider',
        'issue_date',
        'expiry_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->status === 'Active' && $this->expiry_date->diffInDays(now()) <= $days;
    }
}
