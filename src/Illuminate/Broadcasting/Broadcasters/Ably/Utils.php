<?php

namespace Illuminate\Broadcasting\Broadcasters\Ably;

class Utils {
    // JWT related PHP utility functions
    /**
     * @param $jwt string
     * @return array
     */
    public static function parseJwt($jwt)
    {
        $tokenParts = explode('.', $jwt);
        $header = json_decode(base64_decode($tokenParts[0]), true);
        $payload = json_decode(base64_decode($tokenParts[1]), true);;
        return array('header' => $header, 'payload' => $payload);
    }

    /**
     * @param $headers array
     * @param $payload array
     * @return string
     */
    public static function generateJwt($headers, $payload, $key)
    {
        $encodedHeaders = self::base64urlEncode(json_encode($headers));
        $encodedPayload = self::base64urlEncode(json_encode($payload));

        $signature = hash_hmac('SHA256', "$encodedHeaders.$encodedPayload", $key, true);
        $encodedSignature = self::base64urlEncode($signature);

        return "$encodedHeaders.$encodedPayload.$encodedSignature";
    }

    /**
     * @param $jwt string
     * @param $timeFn
     * @return bool
     */
    public static function isJwtValid($jwt, $timeFn, $key)
    {
        // split the jwt
        $tokenParts = explode('.', $jwt);
        $header = $tokenParts[0];
        $payload = $tokenParts[1];
        $tokenSignature = $tokenParts[2];

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = json_decode(base64_decode($payload))->exp;
        $isTokenExpired = $expiration <= $timeFn();

        // build a signature based on the header and payload using the secret
        $signature = hash_hmac('SHA256', $header . "." . $payload, $key, true);
        $isSignatureValid = self::base64urlEncode($signature) === $tokenSignature;

        return $isSignatureValid && !$isTokenExpired;
    }

    public static function base64urlEncode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }
}
