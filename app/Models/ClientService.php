<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientService extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToOrganization;

    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_CLOSED = 'Closed';

    protected $fillable = [
        'client_id',
        'service_id',
        'status',
        'custom_due_day',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function dues()
    {
        return $this->hasMany(ServiceDue::class);
    }

    public function documentChecks()
    {
        return $this->hasMany(ClientServiceDocumentCheck::class);
    }
}
