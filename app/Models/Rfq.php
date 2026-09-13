<?php

namespace App\Models;

use App\Enums\RfqStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

#[Fillable([
    'company_id',
    'buyer_name',
    'buyer_email',
    'buyer_phone',
    'buyer_country',
    'message',
    'locale',
    'status',
    'ip_address',
])]
class Rfq extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => RfqStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Unused — the in-platform reply was removed; see RfqResponse's note. */
    public function responses(): HasMany
    {
        return $this->hasMany(RfqResponse::class);
    }

    /**
     * Everyone on the company's side who should hear about this request.
     *
     * A company has exactly one owner today — companies.user_id, the same
     * column CompanySubscriptionService and CompanyChatService treat as the
     * owner — but notifications are addressed through this method rather than
     * reaching for ->company->user at each call site, so widening it to a
     * team later is a one-file change.
     *
     * @return Collection<int, User>
     */
    public function recipients(): Collection
    {
        $owner = $this->company?->user;

        return $owner ? new Collection([$owner]) : new Collection;
    }

    /**
     * Where the owner reads and answers this request.
     *
     * The dedicated dashboard page does not exist yet; until its route is
     * registered, links resolve to the dashboard root so notifications that
     * are already queued (or already delivered) never point at a 404.
     */
    public function dashboardUrl(): string
    {
        return Route::has('rfqs')
            ? route('rfqs', ['rfq' => $this->getKey()])
            : route('dashboard');
    }

    /**
     * RESERVED, like RfqStatus::Responded itself — nothing calls this today.
     *
     * It was driven by the in-platform reply, which no longer exists; with
     * replies happening over email or phone, the app has no moment at which
     * it could honestly claim the buyer was answered. Kept with the enum case
     * so a future version that does know (a logged buyer-facing send) has the
     * transition already defined.
     */
    public function markResponded(): void
    {
        if ($this->status !== RfqStatus::Responded) {
            $this->update(['status' => RfqStatus::Responded]);
        }
    }

    /** The buyer's phone with layout whitespace removed, for a tel: href. */
    public function telNumber(): ?string
    {
        $phone = preg_replace('/\s+/', '', (string) $this->buyer_phone);

        return $phone === '' ? null : $phone;
    }

    /**
     * The buyer's number in the digits-only form wa.me expects, or null when
     * it cannot be derived safely.
     *
     * OtpService::toE164() and IPPanelChannel::toE164() both turn a bare
     * leading 0 into +98, because every number they touch is a domestic
     * Iranian one. That assumption does not hold for an RFQ: the buyer is an
     * international visitor by definition, and a London number written
     * "020 7946 0018" would normalise to +9820..., which is a real number
     * belonging to somebody else. Opening a WhatsApp chat with a stranger is
     * worse than offering no button, so the Iranian default is deliberately
     * not reused here.
     *
     * Only a number the buyer already wrote in international form (+ or 00)
     * yields a link; anything else falls back to the plain tel: link, which
     * the visitor's own dialler resolves correctly.
     */
    public function whatsappNumber(): ?string
    {
        $phone = trim((string) $this->buyer_phone);

        if (str_starts_with($phone, '+')) {
            $digits = (string) preg_replace('/\D/', '', $phone);
        } elseif (str_starts_with($phone, '00')) {
            $digits = substr((string) preg_replace('/\D/', '', $phone), 2);
        } else {
            return null;
        }

        // A country code plus the shortest realistic subscriber number; below
        // this the input is a typo, not a reachable handset.
        return strlen($digits) >= 8 ? $digits : null;
    }
}
