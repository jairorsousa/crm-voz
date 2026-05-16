<?php

namespace App\Support\CRM;

use RuntimeException;

class TwilioVoiceAccessToken
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public static function make(array $settings, string $identity): string
    {
        $accountSid = (string) ($settings['account_sid'] ?? '');
        $apiKey = (string) ($settings['api_key'] ?? '');
        $apiSecret = (string) ($settings['api_secret'] ?? '');
        $twimlAppSid = (string) ($settings['twiml_app_sid'] ?? '');

        if (blank($accountSid) || blank($apiKey) || blank($apiSecret) || blank($twimlAppSid)) {
            throw new RuntimeException('Para ligar pelo navegador, informe Account SID, API Key, API Secret e TwiML App SID no canal Twilio.');
        }

        $now = time();

        return self::jwt([
            'typ' => 'JWT',
            'alg' => 'HS256',
            'cty' => 'twilio-fpa;v=1',
        ], [
            'jti' => $apiKey.'-'.$now,
            'iss' => $apiKey,
            'sub' => $accountSid,
            'exp' => $now + 3600,
            'grants' => [
                'identity' => $identity,
                'voice' => [
                    'outgoing' => [
                        'application_sid' => $twimlAppSid,
                    ],
                    'incoming' => [
                        'allow' => false,
                    ],
                ],
            ],
        ], $apiSecret);
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $payload
     */
    private static function jwt(array $header, array $payload, string $secret): string
    {
        $segments = [
            self::base64Url(json_encode($header, JSON_THROW_ON_ERROR)),
            self::base64Url(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = self::base64Url($signature);

        return implode('.', $segments);
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
