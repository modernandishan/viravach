{{--
    New quote request, laid out for the company owner.

    ONE view for all five locales: the strings come from lang/{locale}/rfq.php
    and the only structural difference between fa/ar and the rest is the
    reading direction, carried by $direction and the $start alias below.
    Five near-identical Blade files would have to be kept in sync by hand for
    no gain.

    Standalone, self-contained markup: the site layouts pull the Metronic
    theme's Vite assets, and mail clients neither load external stylesheets
    nor honour <style> blocks reliably, so every rule here is inline and the
    layout is table-based. Colours and radii are the site's own design tokens
    (resources/css/app.css: --vv-ink-*, --vv-primary-700), copied as literals
    because custom properties do not survive most mail clients either.
--}}
@php
    // Physical sides, not logical ones: Outlook ignores CSS logical
    // properties (padding-inline-start, border-inline-start), so RTL has to
    // be expressed as plain left/right values.
    $start = $direction === 'rtl' ? 'right' : 'left';

    // Inter is the theme's face (public/theme/1/css/style.bundle.css:
    // --bs-font-sans-serif) but is a webfont, so the stack falls through to
    // faces every client already has; Tahoma leads for Arabic script because
    // it is the one ubiquitous desktop face that shapes it correctly.
    $font = $direction === 'rtl'
        ? "Tahoma, 'Segoe UI', Arial, sans-serif"
        : "Inter, 'Segoe UI', Helvetica, Arial, sans-serif";

    // $companyName arrives from the mailable, which needs the same value
    // for the subject line and guards it against resolving to an empty string.
    $replyLanguage = config("laravellocalization.supportedLocales.{$rfq->locale}.native") ?? $rfq->locale;

    $labelStyle = "padding:12px 0;font-size:13px;color:#64748B;vertical-align:top;text-align:{$start};";
    $valueStyle = "padding:12px 0;font-size:14px;color:#0F1720;vertical-align:top;text-align:{$start};";
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ __('rfq.mail_heading') }}</title>
</head>
<body dir="{{ $direction }}" style="margin:0;padding:0;width:100%;background-color:#F6F8FA;color:#33414F;font-family:{{ $font }};">

{{-- Inbox preview line: shown next to the subject, never in the body. --}}
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#F6F8FA;">
    {{ __('rfq.mail_preheader', ['name' => $rfq->buyer_name]) }}
