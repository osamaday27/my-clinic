<?php
namespace Tests\Feature;

use App\Services\Sms\WhatsAppCloudGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WhatsAppGatewayTest extends TestCase
{
    private function configure(): void
    {
        config(['clinic.whatsapp' => ['token' => 'TOKEN123', 'phone_id' => 'PHONE9', 'template' => 'appointment_reminder', 'language' => 'ar', 'api_version' => 'v20.0', 'country_code' => '20']]);
    }

    public function test_sends_template_with_normalised_number_and_bearer_token(): void
    {
        $this->configure();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        (new WhatsAppCloudGateway())->send('01012345678', "Reminder\nDr. Hany 10:00");

        Http::assertSent(fn ($r) => str_contains($r->url(), '/PHONE9/messages')
            && $r['to'] === '201012345678'
            && $r['template']['name'] === 'appointment_reminder'
            && $r['template']['components'][0]['parameters'][0]['text'] === 'Reminder Dr. Hany 10:00'
            && $r->hasHeader('Authorization', 'Bearer TOKEN123'));
    }

    public function test_failure_raises_without_leaking_the_response_body(): void
    {
        $this->configure();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'to=201012345678 secret'], 400)]);

        try {
            (new WhatsAppCloudGateway())->send('01012345678', 'x');
            $this->fail('should throw');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('HTTP 400', $e->getMessage());
            $this->assertStringNotContainsString('201012345678', $e->getMessage());
        }
    }

    public function test_requires_configuration_and_normalises_formats(): void
    {
        config(['clinic.whatsapp' => ['token' => null, 'phone_id' => null, 'country_code' => '20']]);
        $g = new WhatsAppCloudGateway();

        $this->assertSame('201012345678', $g->normalise('+20 101 234 5678', '20'));
        $this->assertSame('201012345678', $g->normalise('00201012345678', '20'));
        $this->assertSame('201012345678', $g->normalise('01012345678', '20'));

        $this->expectException(RuntimeException::class);
        $g->send('01012345678', 'x');
    }
}

/*
SYSTEM DESIGN:
Adapter contract tests with Http::fake: payload shape, auth header, error handling without PHI leakage.

DATA STRUCTURE:

*/
