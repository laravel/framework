<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\TransportSharing;
use GuzzleHttp\Utils;
use RuntimeException;

enum PersistentTransport: string
{
    case None = 'none';
    case Preferred = 'preferred';
    case Required = 'required';

    /**
     * Create a Guzzle handler applying this transport mode, or null when sharing is disabled.
     *
     * The underlying handler is resolved lazily so requests answered before
     * reaching the transport (fakes, stray request guards) never build it.
     *
     * @return callable|null
     */
    public function handler()
    {
        if ($this === self::None) {
            return null;
        }

        return function ($request, $options) {
            static $handler;

            $handler ??= Utils::chooseHandler(array_filter([
                'transport_sharing' => $this->transportSharingMode(),
            ]));

            return $handler($request, $options);
        };
    }

    /**
     * Resolve the Guzzle "transport_sharing" mode for this persistence level.
     *
     * @return string|null
     *
     * @throws \RuntimeException
     */
    public function transportSharingMode()
    {
        if ($this === self::None) {
            return null;
        }

        $required = $this === self::Required;

        // Guzzle 8: persistent (cross-request) sharing.
        if (defined(TransportSharing::class.'::PERSISTENT_PREFER') && defined(TransportSharing::class.'::PERSISTENT_REQUIRE')) {
            return $required ? TransportSharing::PERSISTENT_REQUIRE : TransportSharing::PERSISTENT_PREFER;
        }

        if ($required) {
            throw new RuntimeException('Persistent HTTP transport sharing is set to "Required", but persistent cURL share handles require guzzlehttp/guzzle ^8.0.');
        }

        // Guzzle 7.11: handler-lifetime sharing only, best-effort.
        if (class_exists(TransportSharing::class)) {
            return TransportSharing::HANDLER_PREFER;
        }

        return null;
    }
}
