<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientContact extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    protected $fillable = ['client_id', 'name', 'phone', 'email', 'designation'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
