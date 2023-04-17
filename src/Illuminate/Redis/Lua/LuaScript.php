<?php

namespace Illuminate\Redis\Lua;

/**
 * A class representing a Lua script with code or SHA1 hash, used for executing with {@link LuaScriptExecutor}.
 */
class LuaScript
{
    /**
     * The lua script code.
     *
     * @var string|null
     */
    private $script;

    /**
     * The SHA1 hash of the script that's already loaded in Redis.
     *
     * @var string|null
     */
    private $sha1;

    /**
     * Create a new LuaScript instance.
     *
     * @param  string|null  $script  The lua script code.
     * @param  string|null  $sha1  The SHA1 hash of the script that's already loaded in Redis.
     */
    private function __construct($script, $sha1 = null)
    {
        $this->script = $script;
        $this->sha1 = $sha1;
    }

    /**
     * Create a new instance from a plain script.
     *
     * @param  string  $script  The script.
     * @return self
     *
     * @throws \InvalidArgumentException if $script parameter is null or empty.
     */
    public static function fromPlainScript($script)
    {
        if (empty($script)) {
            throw new \InvalidArgumentException('The parameter $script cannot be null or empty.');
        }

        return new self($script);
    }

    /**
     * Create a new instance from a hash of the script.
     *
     * @param  string  $sha1  The sha1 hash of the script.
     * @return self
     *
     * @throws \InvalidArgumentException if $sha1 parameter is null or empty.
     */
    public static function fromSHA1Hash($sha1)
    {
        if (empty($sha1)) {
            throw new \InvalidArgumentException('The parameter $sha1 cannot be null or empty.');
        }

        return new self(null, $sha1);
    }

    /**
     * Returns the plain script.
     *
     * @return string|null The plain script or null if it has not been set.
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * Return the SHA1 hash of the script.
     *
     * @return string|null The SHA1 hash or null if it has not been set.
     */
    public function getSha1()
    {
        return $this->sha1;
    }

    /**
     * Determines if the current instance represents a plain Lua script.
     *
     * @return bool Returns true if the instance represents a plain Lua script, false if it represents a SHA1 hash.
     */
    public function isPlainScript()
    {
        return $this->script !== null;
    }
}
