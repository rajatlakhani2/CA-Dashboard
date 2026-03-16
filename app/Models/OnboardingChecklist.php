<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'item',
        'is_completed',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function completedByUser()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public static function defaultItems(): array
    {
        return [
            'PAN Verification',
            'KYC Documents Collected',
            'Engagement Letter Signed',
            'Fee Agreement Finalized',
            'Past Records Collected',
            'GST Registration Verified',
            'Bank Details Obtained',
            'Authorised Signatory Details',
            'DSC Obtained (if applicable)',
            'Login Credentials Shared',
        ];
    }
}
