<?php

namespace BinaryCats\LobWebhooks;

use BinaryCats\LobWebhooks\Exceptions\WebhookFailed;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessLobWebhookJob extends ProcessWebhookJob
{
    /**
     * Name of the payload key to contain the type of event.
     *
     * @var string
     */
    protected $key = 'event_type.id';

    /**
     * Handle the process.
     *
     * @return void
     */
    public function handle()
    {
        $type = Arr::get($this->webhookCall, "payload.{$this->key}");

        if (! $type) {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event($this->determineEventKey($type), $this->webhookCall);

        $jobClass = $this->determineJobClass($type);

        if ('' === $jobClass) {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this->webhookCall);
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    /**
     * @param  string  $eventType
     * @return string
     */
    protected function determineJobClass(string $eventType): string
    {
        return config($this->determineJobConfigKey($eventType), '');
    }

    /**
     * @param  string  $eventType
     * @return string
     */
    protected function determineJobConfigKey(string $eventType): string
    {
        return Str::of($eventType)
            ->replace('.', '_')
            ->prepend('lob-webhooks.jobs.')
            ->lower();
    }

    /**
     * @param  string  $eventType
     * @return string
     */
    protected function determineEventKey(string $eventType): string
    {
        return Str::of($eventType)
            ->prepend('lob-webhooks::')
            ->lower();
    }
}
