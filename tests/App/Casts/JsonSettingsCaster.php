<?php

namespace Illuminate\Tests\App\Casts;

use Exception;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Tests\App\ValueObjects\Settings;

class JsonSettingsCaster implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Settings
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Settings) {
            return $value;
        }

        $payload = json_decode($value, true, JSON_THROW_ON_ERROR);

        return Settings::from($payload);
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = Settings::from($value);
        }

        if (! $value instanceof Settings) {
            throw new Exception("Attribute `{$key}` with JsonSettingsCaster should be a Settings object");
        }

        return $value->toJson();
    }
}
