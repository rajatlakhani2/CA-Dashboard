<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    protected $fillable = [
        'user_id',
        'leave_date',
        'reason',
        'informed_at',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'leave_date' => 'date',
        'informed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
