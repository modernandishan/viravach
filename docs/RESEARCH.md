# Research — Cloudflare Turnstile on the public RFQ form

Scope: how to put a Turnstile widget inside a Livewire 4 single-file page
component (`resources/views/pages/⚡*.blade.php`) and verify it server-side.
Research only — nothing here is implemented yet. Verified September 2026.

---

## 1. Embedding the widget in a Livewire 4 component

### The problem

Livewire re-renders a component on every network roundtrip and **morphs** the
result into the live DOM — it diffs old against new HTML and makes surgical
changes rather than replacing the subtree
([Morphing](https://livewire.laravel.com/docs/4.x/morphing)). Turnstile's
widget is an iframe that Cloudflare's script injects and owns. Any morph that
touches that subtree can tear the iframe out and take the solved token with
it, which shows up as the widget silently disappearing, or resetting, after a
failed validation or any other `wire:model.live` update on the page.

### The fix: `wire:ignore`

`wire:ignore` tells Livewire to leave an element's contents alone even when
they change between requests. It is the documented mechanism for third-party
JS that manages its own DOM
([wire:ignore](https://livewire.laravel.com/docs/4.x/wire-ignore)).

```blade
<div wire:ignore>
    <div id="rfq-turnstile"></div>
</div>
<input type="hidden" wire:model="turnstileToken">
```

Notes on the variants:

- `wire:ignore` — ignores the element's attributes *and* children. This is
  what the Turnstile container needs.
- `wire:ignore.self` — ignores only the root element's own attributes; children
  still morph. **Not** what we want here.
- The hidden token input must sit **outside** the ignored div, otherwise
  Livewire will not pick up `wire:model` changes to it.

### Getting the token into a Livewire property

Do not rely on the automatic `response-field` hidden input Turnstile injects —
it lives inside the ignored subtree and Livewire never sees it. Use the
explicit-render `callback` to push the token across the boundary instead:

```blade
@assets
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" defer></script>
@endassets

<script>
    let widgetId = null;

    window.onloadTurnstileCallback = () => {
        widgetId = turnstile.render('#rfq-turnstile', {
            sitekey: @js(config('services.turnstile.site_key')),
            callback: (token) => $wire.set('turnstileToken', token),
            'expired-callback': () => $wire.set('turnstileToken', ''),
            'error-callback': () => $wire.set('turnstileToken', ''),
        });
    };
</script>
```

Livewire 4 specifics that matter here:

- **`@assets` runs once per page**, no matter how many component instances are
  on it, which is exactly the semantics `api.js` needs — loading Cloudflare's
  script twice double-registers the auto-render pass
  ([JavaScript](https://livewire.laravel.com/docs/4.x/javascript)).
- **A component's `<script>` runs after page load but before the component
  renders**, and re-runs each time the component enters the page via
  `wire:navigate`. So it can safely reference `$wire`, but Alpine `$refs` and
  Alpine state do not exist yet. Because the public site uses `wire:navigate`
  links, the widget must be (re-)rendered on each entry rather than only on
  first page load — this is the single biggest difference from a plain Blade
  page.
- In **single-file components** (this project's `⚡` pages), a plain `<script>`
  block in the template is already treated as a component script; the
  `@script` / `@endscript` wrapper is the class-based-component form.

### Resetting after a failed submit

Turnstile tokens are **single-use and expire after 300 seconds**
([server-side validation](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/)).
So after *any* rejected submit — Turnstile failure, Laravel validation error,
or a rate-limit hit — the old token is dead and the widget must be reset:

```js
$wire.on('rfq-submit-failed', () => {
    turnstile.reset(widgetId);
    $wire.set('turnstileToken', '');
});
```

Client-side API surface we need
([client-side rendering](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/)):
`turnstile.render(container, options)` → widget id, `turnstile.reset(id)`,
`turnstile.remove(id)`, `turnstile.getResponse(id)`.

### Known gotchas checklist

| Gotcha | Consequence | Mitigation |
| --- | --- | --- |
| Widget container not wrapped in `wire:ignore` | Widget vanishes/resets on any morph | Wrap it |
| Hidden token input inside `wire:ignore` | Token never reaches the server | Keep it outside |
| Relying on Turnstile's own `response-field` input | Same — Livewire never morphs it in | Use the `callback` + `$wire.set()` |
| Loading `api.js` in a normal `<script>` per instance | Double registration | `@assets` |
| `wire:navigate` back onto the page | Widget container is empty, no widget | Re-render in the component script (it re-runs on entry) |
| Reusing a token after a failed submit | `timeout-or-duplicate` error | `turnstile.reset()` on every failure path |
| Token older than 5 minutes (slow form fill) | `invalid-input-response` | `expired-callback` clears the property; re-solve |
| Multiple widgets on one page | Render clashes | Unique container ids; keep each widget id |

---

## 2. Server-side verification

**Endpoint** (unchanged, current):

```
POST https://challenges.cloudflare.com/turnstile/v0/siteverify
```

Accepts `application/x-www-form-urlencoded` or `application/json`; always
responds JSON.

**Parameters**

| Parameter | Required | Notes |
| --- | --- | --- |
| `secret` | yes | Widget secret key from the Cloudflare dashboard |
| `response` | yes | The token from the widget (max 2048 chars) |
| `remoteip` | no | Visitor IP. Worth sending — we already store it on `rfqs.ip_address` |
| `idempotency_key` | no | UUID, makes a retry of the *same* verification safe |

**Response fields:** `success` (bool), `challenge_ts` (ISO 8601),
`hostname`, `error-codes` (array), `action`, `cdata`, and
`metadata.ephemeral_id` (Enterprise only).

**Error codes:** `missing-input-secret`, `invalid-input-secret`,
`missing-input-response`, `invalid-input-response`, `timeout-or-duplicate`,
`internal-error`, `bad-request`.

**Token rules:** single-use, valid 300 seconds, cannot be reused after
validation. `timeout-or-duplicate` therefore means "the user needs a fresh
challenge", not "the user is a bot" — it should produce a re-solve prompt, not
a hard rejection message.

Sketch of the call (no package needed, see §3):

```php
$verification = Http::asForm()
    ->timeout(5)
    ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'secret' => config('services.turnstile.secret_key'),
        'response' => $this->turnstileToken,
        'remoteip' => request()->ip(),
    ]);

if (! $verification->json('success')) {
    // inspect $verification->json('error-codes')
}
```

Open decisions for the implementation phase:

- **Cloudflare unreachable** (`internal-error`, connection timeout): fail open
  or fail closed? Recommendation is fail *closed* with a retryable error
  message, since the RFQ form is the abuse surface we are protecting, and the
  per-IP rate limiter is the fallback if we ever flip this.
- **Where verification runs**: in the Livewire `submit()` action, before any
  validation that writes, so a bad token never touches the database.

---

## 3. Package or plain `Http::post()`?

**Recommendation: no package — a direct `Http::post()` call, as assumed.**

What is actually out there:

- [`ryangjchandler/laravel-cloudflare-turnstile`](https://github.com/ryangjchandler/laravel-cloudflare-turnstile)
  — the best-adopted option (~460 stars, active). It supports Laravel 11–13
  (`illuminate/contracts: ^11.0|^12.0|^13.0`) and gives you a `<x-turnstile>`
  Blade component, a validation rule, and Livewire support that detects
  `wire:model` and adds `wire:ignore` plus reset-on-null-property callbacks.
  Its composer.json declares **no `livewire/livewire` dependency and it is not
  tested against Livewire 4** — the Livewire integration is a
  string-sniffing Blade component, which is precisely the layer where Livewire
  4's morphing and `wire:navigate` behaviour differs from v3.
- [`laragear/turnstile`](https://packagist.org/packages/laragear/turnstile)
  — smaller, HTTP/3-focused, no specific Volt/Livewire 4 story.

Why not adopt one:

1. The server side is *one* POST to one endpoint with two required fields.
   `Http::asForm()->post()` is already the house style and is trivially fakeable
   in tests with `Http::fake()` — no new dependency, no vendor upgrade path.
2. The part that is genuinely fiddly is the Livewire 4 client side, and that is
   exactly the part no package currently guarantees for Livewire 4. Adopting a
   package here means debugging *someone else's* `wire:ignore` emission instead
   of our own eight lines.
3. Project policy (`CLAUDE.md`): dependencies are not added without approval.

Revisit only if we end up with Turnstile on several forms and want the widget
markup shared — and even then, a local Blade component in
`resources/views/components/` is the cheaper answer.

Config should live in `config/services.php` as a `turnstile` block
(`site_key`, `secret_key`) alongside the other third-party credentials.

---

## 4. Rate limiting as the complement

Cloudflare's own guidance is explicit that the widget is **not** a complete
control:

> "The client-side widget alone does not protect your forms because attackers
> can submit directly to your form endpoint."

> "Some abuse scripts skip the browser entirely and POST directly to your form
> endpoints. […] rate limiting rules catch these requests because client-side
> verification only runs in a browser."

— [Protect your forms from spam and abuse](https://developers.cloudflare.com/use-cases/solutions/protect-sensitive-forms-fraud-abuse/)

And on thresholds:

> "Before creating a rate limiting rule, check the normal submission rate for
> your form endpoints. Your rate limit threshold should be above this baseline."

— [Rate limiting best practices](https://developers.cloudflare.com/waf/rate-limiting-rules/best-practices/)

The two layers are complementary, not redundant: Turnstile raises the cost of
each individual submission; rate limiting caps volume from a source that never
loaded the widget at all. Cloudflare's WAF rate limiting sits at the edge; the
application-level `RateLimiter` we are adding also covers requests that reach
origin directly, and is the layer that can be *per company* — something the
edge cannot express.

Planned shape for the implementation phase (both, ANDed with Turnstile):

- **Per IP** — caps a single source spraying many companies.
- **Per company** — caps a single company's inbox being flooded, regardless of
  source IP; this is the one that protects the notification queue and the
  owner's SMS credits.

Both belong in the Livewire `submit()` action via `RateLimiter::tooManyAttempts()`
/ `RateLimiter::hit()`, before verification, so a flood costs us no outbound
Cloudflare calls. `rfqs.ip_address` exists to make the per-IP limiter
auditable after the fact.

---

## Sources

- [Turnstile — server-side validation](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/)
- [Turnstile — client-side rendering](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/)
- [Cloudflare — protect your forms from spam and abuse](https://developers.cloudflare.com/use-cases/solutions/protect-sensitive-forms-fraud-abuse/)
- [Cloudflare — rate limiting best practices](https://developers.cloudflare.com/waf/rate-limiting-rules/best-practices/)
- [Livewire 4 — wire:ignore](https://livewire.laravel.com/docs/4.x/wire-ignore)
- [Livewire 4 — JavaScript (`@assets`, `@script`)](https://livewire.laravel.com/docs/4.x/javascript)
- [Livewire 4 — Morphing](https://livewire.laravel.com/docs/4.x/morphing)
- [ryangjchandler/laravel-cloudflare-turnstile](https://github.com/ryangjchandler/laravel-cloudflare-turnstile)
- [Laragear/Turnstile](https://github.com/Laragear/Turnstile)
