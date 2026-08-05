<?php

namespace Illuminate\Support;

use Aws\Handler\HttpTransportSharing;
use RuntimeException;

class AwsTransportSharing
{
    /**
     * Apply the AWS SDK "transport_sharing" option supported by the installed SDK.
     *
     * When the installed AWS SDK does not support the option, the "none" and
     * "*_prefer" modes are removed from the configuration, while the
     * "*_require" modes fail loudly.
     *
     * @param  array  $config
     * @return array
     *
     * @throws \RuntimeException
     */
    public static function apply(array $config): array
    {
        $sharing = $config['transport_sharing'] ?? null;

        if ($sharing === null) {
            unset($config['transport_sharing']);

            return $config;
        }

        if (class_exists(HttpTransportSharing::class)) {
            return $config;
        }

        if (in_array($sharing, ['handler_require', 'persistent_require'], true)) {
            throw new RuntimeException(sprintf(
                'The "%s" transport sharing mode requires a version of aws/aws-sdk-php that supports the "transport_sharing" client option.',
                $sharing
            ));
        }

        unset($config['transport_sharing']);

        return $config;
    }
}
