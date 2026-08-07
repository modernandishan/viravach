<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire attaches "X-Socket-ID: window.Echo.socketId()" to every request
 * whenever window.Echo exists — but socketId() is undefined until the
 * WebSocket actually connects, and fetch serializes that to the literal
 * string "undefined". musonza's broadcast(...)->toOthers() then hands it to
 * pusher-php, whose socket-id validation throws "Invalid socket ID
 * undefined" in the queue worker and kills the broadcast job entirely.
 *
 * Dropping a malformed header instead means toOthers() simply has no socket
 * to exclude: the event is delivered to every subscriber, including the
 * sender's own tab, whose handlers are idempotent (they fetch by
 * last-known-id), so the graceful degradation is harmless.
 */
class DiscardInvalidBroadcastSocketId
{
    /**
     * Pusher-protocol socket ids are always "<digits>.<digits>".
     */
    protected const SOCKET_ID_PATTERN = '/^\d+\.\d+$/';

    public function handle(Request $request, Closure $next): Response
    {
        $socketId = $request->header('X-Socket-ID');

        if ($socketId !== null && ! preg_match(self::SOCKET_ID_PATTERN, $socketId)) {
            $request->headers->remove('X-Socket-ID');
        }

        return $next($request);
    }
}
