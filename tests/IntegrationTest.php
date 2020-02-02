<?php

namespace BinaryCats\LobWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\Models\WebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Route::lobWebhooks('lob-webhook-url');
        Route::lobWebhooks('lob-webhook-url/{configKey}');

        config(['lob-webhooks.jobs' => ['my_type' => DummyJob::class]]);
        cache()->clear();
    }

    /** @test */
    public function it_can_handle_a_valid_request()
    {
        $payload = [
            'event_type' => [
                'id' => 'my.type',
                'key' => 'value',
            ],
        ];

        $headers = [
            'lob-signature-timestamp' => $timestamp = time(),
            'lob-signature' => $this->determineLobSignature($payload, $timestamp),
        ];

        $this
            ->postJson('lob-webhook-url', $payload, $headers)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('my.type', $webhookCall->payload['event_type']['id']);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('lob-webhooks::my.type', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function a_request_with_an_invalid_signature_wont_be_logged()
    {
        $payload = [
            'event_type' => [
                'id' => 'my.type',
                'key' => 'value',
            ],
        ];

        $headers = [
            'lob-signature-timestamp' => 0,
            'lob-signature' => 'incorrect_signature',
        ];

        $this
            ->postJson('lob-webhook-url', $payload, $headers)
            ->assertStatus(500);

        $this->assertCount(0, WebhookCall::get());

        Event::assertNotDispatched('lob-webhooks::my.type');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function a_request_with_an_invalid_payload_will_be_logged_but_events_and_jobs_will_not_be_dispatched()
    {
        $payload = ['invalid_payload'];

        $headers = [
            'lob-signature-timestamp' => $timestamp = time(),
            'lob-signature' => $this->determineLobSignature($payload, $timestamp),
        ];

        $this
            ->postJson('lob-webhook-url', $payload, $headers)
            ->assertStatus(400);

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertFalse(isset($webhookCall->payload['event_type']['id']));

        $this->assertEquals(['invalid_payload'], $webhookCall->payload);

        $this->assertEquals('Webhook call id `1` did not contain a type. Valid Lob.com webhook calls should always contain a type.', $webhookCall->exception['message']);

        Event::assertNotDispatched('lob-webhooks::my.type');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test * */
    public function a_request_with_a_config_key_will_use_the_correct_signing_secret()
    {
        config()->set('lob-webhooks.signing_secret', 'secret1');
        config()->set('lob-webhooks.signing_secret_somekey', 'secret2');

        $payload = [
            'event_type' => [
                'id' => 'my.type',
                'key' => 'value',
            ],
        ];

        $headers = [
            'lob-signature-timestamp' => $timestamp = time(),
            'lob-signature' => $this->determineLobSignature($payload, $timestamp, 'somekey'),
        ];

        $this
            ->postJson('lob-webhook-url/somekey', $payload, $headers)
            ->assertSuccessful();
    }
}
