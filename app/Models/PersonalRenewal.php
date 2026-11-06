<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalRenewal extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_PAID = 'Paid';

    /** Stored value => label. Existing rows use LIC, Loan, Medical, Other. */
    public const CATEGORIES = [
        'LIC' => 'LIC Premium',
        'Medical' => 'Health / Mediclaim',
        'Term' => 'Term / Life Insurance',
        'Vehicle' => 'Vehicle Insurance',
        'Home' => 'Home / Property Insurance',
        'Loan' => 'Loan EMI',
        'Other' => 'Other',
    ];

    /** Grouped under the "Insurance" tab. */
    public const INSURANCE_CATEGORIES = ['Medical', 'Term', 'Vehicle', 'Home'];

    public const FREQUENCIES = ['Monthly', 'Quarterly', 'Half-Yearly', 'Yearly'];

    protected $fillable = [
        'title',
        'category',
        'due_date',
        'amount',
        'frequency',
        'status',
        'notes',
        'user_id',
        'client_id',
        'document_path',
        'policy_number',
        'insurer',
        'insured_person',
        'sum_assured',
        'maturity_date',
        'remind_days_before',
        'whatsapp_reminder',
        'last_reminded_on',
    ];

    protected $casts = [
        'due_date' => 'date',
        'expiry_date' => 'date',
        'maturity_date' => 'date',
        'last_reminded_on' => 'date',
        'whatsapp_reminder' => 'boolean',
        'sum_assured' => 'decimal:2',
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? (string) $this->category;
    }

    public function isInsurance(): bool
    {
        return $this->category === 'LIC' || in_array($this->category, self::INSURANCE_CATEGORIES, true);
    }

    /** Days until due (negative = overdue). */
    public function daysLeft(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false);
    }

    /** One-line WhatsApp/email text for this premium. */
    public function reminderLine(): string
    {
        $days = $this->daysLeft();
        $when = match (true) {
            $days < 0 => abs($days).' day(s) overdue',
            $days === 0 => 'due TODAY',
            $days === 1 => 'due tomorrow',
            default => 'due in '.$days.' days',
        };
        $who = $this->insured_person ? ' – '.$this->insured_person : '';
        $policy = $this->policy_number ? ' (Policy '.$this->policy_number.')' : '';

        return $this->categoryLabel().': '.$this->title.$who.$policy.' ₹'.number_format((float) $this->amount)
            .' – '.$when.' ('.$this->due_date->format('d M').')';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
