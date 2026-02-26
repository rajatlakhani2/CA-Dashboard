<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientContact extends Model
{
    protected $fillable = ['client_id', 'name', 'phone', 'email', 'designation'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
