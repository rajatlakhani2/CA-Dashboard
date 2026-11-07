<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * One of the firm's bank accounts, shown on invoice PDFs.
 * Removed accounts are soft-deleted so invoices that printed them still do.
 */
class BankAccount extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    use SoftDeletes;

    protected $fillable = [
        'label',
        'bank_name',
        'account_name',
        'account_number',
        'ifsc',
        'branch',
        'upi_id',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Accounts for the current firm, default first. Filters by firm explicitly
     * rather than relying only on the global scope, which is skipped when no
     * organization context is set.
     */
    public static function ordered()
    {
        return static::forCurrentFirm()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id');
    }

    public static function forCurrentFirm()
    {
        $orgId = static::currentFirmId();

        return $orgId
            ? static::query()->where('organization_id', $orgId)
            : static::query()->whereNull('organization_id');
    }

    public static function currentFirmId(): ?int
    {
        return \App\Support\OrganizationContext::id() ?? auth()->user()?->organization_id;
    }

    public static function defaultAccount(): ?self
    {
        return static::ordered()->first();
    }

    /** Short name for pickers, e.g. "Primary — HDFC Bank ••4321". */
    public function displayName(): string
    {
        $tail = $this->account_number ? '••'.Str::substr(preg_replace('/\s+/', '', $this->account_number), -4) : null;
        $main = trim(implode(' ', array_filter([$this->bank_name, $tail])));
        $label = $this->label ?: null;

        if ($label && $main) {
            return $label.' — '.$main;
        }

        return $label ?: ($main ?: 'Bank account #'.$this->id);
    }

    /** Keys the invoice PDF template already uses. */
    public function toPdfFields(): array
    {
        return [
            'bank_name' => (string) $this->bank_name,
            'bank_account_name' => (string) $this->account_name,
            'bank_account_number' => (string) $this->account_number,
            'bank_ifsc' => (string) $this->ifsc,
            'bank_branch' => (string) $this->branch,
            'bank_upi' => (string) $this->upi_id,
        ];
    }
}
