<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToOrganization;

    protected $fillable = [
        'category',
        'description',
        'amount',
        'expense_date',
        'payment_mode',
        'receipt_path',
        'vendor',
        'user_id',
        'is_recurring',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function categories(): array
    {
        return ['Rent', 'Salaries', 'Software', 'Travel', 'Office Supplies', 'Utilities', 'Professional Fees', 'Other'];
    }
}
