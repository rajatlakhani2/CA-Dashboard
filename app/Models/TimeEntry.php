<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToOrganization;

    protected $fillable = [
        'task_id',
        'user_id',
        'date',
        'hours',
        'description',
        'is_billable',
    ];

    protected $casts = [
        'date' => 'date',
        'is_billable' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
