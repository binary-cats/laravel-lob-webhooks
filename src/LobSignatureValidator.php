<?php

namespace BinaryCats\LobWebhooks;

use Exception;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class LobSignatureValidator implements SignatureValidator
{
    /**
     * Bind the implemetation.
     *
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * Inject the config.
     *
     * @var Spatie\WebhookClient\WebhookConfig
     */
    protected $config;

    /**
     * True if the signature has been valiates.
     *
     * @param  Illuminate\Http\Request       $request
     * @param  Spatie\WebhookClient\WebhookConfig $config
     *
     * @return bool
     */
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signatureArray = [
            'token'     => $this->payload($request),
            'timestamp' => $request->header('lob-signature-timestamp'),
            'signature' => $request->header('lob-signature'),
        ];

        $secret = $config->signingSecret;

        try {
            Webhook::constructEvent($request->input(), $signatureArray, $secret);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Compile the payload.
     *
     * @param  Illuminate\Http\Request       $request
     * @return string
     */
    protected function payload(Request $request): string
    {
        // Will decode the body into an object, not an array
        $decoded = json_decode($request->getContent());
        // recode back into string
        return json_encode($decoded, JSON_UNESCAPED_SLASHES);
    }
}
