<?php

namespace App\Ai;

use App\Ai\Exceptions\ContentGenerationException;
use App\Settings\ContentSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Images-generations client for the configured Open WebUI-compatible
 * gateway, sibling to ContentGenerator but hitting
 * {base_url}/v1/images/generations instead of {base_url}/chat/completions
 * — the images endpoint lives under /v1/ even though base_url itself ends
 * in /api (see endpoint()). Transport only: it knows nothing about prompts
 * or companies. Never returns partial data — any failure throws.
 */
class ImageGenerator
{
    /**
     * Set once per process the first time a response's shape is
     * successfully identified, so a later model swap that changes which key
     * carries the image is visible in the debug log instead of only
     * surfacing as a silent failure much later.
     */
    private static bool $loggedResponseShape = false;

    public function generate(string $prompt): string
    {
        $settings = app(ContentSettings::class);

        if (! $settings->image_enabled) {
            throw new ContentGenerationException('AI image generation is disabled.');
        }

        $model = $settings->image_model;

        $startedAt = hrtime(true);
        $response = $this->send($settings, $model, $prompt);
        $bytes = $this->extractImageBytes($settings, $response, $model);

        Log::info('Image generation completed.', [
            'model' => $model,
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1e6),
        ]);

        return $bytes;
    }

    /**
     * response_format is deliberately NOT sent: the verified Open WebUI
     * gateway this app targets does not support it and ignores/rejects it —
     * the response shape is handled defensively in extractImageBytes()
     * instead of being requested.
     */
    protected function send(ContentSettings $settings, string $model, string $prompt): Response
    {
        try {
            $response = Http::timeout($settings->timeout)
                ->connectTimeout(5)
                ->withToken($settings->api_key)
                ->acceptJson()
                ->asJson()
                ->post($this->endpoint($settings), [
                    'model' => $model,
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => $settings->image_size,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Image generation: request failed.', [
                'model' => $model,
                'failure' => 'connection failed: '.$e->getMessage(),
            ]);

            throw new ContentGenerationException(
                'The image gateway connection failed: '.$e->getMessage(),
                previous: $e,
            );
        }

        if (! $response->successful()) {
            Log::warning('Image generation: request failed.', [
                'model' => $model,
                'failure' => 'HTTP '.$response->status(),
            ]);

            throw new ContentGenerationException(
                "The image gateway request failed: HTTP {$response->status()}.",
            );
        }

        return $response;
    }

    /**
     * base_url is 'http://open-webui:8080/api' (chat completions resolve to
     * {base_url}/chat/completions), but the images endpoint lives under
     * /v1/ regardless — verified against the live server.
     */
    protected function endpoint(ContentSettings $settings): string
    {
        return rtrim($settings->base_url, '/').'/v1/images/generations';
    }

    /**
     * Handles three response shapes, checked in this order:
     *   - a bare JSON array of {url: ...}       — what this server returns
     *   - {"data":[{"url": ...}]}                — OpenAI standard
     *   - {"data":[{"b64_json": ...}]}            — other gateways
     */
    protected function extractImageBytes(ContentSettings $settings, Response $response, string $model): string
    {
        $body = $response->json();

        if (! is_array($body)) {
            Log::warning('Image generation: unexpected response shape.', [
                'model' => $model,
                'status' => $response->status(),
            ]);

            throw new ContentGenerationException('The image gateway returned an unexpected response shape.');
        }

        if (array_is_list($body)) {
            $data = $body[0] ?? null;
            $container = 'bare_array';
        } else {
            $data = $body['data'][0] ?? null;
            $container = 'data';
        }

        if (! is_array($data)) {
            Log::warning('Image generation: unexpected response shape.', [
                'model' => $model,
                'status' => $response->status(),
            ]);

            throw new ContentGenerationException('The image gateway returned an unexpected response shape.');
        }

        if (! empty($data['url']) && is_string($data['url'])) {
            $this->logResponseShapeOnce("{$container}.url", $model);

            return $this->fetch($settings, $this->resolveUrl($settings, $data['url']));
        }

        if (! empty($data['b64_json']) && is_string($data['b64_json'])) {
            $this->logResponseShapeOnce("{$container}.b64_json", $model);

            $bytes = base64_decode($data['b64_json'], true);

            if ($bytes === false || $bytes === '') {
                throw new ContentGenerationException('The image gateway returned invalid base64 image data.');
            }

            return $bytes;
        }

        Log::warning('Image generation: response contained neither url nor b64_json.', [
            'model' => $model,
        ]);

        throw new ContentGenerationException('The image gateway response contained no image data.');
    }

    /**
     * A relative URL (e.g. '/api/v1/files/{uuid}/content') is relative to
     * the gateway's own host, not to this application — resolve it against
     * base_url's scheme+host+port, discarding base_url's own path.
     */
    protected function resolveUrl(ContentSettings $settings, string $url): string
    {
        if (! str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($settings->base_url);
        $origin = ($parts['scheme'] ?? 'http').'://'.($parts['host'] ?? '');

        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin.$url;
    }

    /**
     * Fetches the generated file with the same bearer token and timeout as
     * generation, then verifies it is actually an image (by content type or
     * magic bytes) before returning it — a gateway auth failure or a
     * moved/expired file often comes back as a 200 HTML error page, which
     * must never be silently written to the featured_image collection.
     */
    protected function fetch(ContentSettings $settings, string $url): string
    {
        try {
            $response = Http::timeout($settings->timeout)
                ->withToken($settings->api_key)
                ->get($url);
        } catch (ConnectionException $e) {
            throw new ContentGenerationException(
                'Fetching the generated image failed: '.$e->getMessage(),
                previous: $e,
            );
        }

        if (! $response->successful() || $response->body() === '') {
            throw new ContentGenerationException(
                'Fetching the generated image failed: HTTP '.$response->status().'.',
            );
        }

        if (! $this->looksLikeImage($response)) {
            Log::warning('Image generation: fetched file does not look like an image.', [
                'content_type' => $response->header('Content-Type'),
            ]);

            throw new ContentGenerationException('The fetched file is not a valid image.');
        }

        return $response->body();
    }

    protected function looksLikeImage(Response $response): bool
    {
        $contentType = (string) $response->header('Content-Type');

        if (str_starts_with($contentType, 'image/')) {
            return true;
        }

        return $this->looksLikeImageBytes($response->body());
    }

    /**
     * File-signature check for the common raster formats — used when the
     * content type is missing or generic (e.g. application/octet-stream).
     */
    protected function looksLikeImageBytes(string $bytes): bool
    {
        return str_starts_with($bytes, "\x89PNG\r\n\x1a\n")
            || str_starts_with($bytes, "\xFF\xD8\xFF")
            || str_starts_with($bytes, 'GIF87a')
            || str_starts_with($bytes, 'GIF89a')
            || (str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP');
    }

    private function logResponseShapeOnce(string $shape, string $model): void
    {
        if (self::$loggedResponseShape) {
            return;
        }

        self::$loggedResponseShape = true;

        Log::debug('Image generation: response shape observed.', [
            'model' => $model,
            'shape' => $shape,
        ]);
    }
}
