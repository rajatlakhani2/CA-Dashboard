<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    protected $fillable = [
        'client_id',
        'document_type',
        'file_path',
        'expiry_date',
        'uploaded_at'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'uploaded_at' => 'date'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
