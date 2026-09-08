<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ViewsMailtoScanTest extends TestCase
{
    public function test_site_views_do_not_show_brand_emails_without_mailto(): void
    {
        $viewsPath = dirname(__DIR__, 2).'/resources/views';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
        $scanned = 0;

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $scanned++;
            $content = (string) file_get_contents($file->getPathname());
            $content = preg_replace('#<script type="application/ld\+json">.*?</script>#s', '', $content) ?? $content;

            if (!preg_match_all('/[A-Z0-9._%+\-]+@carinho\.com\.vc/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[0] as [$email, $offset]) {
                $before = substr($content, max(0, $offset - 120), 120);

                $this->assertTrue(
                    str_contains($before, 'mailto:') || str_contains($before, '<x-mailto'),
                    sprintf(
                        'O e-mail %s em %s precisa de link mailto.',
                        $email,
                        $file->getPathname()
                    )
                );
            }
        }

        $this->assertGreaterThan(0, $scanned);
        $this->assertFileExists($viewsPath.'/components/mailto.blade.php');
    }

    public function test_contact_emails_in_views_use_mailto_component(): void
    {
        $viewsPath = dirname(__DIR__, 2).'/resources/views';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
        $barePrints = [];

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());
            $content = preg_replace('#<script type="application/ld\+json">.*?</script>#s', '', $content) ?? $content;

            if (preg_match_all("/\{\{\s*config\('branding\.contact\.email(?:_[a-z]+)?'\)\s*\}\}/", $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as [$snippet, $offset]) {
                    $before = substr($content, max(0, $offset - 80), 80);
                    if (!str_contains($before, ':address=') && !str_contains($before, 'mailto:')) {
                        $barePrints[] = $file->getPathname().': '.$snippet;
                    }
                }
            }
        }

        $this->assertSame([], $barePrints, "E-mails de contato impressos sem mailto:\n".implode("\n", $barePrints));
    }
}
