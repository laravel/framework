<?php

namespace Illuminate\Tests\Validation;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ValidationUrlRuleTest extends TestCase
{
    public function testUrlRuleStringification()
    {
        $rule = Rule::url();

        $this->assertSame('url', (string) $rule);
    }

    #[TestWith([true, 'active_url'])]
    #[TestWith([false, 'url'])]
    public function testActiveUrlRuleStringification(bool $active, string $rule)
    {
        $rule = Rule::url()->active($active);

        $this->assertSame($rule, (string) $rule);
    }

    public function testUrlRuleConstructorProtocolsStringification()
    {
        $rule = Rule::url('http', 'https');

        $this->assertSame('url:http,https', (string) $rule);

        $rule = Rule::url(['http', 'https']);

        $this->assertSame('url:http,https', (string) $rule);

        $rule = Rule::url(collect(['http', 'https']));

        $this->assertSame('url:http,https', (string) $rule);
    }

    public function testUrlRuleProtocolsStringification()
    {
        $rule = Rule::url()->protocols('http', 'https');

        $this->assertSame('url:http,https', (string) $rule);

        $rule = Rule::url()->protocols(['http', 'https']);

        $this->assertSame('url:http,https', (string) $rule);

        $rule = Rule::url()->protocols(collect(['http', 'https']));

        $this->assertSame('url:http,https', (string) $rule);

        $rule = Rule::url('ftp')->protocols(collect(['http', 'https']));

        $this->assertSame('url:http,https', (string) $rule);
    }

    #[TestWith(['http', 'url:http,https'])]
    #[TestWith(['ftp', 'url:http,https,ftp'])]
    public function testUrlRuleProtocolStringification(string $input, string $output)
    {
        $rule = Rule::url('http', 'https')->protocol($input);

        $this->assertSame($output, (string) $rule);
    }
}
