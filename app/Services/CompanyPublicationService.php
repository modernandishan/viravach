<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyPublication;
use App\Models\SeoMeta;
use App\Support\DashboardWidgetCache;
use Illuminate\Support\Facades\DB;

class CompanyPublicationService
{
    /**
     * Upsert the public snapshot for the given company from its current
     * draft data, replacing the publication's media with fresh copies so
     * the snapshot's files stay independent of the source company.
     */
    public function publish(Company $company): CompanyPublication
    {
        $company->loadMissing(['categories', 'primaryAddress', 'exportCountries', 'seo', 'media']);

        return DB::transaction(function () use ($company): CompanyPublication {
            // Resolved inside the closure, not outside it: a variable declared
            // in the enclosing scope is NOT visible here unless it is named in
            // `use`, and omitting it made every approve/republish throw
            // "Undefined variable $address". The relation is already loaded by
            // the loadMissing() above, so this costs nothing. A company with
            // no primary address is normal — every field below stays null.
            $address = $company->primaryAddress;

            $publication = CompanyPublication::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'slug' => $company->slug,
                    'name' => $company->getTranslations('name'),
                    'legal_name' => $company->getTranslations('legal_name'),
                    'legal_type' => $company->legal_type,
                    'registration_number' => $company->registration_number,
                    'national_id' => $company->national_id,
                    'established_at' => $company->established_at,
                    // description is dead: never read or written any more —
                    // the AI-generated content payload (content) replaced it.
                    // The column itself is dropped by a later migration.
                    'summary' => $company->getTranslations('summary'),
                    'content' => $company->content,
                    'website' => $company->website,
                    'email' => $company->email,
                    'phones' => $company->phones,
                    'social_links' => $company->social_links,
                    'is_verified' => $company->is_verified,
                    'is_featured' => $company->is_featured,
                    'employee_range' => $company->employee_range,
                    // Primary-address snapshot. Copied rather than read
                    // through at render time, so an unapproved address edit
                    // cannot reach the public page or ViraBot's context.
                    'state_id' => $address?->state_id,
                    'country_id' => $address?->country_id,
                    'city_id' => $address?->city_id,
                    'address_line' => $address?->getTranslations('address_line') ?: null,
                    'postal_code' => $address?->postal_code,
                    'latitude' => $address?->latitude,
                    'longitude' => $address?->longitude,
                    'published_at' => now(),
                ],
            );

            $publication->categories()->sync($company->categories->pluck('id'));

            // Same reason as the address: previously read through to the live
            // draft on both the public page and in ViraBot's context.
            $publication->exportCountries()->sync($company->exportCountries->pluck('id'));

            $this->copySeoMeta($company, $publication);
            $this->copyMedia($company, $publication);

            // Publishing changes what the owner's dashboard widgets show
            // (view-derived widgets gain a page, the subscriptions widget can
            // gain a company). Clear their cached payloads so the dashboard is
            // never stale immediately after an approval.
            DashboardWidgetCache::forgetForUser($company->user_id);

            return $publication;
        });
    }

    /**
     * Snapshot the company's SEO meta so admin edits to the draft's SEO do
     * not leak to the public page before the next approval.
     */
    private function copySeoMeta(Company $company, CompanyPublication $publication): void
    {
        $seo = $company->seo;

        if ($seo === null) {
            return;
        }

        // only()/getAttribute would collapse translatable attributes to the
        // current locale's string, so pull the full translation arrays.
        $attributes = collect((new SeoMeta)->getFillable())
            ->mapWithKeys(fn (string $key): array => [
                $key => $seo->isTranslatableAttribute($key)
                    ? $seo->getTranslations($key)
                    : $seo->getAttribute($key),
            ])
            ->all();

        $publication->seo()->updateOrCreate([], $attributes);
    }

    /**
     * Clear each public collection on the publication, then re-copy from the
     * company so images removed from the draft disappear on re-approval.
     */
    private function copyMedia(Company $company, CompanyPublication $publication): void
    {
        foreach ($publication->getRegisteredMediaCollections()->pluck('name') as $collection) {
            $publication->clearMediaCollection($collection);

            foreach ($company->getMedia($collection) as $media) {
                $media->copy($publication, $collection, 's3');
            }
        }
    }
}
