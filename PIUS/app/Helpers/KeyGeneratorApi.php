<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class KeyGeneratorApi
{
    private const BASE_URL = 'http://127.0.0.1:8000/api/v1';
    public static function generateKey(string $telegramCode, string $fileName): array
    {
        if (strlen($telegramCode) > 10 || strlen($fileName) > 100) {
            return [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid input length'
                ]
            ];
        }

        $response = Http::post(self::BASE_URL.'/key/generate', [
            'telegram_code' => $telegramCode,
            'file_name' => $fileName,
        ]);

        if ($response->status() !== 201) {
            $errorData = $response->json();
            return [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => $response->status(),
                    'message' => $errorData['message'] ?? 'Key generation failed'
                ]
            ];
        }

        return [
            'success' => true,
            'data' => $response->json(),
            'error' => null
        ];
    }

    public static function getKey(int $keyId, string $telegramCode): array
    {
        if (strlen($telegramCode) > 10) {
            return [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 400,
                    'message' => 'Telegram code too long'
                ]
            ];
        }

        $response = Http::post(self::BASE_URL.'/key/get_key', [
            'id' => $keyId,
            'telegram_code' => $telegramCode,
        ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            return [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => $response->status(),
                    'message' => $errorData['message'] ?? 'Failed to get key'
                ]
            ];
        }

        return [
            'success' => true,
            'data' => ['key' => $response->json()['key']],
            'error' => null
        ];
    }
}