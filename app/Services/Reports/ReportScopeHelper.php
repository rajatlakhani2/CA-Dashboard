<?php

namespace App\Services\Reports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportScopeHelper
{
    public static function datesFromRequest(Request $request): array
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
    }

    public static function managerBranchId(?User $user): ?int
    {
        return $user?->isManager() && $user->branch_id ? (int) $user->branch_id : null;
    }

    public static function scopeUsers(Builder $query, ?User $actor): void
    {
        $branchId = self::managerBranchId($actor);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
    }

    public static function scopeClients(Builder $query, ?User $actor): void
    {
        $branchId = self::managerBranchId($actor);

        if (! $branchId) {
            return;
        }

        $query->where(function (Builder $q) use ($branchId) {
            $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
        });
    }

    public static function scopeTasks(Builder $query, ?User $actor): void
    {
        $branchId = self::managerBranchId($actor);

        if (! $branchId) {
            return;
        }

        $query->where(function (Builder $q) use ($branchId) {
            $q->whereNull('client_id')
                ->orWhereHas('client', fn (Builder $c) => $c->whereNull('branch_id')->orWhere('branch_id', $branchId));
        });
    }

    public static function scopeInvoices(Builder $query, ?User $actor): void
    {
        $branchId = self::managerBranchId($actor);

        if (! $branchId) {
            return;
        }

        $query->where(function (Builder $q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhere(function (Builder $q) use ($branchId) {
                    $q->whereNull('branch_id')
                        ->whereHas('client', fn (Builder $c) => $c->whereNull('branch_id')->orWhere('branch_id', $branchId));
                });
        });
    }
}
