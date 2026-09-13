<?php

namespace App\Mail;

use App\Models\Rfq;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

/**
 * The full quote request, laid out for the company owner.
 *
 * Rendered from a Blade view rather than notification markdown so the buyer's
 * details read as a table and the message body keeps its line breaks.
 *
 * The whole mail — subject, labels, button and reading direction — renders in
 * the BUYER's locale: $rfq->locale, the site language they were browsing when
 * they submitted the form. That is the language the owner has to answer them
 * in, so the mail arrives already speaking it, and the owner never has to
 * work out from a Persian template that the reply must go out in Russian.
 * Owners have no stored language preference of their own to prefer over it.
 *
 * The locale is pinned here, on the mailable, rather than left to the caller:
 * ->render() in a test and the queued send through App\Notifications\
 * NewRfqReceived (which pins the site default for its own SMS path) then
 * agree, whatever the ambient locale of the request or worker happens to be.
 *
 * Queueing is handled by the notification that returns this mailable
 * (App\Notifications\NewRfqReceived), so this class deliberately does NOT
 * implement ShouldQueue — that would push a second job onto the default
 * queue from inside one that is already running on 'rfq-notifications'.
 */
class NewRfqReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Rfq $rfq)
    {
        $this->locale($rfq->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('rfq.mail_subject', [
                'company' => $this->companyName(),
            ]),
        );
    }

    public function content(): Content
    {
        $locale = $this->rfq->locale;

        return new Content(
            view: 'emails.rfq.received',
            with: [
                'rfq' => $this->rfq,
                'dashboardUrl' => $this->rfq->dashboardUrl(),
                'locale' => $locale,
                'direction' => $this->directionFor($locale),
                'companyName' => $this->companyName(),
            ],
        );
    }

    /**
     * The company's name as the buyer's language knows it, never empty.
     *
     * The first read is the one the RFQ inbox page uses — the translatable
     * accessor, which resolves in this mail's own (the buyer's) locale.
     *
     * It can legitimately come back empty, and did: APP_FALLBACK_LOCALE is
     * `fa`, so spatie falls back Persian-only, and a company named just
     * `{"en": "Acme Tools"}` has nothing to offer a Russian reader. Without
     * the guard below the subject shipped as "Новый запрос цены для " with
     * the name simply missing. Any real name beats a blank, so the last
     * resort is whatever translation the company does have — getTranslations()
     * has already dropped the null and empty ones.
     */
    private function companyName(): string
    {
        $company = $this->rfq->company;

        if ($company === null) {
            return '';
        }

        $name = trim((string) $company->name);

        return $name !== ''
            ? $name
            : trim((string) Arr::first($company->getTranslations('name'), default: ''));
    }

    /**
     * Reading direction of the locale this mailable renders in.
     *
     * Derived from the locale's script in config/laravellocalization.php
     * rather than from LaravelLocalization::getCurrentLocaleDirection(),
     * which answers for the *current request* — a queue worker has no
     * request, and a worker's ambient locale is not the buyer's.
     */
    private function directionFor(string $locale): string
    {
        $script = config("laravellocalization.supportedLocales.{$locale}.script");

        return in_array($script, ['Arab', 'Hebr', 'Thaa'], true) ? 'rtl' : 'ltr';
    }
}
