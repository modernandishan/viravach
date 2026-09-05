# TODO — deferred work

Items deliberately postponed. Newest entries appended at the bottom.

---

## Enamad badge performance (deferred)

The Enamad trust-seal badge in the footer loads a live JS widget from Enamad's own servers. For visitors outside Iran this is slow, sometimes throws console errors, and adds no value (Enamad is an Iranian-only verification service).

Two options were discussed, not yet decided/implemented:

1. **Self-hosted static image + direct link.** Replace the live JS widget with a static image linking to the real Enamad verification URL (`https://trustseal.enamad.ir/?id=...`) — Enamad officially supports this "static code" embed method. Removes the external script entirely for everyone, Iran or not, and needs no IP detection.
2. **GeoIP-gated live widget.** Keep the live JS widget but render it only for Iranian IPs, using the GeoIP infrastructure already in the codebase (`InteractionDesignFoundation\GeoIP`, the same package `DetectLocaleFromIp` middleware uses) — render nothing (or the static image) for everyone else.

Option 1 is the likely better fix (simpler, faster for all visitors, no geo-detection dependency) but needs a quick check in the Enamad dashboard to confirm the static-badge embed is currently valid for this account before implementing.
