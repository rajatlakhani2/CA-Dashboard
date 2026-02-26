<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'frequency',
        'due_day',
        'due_month',
        'is_statutory',
    ];

    public function clientServices()
    {
        return $this->hasMany(ClientService::class);
    }
}
