<?php

use Illuminate\Support\Str;

use function PHPStan\Testing\assertType;

/**
 * @var mixed $json
 * @var mixed $ulid
 * @var mixed $url
 * @var mixed $uuid
 */
if (Str::isJson($json)) {
    assertType('non-empty-string', $json);
} else {
    assertType('mixed', $json);
}

if (Str::isUlid($ulid)) {
    assertType('non-empty-string', $ulid);
} else {
    assertType('mixed', $ulid);
}

if (Str::isUrl($url)) {
    assertType('non-empty-string', $url);
} else {
    assertType('mixed', $url);
}

if (Str::isUuid($uuid)) {
    assertType('non-empty-string', $uuid);
} else {
    assertType('mixed', $uuid);
}
