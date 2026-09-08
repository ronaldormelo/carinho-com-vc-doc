<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ViewsWhatsAppScanTest extends TestCase
{
    public function test_site_views_do_not_print_whatsapp_number_without_the_link_component(): void
    {
        $viewsPath = dirname(__DIR__, 2).'/resources/views';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
        $bare = [];

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            $content = (string) file_get_contents($path);
            $content = preg_replace('#<script type="application/ld\+json">.*?</script>#s', '', $content) ?? $content;

            if (str_ends_with($path, '/components/whatsapp-number.blade.php')) {
                $this->assertStringContainsString('<a', $content);
                $this->assertStringContainsString("route('whatsapp.cta'", $content);
                continue;
            }

            if (str_contains($content, "config('branding.contact.whatsapp_display')")) {
                if (str_ends_with($path, '/layouts/app.blade.php') && str_contains($content, '"telephone":')) {
                    continue;
                }
                $bare[] = $path;
            }
        }

        $this->assertSame([], $bare, "Número visível sem <x-whatsapp-number />:\n".implode("\n", $bare));
    }
}
