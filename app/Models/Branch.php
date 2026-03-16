<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'gstin',
        'state_code',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
