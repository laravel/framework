<?php

namespace Illuminate\Broadcasting;

use InvalidArgumentException;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\Util\Base64UrlSafe;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\Dir;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use LogicException;
use SensitiveParameter;

/**
 * Encrypts Mercure updates end to end, so the hub never sees their content.
 *
 * As recommended by the Mercure specification, updates are encrypted as
 * JSON Web Encryption ([RFC 7516](https://www.rfc-editor.org/rfc/rfc7516.html))
 * compact tokens and the keys are shared with subscribers out of band, as
 * JSON Web Keys ([RFC 7517](https://www.rfc-editor.org/rfc/rfc7517.html))
 * returned by the broadcasting auth endpoint; the hub is not involved in
 * this exchange.
 *
 * Each channel gets its own AES-256-GCM key, derived from the single
 * configured key with HKDF, so a subscriber's keys only ever decrypt the
 * channels it was individually authorized to join. Derivation is
 * deterministic: rotating the configured key re-keys every channel at once,
 * and a subscriber removed from a channel keeps that channel's key until
 * such a rotation.
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class MercureChannelEncrypter
{
    /**
     * The JWE builder, built on first use and reused across updates.
     *
     * @var \Jose\Component\Encryption\JWEBuilder|null
     */
    protected $jweBuilder;

    /**
     * The compact JWE serializer.
     *
     * @var \Jose\Component\Encryption\Serializer\CompactSerializer|null
     */
    protected $serializer;

    /**
     * Create a new Mercure channel encrypter.
     *
     * @param  string  $key
     */
    public function __construct(#[SensitiveParameter] protected string $key)
    {
        if (! class_exists(JWEBuilder::class)) {
            throw new LogicException('web-token/jwt-library is required to use end-to-end encrypted Mercure channels. You may install it via: composer require web-token/jwt-library');
        }

        if (strlen($key) !== 32) {
            throw new InvalidArgumentException('The Mercure channel encryption key must be exactly 32 bytes.');
        }
    }

    /**
     * Derive the AES-256-GCM key of the given channel.
     *
     * Subscribers receive it pre-derived (see channelJwk()) and never derive
     * it themselves.
     *
     * @param  string  $channel
     * @return string
     */
    public function channelKey($channel)
    {
        return hash_hkdf('sha256', $this->key, 32, $channel);
    }

    /**
     * Build the JSON Web Key sharing the given channel's key with an
     * authorized subscriber, importable as-is by the Web Cryptography
     * API's importKey('jwk', ...).
     *
     * @param  string  $channel
     * @return array{kty: string, k: string, alg: string, use: string}
     */
    public function channelJwk($channel)
    {
        return [
            'kty' => 'oct',
            'k' => Base64UrlSafe::encodeUnpadded($this->channelKey($channel)),
            'alg' => 'A256GCM',
            'use' => 'enc',
        ];
    }

    /**
     * Encrypt the given plaintext for the given channel, as a compact JWE.
     *
     * Direct encryption ("alg": "dir") is used since the channel key is
     * already shared with subscribers through the auth endpoint: wrapping a
     * per-message key would add nothing. The channel name isn't repeated in
     * the header: subscribers select the key from the update's plaintext
     * routing envelope (see MercureBroadcaster::broadcast()).
     *
     * @param  string  $plaintext
     * @param  string  $channel
     * @return string
     */
    public function encrypt($plaintext, $channel)
    {
        $this->jweBuilder ??= new JWEBuilder(new AlgorithmManager([new Dir, new A256GCM]));
        $this->serializer ??= new CompactSerializer;

        $jwe = $this->jweBuilder->create()
            ->withPayload($plaintext)
            ->withSharedProtectedHeader(['alg' => 'dir', 'enc' => 'A256GCM'])
            ->addRecipient(new JWK(['kty' => 'oct', 'k' => Base64UrlSafe::encodeUnpadded($this->channelKey($channel))]))
            ->build();

        return $this->serializer->serialize($jwe, 0);
    }
}
