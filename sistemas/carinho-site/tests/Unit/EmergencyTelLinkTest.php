<?php

namespace Tests\Unit;

use App\Support\EmergencyTelLink;
use PHPUnit\Framework\TestCase;

class EmergencyTelLinkTest extends TestCase
{
    public function test_emergency_numbers_become_tel_href(): void
    {
        $this->assertSame('tel:192', EmergencyTelLink::href('192'));
        $this->assertSame('tel:193', EmergencyTelLink::href('193'));
        $this->assertSame('tel:190', EmergencyTelLink::href('190'));
        $this->assertSame('tel:188', EmergencyTelLink::href('188'));
    }

    public function test_rejects_other_numbers(): void
    {
        $this->assertNull(EmergencyTelLink::href('1933'));
        $this->assertNull(EmergencyTelLink::href('99977-1471'));
        $this->assertNull(EmergencyTelLink::href(''));
        $this->assertNull(EmergencyTelLink::href(null));
    }

    public function test_linkify_wraps_only_emergency_numbers(): void
    {
        $html = EmergencyTelLink::linkify('Ligar 192 (SAMU) e avisar.');

        $this->assertStringContainsString('href="tel:192"', $html);
        $this->assertStringContainsString('>192</a>', $html);
        $this->assertStringContainsString('(SAMU)', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_linkify_escapes_html_before_wrapping(): void
    {
        $html = EmergencyTelLink::linkify('<b>192</b>');

        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringContainsString('href="tel:192"', $html);
    }
}
