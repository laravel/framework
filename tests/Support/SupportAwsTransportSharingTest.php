<?php

namespace Illuminate\Tests\Support;

use Aws\Handler\HttpTransportSharing;
use Illuminate\Support\AwsTransportSharing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SupportAwsTransportSharingTest extends TestCase
{
    public function testRemovesAbsentOrNullMode()
    {
        $this->assertSame(['region' => 'us-east-1'], AwsTransportSharing::apply(['region' => 'us-east-1']));
        $this->assertSame(['region' => 'us-east-1'], AwsTransportSharing::apply(['region' => 'us-east-1', 'transport_sharing' => null]));
    }

    public function testForwardsModesWhenSdkSupportsTransportSharing()
    {
        if (! class_exists(HttpTransportSharing::class)) {
            $this->markTestSkipped('The installed AWS SDK does not support transport sharing.');
        }

        $this->assertSame(
            ['region' => 'us-east-1', 'transport_sharing' => 'persistent_require'],
            AwsTransportSharing::apply(['region' => 'us-east-1', 'transport_sharing' => 'persistent_require'])
        );
    }

    public function testRemovesPreferModesWhenSdkLacksTransportSharing()
    {
        if (class_exists(HttpTransportSharing::class)) {
            $this->markTestSkipped('The installed AWS SDK supports transport sharing.');
        }

        $this->assertSame(['region' => 'us-east-1'], AwsTransportSharing::apply(['region' => 'us-east-1', 'transport_sharing' => 'none']));
        $this->assertSame(['region' => 'us-east-1'], AwsTransportSharing::apply(['region' => 'us-east-1', 'transport_sharing' => 'handler_prefer']));
        $this->assertSame(['region' => 'us-east-1'], AwsTransportSharing::apply(['region' => 'us-east-1', 'transport_sharing' => 'persistent_prefer']));
    }

    public function testRequireModesFailWhenSdkLacksTransportSharing()
    {
        if (class_exists(HttpTransportSharing::class)) {
            $this->markTestSkipped('The installed AWS SDK supports transport sharing.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "persistent_require" transport sharing mode requires a version of aws/aws-sdk-php that supports the "transport_sharing" client option.');

        AwsTransportSharing::apply(['transport_sharing' => 'persistent_require']);
    }
}
