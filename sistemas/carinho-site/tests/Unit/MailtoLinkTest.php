<?php

namespace Tests\Unit;

use App\Support\MailtoLink;
use PHPUnit\Framework\TestCase;

class MailtoLinkTest extends TestCase
{
    public function test_valid_email_becomes_mailto_href(): void
    {
        $this->assertSame(
            'mailto:contato@carinho.com.vc',
            MailtoLink::href('contato@carinho.com.vc')
        );
    }

    public function test_trims_whitespace_before_validating(): void
    {
        $this->assertSame(
            'privacidade@carinho.com.vc',
            MailtoLink::normalize('  privacidade@carinho.com.vc  ')
        );
    }

    public function test_rejects_empty_and_invalid_addresses(): void
    {
        $this->assertNull(MailtoLink::href(null));
        $this->assertNull(MailtoLink::href(''));
        $this->assertNull(MailtoLink::href('   '));
        $this->assertNull(MailtoLink::href('nao-e-email'));
        $this->assertNull(MailtoLink::href('javascript:alert(1)'));
        $this->assertNull(MailtoLink::href('contato@'));
    }
}
