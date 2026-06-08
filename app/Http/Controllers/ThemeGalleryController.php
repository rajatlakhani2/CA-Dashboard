<?php

namespace App\Http\Controllers;

class ThemeGalleryController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->isWorkspaceOwner(), 403);

        return view('demo.theme-gallery', [
            'themes' => $this->themes(),
        ]);
    }

    private function themes(): array
    {
        return [
            [
                'id' => 'vouchex',
                'name' => 'VouchEx Navy',
                'badge' => 'Current',
                'font' => 'DM Sans',
                'font_url' => 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;1,9..40,400&display=swap',
                'sidebar' => '#0c1f4a',
                'sidebar_mid' => '#0a1838',
                'accent' => '#2563eb',
                'accent_soft' => '#dbeafe',
                'bg' => '#f8fafc',
                'text' => '#0f172a',
                'muted' => '#64748b',
                'radius' => '14px',
                'base_size' => '16px',
                'vibe' => 'Professional navy — what you have today.',
            ],
            [
                'id' => 'slate',
                'name' => 'Slate Pro',
                'badge' => 'Recommended',
                'font' => 'Inter',
                'font_url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
                'sidebar' => '#1e293b',
                'sidebar_mid' => '#0f172a',
                'accent' => '#3b82f6',
                'accent_soft' => '#eff6ff',
                'bg' => '#f1f5f9',
                'text' => '#0f172a',
                'muted' => '#64748b',
                'radius' => '12px',
                'base_size' => '15px',
                'vibe' => 'Crisp, neutral — popular in fintech dashboards.',
            ],
            [
                'id' => 'forest',
                'name' => 'Forest Calm',
                'badge' => null,
                'font' => 'Source Sans 3',
                'font_url' => 'https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap',
                'sidebar' => '#14532d',
                'sidebar_mid' => '#052e16',
                'accent' => '#059669',
                'accent_soft' => '#d1fae5',
                'bg' => '#f7faf8',
                'text' => '#14532d',
                'muted' => '#4b5563',
                'radius' => '12px',
                'base_size' => '15px',
                'vibe' => 'Calm green — compliance & trust feel.',
            ],
            [
                'id' => 'warm',
                'name' => 'Warm Executive',
                'badge' => 'CEO/CFO',
                'font' => 'Libre Franklin',
                'font_url' => 'https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;500;600;700&display=swap',
                'sidebar' => '#292524',
                'sidebar_mid' => '#1c1917',
                'accent' => '#d97706',
                'accent_soft' => '#fef3c7',
                'bg' => '#fafaf9',
                'text' => '#1c1917',
                'muted' => '#78716c',
                'radius' => '10px',
                'base_size' => '15px',
                'vibe' => 'Warm stone + gold — boardroom / executive tone.',
            ],
            [
                'id' => 'violet',
                'name' => 'Soft Violet',
                'badge' => null,
                'font' => 'Plus Jakarta Sans',
                'font_url' => 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap',
                'sidebar' => '#312e81',
                'sidebar_mid' => '#1e1b4b',
                'accent' => '#6366f1',
                'accent_soft' => '#e0e7ff',
                'bg' => '#f5f3ff',
                'text' => '#1e1b4b',
                'muted' => '#6b7280',
                'radius' => '16px',
                'base_size' => '15px',
                'vibe' => 'Softer purple — modern SaaS, lighter than navy.',
            ],
        ];
    }
}
