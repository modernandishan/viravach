<?php

namespace App\Services\Chat;

use App\Services\Chat\Exceptions\TranslationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslationService
{
    /**
     * Locales the configured LibreTranslate instance currently supports.
     * Any other target locale falls back to English.
     */
    protected const SUPPORTED_LOCALES = ['en', 'fa', 'ar', 'ru', 'tr'];

    /**
     * @throws TranslationException when the LibreTranslate request fails
     */
    public function translate(string $text, string $target): string
    {
        $target = in_array($target, self::SUPPORTED_LOCALES, true) ? $target : 'en';

        return Cache::store('redis')->remember(
            md5($text).':'.$target,
            now()->addHours(6),
            fn () => $this->requestTranslation($text, $target),
        );
    }

    /**
     * @throws TranslationException
     */
    protected function requestTranslation(string $text, string $target): string
    {
        try {
            $response = Http::timeout(10)
                ->withBasicAuth(
                    (string) config('services.libretranslate.basic_user'),
                    (string) config('services.libretranslate.basic_pass'),
                )
                ->post(
                    rtrim((string) config('services.libretranslate.url'), '/').'/translate',
                    array_filter([
                        'q' => $text,
                        'source' => 'auto',
                        'target' => $target,
                        'format' => 'text',
                        'api_key' => config('services.libretranslate.key'),
                    ], fn ($value) => $value !== null),
                );
        } catch (Throwable $exception) {
            Log::warning('LibreTranslate request threw an exception.', [
                'target' => $target,
                'exception' => $exception->getMessage(),
            ]);

            throw TranslationException::requestFailed();
        }

        $translated = $response->json('translatedText');

        if ($response->failed() || ! is_string($translated) || $translated === '') {
            Log::warning('LibreTranslate request failed.', [
                'target' => $target,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw TranslationException::requestFailed();
        }

        return $translated;
    }
}
