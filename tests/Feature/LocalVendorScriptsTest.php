<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocalVendorScriptsTest extends TestCase
{
    public function test_no_view_loads_scripts_from_an_outside_cdn(): void
    {
        $offenders = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            if (preg_match('#<script[^>]+src=["\']https?://(?!cdn\.tailwindcss\.com)#i', $file->getContents())) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'Serve scripts from public/vendor instead of an outside site.');
    }

    public function test_vendor_files_referenced_by_views_exist(): void
    {
        $missing = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            preg_match_all("#asset\\('(vendor/[^']+)'\\)#", $file->getContents(), $m);
            foreach ($m[1] as $path) {
                if (! is_file(public_path($path))) {
                    $missing[] = $path;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }
}
