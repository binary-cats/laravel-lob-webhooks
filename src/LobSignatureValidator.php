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
            'token' => json_encode($request->all()),
            'timestamp'  => $request->header('lob-signature-timestamp'),
            'signature'  => $request->header('lob-signature'),
        ];

        $secret = $config->signingSecret;

        try {
            Webhook::constructEvent($request->all(), $signatureArray, $secret);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }
}
