<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentIngestion extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    public const STATUS_PENDING = 'pending_review';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_FIRM = 'firm';

    public const SOURCE_PORTAL = 'portal';

    protected $fillable = [
        'client_id',
        'uploaded_by',
        'source',
        'original_filename',
        'stored_path',
        'mime_type',
        'status',
        'document_type',
        'extracted_fields',
        'confirmed_fields',
        'reviewed_by',
        'reviewed_at',
        'created_task_id',
        'review_notes',
    ];

    protected $casts = [
        'extracted_fields' => 'array',
        'confirmed_fields' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'created_task_id');
    }
}
