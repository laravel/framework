<?php

// Stub of the global function FrankenPHP defines when its built-in Mercure
// hub is enabled. Only process-isolated tests may load it: its mere presence
// switches the "url"-less Mercure connection over to the built-in hub.
if (! function_exists('mercure_publish')) {
    function mercure_publish(string|array $topics, string $data = '', bool $private = false, ?string $id = null, ?string $type = null, ?int $retry = null): string
    {
        $GLOBALS['mercure_published'][] = compact('topics', 'data', 'private', 'id', 'type', 'retry');

        return 'urn:uuid:00000000-0000-0000-0000-000000000000';
    }
}
