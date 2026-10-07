<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use JMac\Testing\Double;
use PHPUnit\Framework\TestCase;

class RoutingResponseFactoryTest extends TestCase
{
    public function testStreamDownloadFilenameWithoutAsciiEquivalent()
    {
        $factory = new ResponseFactory(Double::for(ViewFactory::class), Double::for(Redirector::class));
        $response = $factory->streamDownload(fn () => null, '請求書');
        $this->assertSame('attachment; filename=___; filename*=utf-8\'\'%E8%AB%8B%E6%B1%82%E6%9B%B8', $response->headers->get('content-disposition'));
    }
}
