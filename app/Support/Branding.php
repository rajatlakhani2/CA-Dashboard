<?php

namespace App\Support;

use App\Models\Setting;

class Branding
{
    public const DEFAULT_NAME = 'CA Dashboard';

    public static function dashboardName(): string
    {
        $name = trim((string) Setting::get('dashboard_name', ''));

        return $name !== '' ? $name : self::DEFAULT_NAME;
    }

    public static function dashboardTagline(): string
    {
        $tagline = trim((string) Setting::get('dashboard_tagline', ''));

        return $tagline !== '' ? $tagline : 'Practice management for CA firms';
    }

    public static function pageTitle(?string $suffix = null): string
    {
        $base = self::dashboardName();

        return $suffix ? "{$suffix} — {$base}" : $base;
    }

    public static function companyLogoUrl(): ?string
    {
        $path = trim((string) Setting::get('company_logo_path', ''));

        if ($path === '' || ! file_exists(public_path($path))) {
            return null;
        }

        return asset($path);
    }
}
