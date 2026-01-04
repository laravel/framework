<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\BinaryCodec;
use InvalidArgumentException;
use RuntimeException;

class AsBinary implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<string|null, string|null>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            private string $format;

            private bool $isRequired;

            public function __construct(protected array $arguments)
            {
                [$format, $required] = array_pad(array_values($this->arguments), 2, null);
                $this->format = $format ?: throw new InvalidArgumentException('The binary codec format is required.');
                $this->isRequired = str($required ?? false)->toBoolean();
                $allowedFormats = array_keys(BinaryCodec::all());

                if (! in_array($this->format, $allowedFormats, true)) {
                    throw new InvalidArgumentException(sprintf(
                        'Unsupported binary codec format [%s]. Allowed formats are: %s.',
                        $this->format,
                        implode(', ', $allowedFormats),
                    ));
                }
            }

            public function get($model, $key, $value, $attributes)
            {
                $decoded = BinaryCodec::decode($attributes[$key] ?? null, $this->format);

                if ($this->isRequired && blank($decoded)) {
                    throw new RuntimeException(sprintf(
                        'Binary decode resulted in empty value for required attribute "%s" (format: %s).',
                        $key,
                        $this->format,
                    ));
                }

                return $decoded;
            }

            public function set($model, $key, $value, $attributes)
            {
                return [$key => BinaryCodec::encode($value, $this->format)];
            }
        };
    }

    public static function uuid(bool $isRequired = false): string
    {
        return self::of('uuid', $isRequired);
    }

    public static function ulid(bool $isRequired = false): string
    {
        return self::of('ulid', $isRequired);
    }

    public static function of(string $format, bool $isRequired): string
    {
        return self::class.':'.implode(',', [
            $format,
            $isRequired ? '1' : '0',
        ]);
    }
}
