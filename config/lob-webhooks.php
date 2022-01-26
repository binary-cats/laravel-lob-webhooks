<?php

return [

    /*
     * Lob.com will sign each webhook using a secret. You can find the used secret at the
     * webhook configuration settings: https://dashboard.lob.com/#/webhooks/create.
     *
     * For test environment the secret is a fixed string "secret"
     */
    'signing_secret' => env('LOB_WEBHOOK_SECRET', 'secret'),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Lob.com event type with the `.` replaced by a `_`.
     *
     * You can find a list of Lob.com webhook types here:
     * https://lob.com/docs#all_event_types
     *
     * The package will automatically convert the keys to lowercase, but you should
     * be congnisant of the fact that array keys are case sensitive
     */
    'jobs' => [
        // 'letter.created' => \BinaryCats\LobWebhooks\Jobs\HandleLetter_Created::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\WebhookClient\Models\WebhookCall
     */
    'model' => \Spatie\WebhookClient\Models\WebhookCall::class,

    /*
     * The classname of the model to be used. The class should equal or extend
     * BinaryCats\LobWebhooks\ProcessLobWebhookJob
     */
    'process_webhook_job' => \BinaryCats\LobWebhooks\ProcessLobWebhookJob::class,
];
