<?php

namespace BinaryCats\LobWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;

class LobWebhooksController
{
    /**
     * Invoke controller method.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string|null $configKey
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'lob',
            'signing_secret' => ($configKey) ?
                config('lob-webhooks.signing_secret_'.$configKey) :
                config('lob-webhooks.signing_secret'),
            'signature_header_name' => 'lob-signature',
            'signature_validator' => LobSignatureValidator::class,
            'webhook_profile' => ProcessEverythingWebhookProfile::class,
            'webhook_model' => config('lob-webhooks.model'),
            'process_webhook_job' => config('lob-webhooks.process_webhook_job'),
        ]);

        (new WebhookProcessor($request, $webhookConfig))->process();

        return response()->json(['message' => 'ok']);
    }
}
