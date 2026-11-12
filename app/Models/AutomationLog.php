<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationLog extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'organization_id', 'type', 'dedupe_key', 'channel', 'recipient', 'subject_label', 'status', 'detail',
    ];
}
