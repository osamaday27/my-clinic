<?php
namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** WhatsApp Business Cloud API adapter. Business-initiated messages MUST use a Meta-approved template whose body is "{{1}}". */
class WhatsAppCloudGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        $c = config('clinic.whatsapp');
        if (blank($c['token']) || blank($c['phone_id'])) {
            throw new RuntimeException('WhatsApp is not configured (WHATSAPP_TOKEN / WHATSAPP_PHONE_ID).');
        }

        $response = Http::withToken($c['token'])->acceptJson()->timeout(10)
            ->post("https://graph.facebook.com/{$c['api_version']}/{$c['phone_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalise($phone, $c['country_code']),
                'type' => 'template',
                'template' => [
                    'name' => $c['template'],
                    'language' => ['code' => $c['language']],
                    'components' => [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => preg_replace('/\s+/', ' ', $message)]]]],
                ],
            ]);

        if ($response->failed()) {
            // Status only: the response body can echo the patient's phone/message (PHI) and must not reach logs.
            throw new RuntimeException('WhatsApp send failed: HTTP '.$response->status());     // job retries with back-off
        }
    }

    /** 0101234567 -> 20101234567 ; +2010... / 002010... -> 2010... */
    public function normalise(string $phone, string $country): string
    {
        $d = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($d, '00')) {
            return substr($d, 2);
        }

        return str_starts_with($d, '0') ? $country.substr($d, 1) : $d;
    }
}

/*
SYSTEM DESIGN:
Second adapter behind the SmsGateway port (Open/Closed): switching channel = CLINIC_REMINDER_CHANNEL=whatsapp, no business code changes. Token comes from .env only; failures raise so the queued job retries.

DATA STRUCTURE:
Adapter pattern; phone normalisation = digit filter + country-code prefix rule.
*/