</div>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" dir="{{ $direction }}" style="background-color:#F6F8FA;">
    <tr>
        <td align="center" style="padding:24px 12px;">

            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="width:100%;max-width:600px;background-color:#FFFFFF;border:1px solid #E9EEF3;border-radius:12px;overflow:hidden;">
                {{-- Brand band: the primary rule the site puts above its cards. --}}
                <tr>
                    <td style="height:4px;line-height:4px;font-size:0;background-color:#0F4C81;">&nbsp;</td>
                </tr>

                <tr>
                    <td align="{{ $start }}" style="padding:24px 32px 0;">
                        <img src="{{ asset('theme/1/media/logos/ViraVach-logo-1.png') }}"
                             alt="{{ config('app.name') }}" height="26"
                             style="display:block;height:26px;width:auto;border:0;">
                    </td>
                </tr>

                <tr>
                    <td align="{{ $start }}" style="padding:20px 32px 0;">
                        <h1 style="margin:0 0 8px;font-family:{{ $font }};font-size:22px;line-height:1.35;font-weight:700;color:#0F1720;">
                            {{ __('rfq.mail_heading') }}
                        </h1>
                        <p style="margin:0;font-family:{{ $font }};font-size:14px;line-height:1.7;color:#64748B;">
                            {{ __('rfq.mail_intro', ['company' => $companyName]) }}
                        </p>
                    </td>
                </tr>

                {{-- Buyer details --}}
                <tr>
                    <td align="{{ $start }}" style="padding:24px 32px 0;">
                        <p style="margin:0 0 4px;font-family:{{ $font }};font-size:12px;font-weight:700;color:#64748B;">
                            {{ __('rfq.mail_details_heading') }}
                        </p>

                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" dir="{{ $direction }}" style="width:100%;border-collapse:collapse;font-family:{{ $font }};">
                            <tr>
                                <td width="38%" style="{{ $labelStyle }}border-bottom:1px solid #E9EEF3;">{{ __('rfq.buyer_name') }}</td>
                                <td style="{{ $valueStyle }}border-bottom:1px solid #E9EEF3;font-weight:600;">{{ $rfq->buyer_name }}</td>
                            </tr>
                            <tr>
                                <td style="{{ $labelStyle }}border-bottom:1px solid #E9EEF3;">{{ __('rfq.buyer_email') }}</td>
                                <td style="{{ $valueStyle }}border-bottom:1px solid #E9EEF3;">
                                    <a href="mailto:{{ $rfq->buyer_email }}" style="color:#0F4C81;text-decoration:none;" dir="ltr">{{ $rfq->buyer_email }}</a>
                                </td>
                            </tr>
                            @if ($rfq->buyer_phone)
                                <tr>
                                    <td style="{{ $labelStyle }}border-bottom:1px solid #E9EEF3;">{{ __('rfq.buyer_phone') }}</td>
                                    <td style="{{ $valueStyle }}border-bottom:1px solid #E9EEF3;">
                                        @if ($rfq->telNumber())
                                            <a href="tel:{{ $rfq->telNumber() }}" style="color:#0F4C81;text-decoration:none;" dir="ltr">{{ $rfq->buyer_phone }}</a>
                                        @else
                                            <span dir="ltr">{{ $rfq->buyer_phone }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            @if ($rfq->buyer_country)
                                <tr>
                                    <td style="{{ $labelStyle }}border-bottom:1px solid #E9EEF3;">{{ __('rfq.buyer_country') }}</td>
                                    <td style="{{ $valueStyle }}border-bottom:1px solid #E9EEF3;">{{ $rfq->buyer_country }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td style="{{ $labelStyle }}">{{ __('rfq.reply_language') }}</td>
                                <td style="{{ $valueStyle }}">{{ $replyLanguage }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- The buyer's own words --}}
                <tr>
                    <td align="{{ $start }}" style="padding:24px 32px 0;">
                        <p style="margin:0 0 8px;font-family:{{ $font }};font-size:12px;font-weight:700;color:#64748B;">
                            {{ __('rfq.buyer_message') }}
                        </p>
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="width:100%;background-color:#F6F8FA;border-radius:8px;border-{{ $start }}:3px solid #0F4C81;">
                            <tr>
                                <td style="padding:16px 18px;font-family:{{ $font }};font-size:14px;line-height:1.8;color:#33414F;text-align:{{ $start }};">
                                    {{-- nl2br rather than white-space:pre-line: Outlook drops the latter. --}}
                                    {!! nl2br(e($rfq->message)) !!}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Call to action: table-based so Outlook renders a real block. --}}
                <tr>
                    <td align="{{ $start }}" style="padding:28px 32px 0;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" bgcolor="#0F4C81" style="background-color:#0F4C81;border-radius:8px;">
                                    <a href="{{ $dashboardUrl }}"
                                       style="display:inline-block;padding:14px 28px;font-family:{{ $font }};font-size:14px;font-weight:700;line-height:1;color:#FFFFFF;text-decoration:none;border-radius:8px;">
                                        {{ __('rfq.mail_cta') }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:16px 0 0;font-family:{{ $font }};font-size:13px;line-height:1.7;color:#64748B;">
                            {{ __('rfq.mail_reply_hint') }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="{{ $start }}" style="padding:20px 32px 28px;">
                        <p style="margin:0 0 4px;font-family:{{ $font }};font-size:12px;line-height:1.6;color:#64748B;">
                            {{ __('rfq.mail_link_fallback') }}
                        </p>
                        <a href="{{ $dashboardUrl }}" dir="ltr" style="font-family:{{ $font }};font-size:12px;line-height:1.6;color:#0F4C81;text-decoration:underline;word-break:break-all;">{{ $dashboardUrl }}</a>
                    </td>
                </tr>
            </table>

            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="width:100%;max-width:600px;">
                <tr>
                    <td align="center" style="padding:16px 24px 0;font-family:{{ $font }};font-size:12px;line-height:1.7;color:#64748B;">
                        {{ __('rfq.mail_footer_reason', ['company' => $companyName, 'app' => config('app.name')]) }}
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>
