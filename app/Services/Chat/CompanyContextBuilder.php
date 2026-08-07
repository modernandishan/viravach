<?php

namespace App\Services\Chat;

use App\Models\CompanyPublication;
use Illuminate\Support\Str;

class CompanyContextBuilder
{
    /**
     * Build a concise plain-text summary of a company for ViraBot's system
     * prompt ({context} placeholder), read from the PUBLISHED snapshot in the
     * current app locale — never from the live draft. This string is inserted
     * into the system prompt on EVERY AI call in a company-scoped
     * conversation, so it must stay short. Returns an empty string when the
     * company has no active publication.
     */
    public function build(int $companyId): string
    {
        $publication = CompanyPublication::query()
            ->active()
            ->where('company_id', $companyId)
            ->with(['categories', 'state', 'company.exportCountries'])
            ->first();

        if (! $publication) {
            return '';
        }

        $lines = [
            'The user is currently viewing the public profile of the following company. Base company-specific answers ONLY on these verified facts from its Viravach profile:',
            'Company name: '.$publication->name,
        ];

        $categories = $publication->categories->pluck('title')->filter()->implode(', ');

        if ($categories !== '') {
            $lines[] = 'Categories: '.$categories;
        }

        if ($publication->state) {
            $lines[] = 'Location: '.$publication->state->name;
        }

        $about = trim((string) $publication->summary) ?: trim(strip_tags((string) $publication->description));

        if ($about !== '') {
            $lines[] = 'About: '.Str::limit($about, 350);
        }

        if ($publication->website) {
            $lines[] = 'Website: '.$publication->website;
        }

        if ($publication->email) {
            $lines[] = 'Email: '.$publication->email;
        }

        if ($publication->phones) {
            $lines[] = 'Phones: '.implode(', ', $publication->phones);
        }

        $exportCountries = $publication->company?->exportCountries->pluck('name')->filter()->implode(', ') ?? '';

        if ($exportCountries !== '') {
            $lines[] = 'Export countries: '.$exportCountries;
        }

        return implode("\n", $lines);
    }
}
