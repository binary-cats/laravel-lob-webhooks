<?php

namespace BinaryCats\LobWebhooks;

use BinaryCats\LobWebhooks\Exceptions\SignatureVerificationException;
use Illuminate\Support\Arr;

class WebhookSignature
{
    /**
     * Signature array.
     *
     * @var array
     */
    protected $signatureArray;

    /**
     * Signature secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * Create new Signature.
     *
     * @param array  $signatureArray
     * @param string $secret
     */
    public function __construct(array $signatureArray, string $secret)
    {
        $this->signatureArray = $signatureArray;
        $this->secret = $secret;
    }

    /**
     * Statis accessor into the class constructor.
     *
     * @param  array  $signatureArray
     * @param  string $secret
     * @return new static
     */
    public static function make($signatureArray, string $secret)
    {
        return new static(Arr::wrap($signatureArray), $secret);
    }

    /**
     * True if the signature is valid.
     *
     * @return bool
     * @throws BinaryCats\LobWebhooks\Exceptions\SignatureVerificationException when validation fails
     */
    public function verify() : bool
    {
        if (hash_equals($this->signature, $this->computeSignature())) {
            return true;
        }

        throw new SignatureVerificationException("Signature Verification Failed", 500);
    }

    /**
     * Compute expected signature.
     *
     * @return string
     */
    protected function computeSignature()
    {
        $comparator = implode('.', [
            $this->timestamp,
            $this->token,
        ]);

        return hash_hmac('sha256', $comparator, $this->secret);
    }

    /**
     * Magically access items from signature array.
     *
     * @param  string $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        return Arr::get($this->signatureArray, $attribute);
    }
}
