<?php

namespace BinaryCats\LobWebhooks\Tests;

use BinaryCats\LobWebhooks\ProcessLobWebhookJob;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookClient\Models\WebhookCall;

class LobWebhookCallTest extends TestCase
{
    /** @var \BinaryCats\LobWebhooks\ProcessLobWebhookJob */
    public $processLobWebhookJob;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['lob-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name' => 'lob',
            'payload' => [
                'event_type' => [
                    'id' => 'my.type',
                    'object' => 'event_type',
                ],
            ],
        ]);

        $this->processLobWebhookJob = new ProcessLobWebhookJob($this->webhookCall);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->processLobWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['lob-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processLobWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['lob-webhooks.jobs' => []]);

        $this->processLobWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['lob-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processLobWebhookJob->handle();

        $webhookCall = $this->webhookCall;

        Event::assertDispatched("lob-webhooks::{$webhookCall->payload['event_type']['id']}", function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertNull(cache('dummyjob'));
    }
}
