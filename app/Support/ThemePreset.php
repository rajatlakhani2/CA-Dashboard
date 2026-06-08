<?php

namespace App\Support;

use App\Models\Organization;

class ThemePreset
{
    /** CA Firm — gallery option 2: Slate Pro */
    public static function slatePro(): array
    {
        return [
            'id' => 'slate',
            'font_family' => 'Inter',
            'font_stack' => "'Inter', ui-sans-serif, system-ui, sans-serif",
            'font_url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            'sidebar' => '#1e293b',
            'sidebar_mid' => '#0f172a',
            'sidebar_deep' => '#020617',
            'accent' => '#3b82f6',
            'accent_light' => '#60a5fa',
            'accent_soft' => '#eff6ff',
            'bg' => '#f1f5f9',
            'surface' => '#ffffff',
            'text' => '#0f172a',
            'muted' => '#64748b',
            'border' => '#e2e8f0',
            'theme_color' => '#1e293b',
            'html_size' => '93.75%',
            'radius' => '12px',
            'nav_active_bg' => 'rgba(59, 130, 246, 0.22)',
            'nav_active_border' => '#60a5fa',
            'nav_active_shadow' => 'rgba(59, 130, 246, 0.2)',
            'login_brand_gradient' => 'linear-gradient(145deg, #1e293b 0%, #0f172a 50%, #334155 100%)',
            'login_brand_muted' => 'rgba(147, 197, 253, 0.85)',
            'login_btn_gradient' => 'linear-gradient(135deg, #3b82f6, #2563eb)',
            'login_btn_shadow' => 'rgba(37, 99, 235, 0.35)',
            'login_focus_ring' => 'rgba(59, 130, 246, 0.15)',
            'login_link' => '#2563eb',
        ];
    }

    /** Executive CEO/CFO — gallery option 4: Warm Executive */
    public static function warmExecutive(): array
    {
        return [
            'id' => 'warm',
            'font_family' => 'Libre Franklin',
            'font_stack' => "'Libre Franklin', ui-sans-serif, system-ui, sans-serif",
            'font_url' => 'https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;500;600;700&display=swap',
            'sidebar' => '#292524',
            'sidebar_mid' => '#1c1917',
            'sidebar_deep' => '#0c0a09',
            'accent' => '#d97706',
            'accent_light' => '#f59e0b',
            'accent_soft' => '#fef3c7',
            'bg' => '#fafaf9',
            'surface' => '#ffffff',
            'text' => '#1c1917',
            'muted' => '#78716c',
            'border' => '#e7e5e4',
            'theme_color' => '#292524',
            'html_size' => '93.75%',
            'radius' => '10px',
            'nav_active_bg' => 'rgba(217, 119, 6, 0.28)',
            'nav_active_border' => '#f59e0b',
            'nav_active_shadow' => 'rgba(217, 119, 6, 0.25)',
            'login_brand_gradient' => 'linear-gradient(145deg, #292524 0%, #1c1917 50%, #44403c 100%)',
            'login_brand_muted' => 'rgba(253, 230, 138, 0.9)',
            'login_btn_gradient' => 'linear-gradient(135deg, #d97706, #b45309)',
            'login_btn_shadow' => 'rgba(217, 119, 6, 0.35)',
            'login_focus_ring' => 'rgba(217, 119, 6, 0.18)',
            'login_link' => '#b45309',
        ];
    }

    public static function forWorkspaceType(?string $type = null): array
    {
        $type ??= WorkspaceProfile::current();

        return $type === WorkspaceProfile::TYPE_EXECUTIVE
            ? self::warmExecutive()
            : self::slatePro();
    }

    public static function resolveForLogin(?string $workspaceSlug): array
    {
        if (! $workspaceSlug) {
            return self::slatePro();
        }

        $org = Organization::where('slug', strtolower(trim($workspaceSlug)))->where('is_active', true)->first();
        if (! $org) {
            return self::slatePro();
        }

        OrganizationContext::set($org->id);
        $preset = self::forWorkspaceType(WorkspaceProfile::current());
        OrganizationContext::clear();

        return $preset;
    }
}
