<?php

namespace App\Ai\Input;

use App\Models\Company;
use App\Models\CompanyCategory;

/**
 * Assembles the deterministic, content-only input payload that AI content
 * generation is driven from, and digests it into a hash used to detect
 * staleness. Pure computation: no network, no persistence, no AI calls.
 *
 * The payload deliberately excludes everything that changes without the
 * user editing generation-relevant content — ids, timestamps, review state,
 * view counts, media — so the hash only moves when the user actually
 * changes the input. The website is excluded as a source of *fetched* text
 * (it is added later as separate context, outside this payload/hash), so an
 * edit on the user's own site never invalidates a generated payload.
 */
class CompanyInputCollector
{
    /**
     * @return array<string, mixed>
     */
    public function collect(Company $company): array
    {
        $company->loadMissing([
            'categories.ancestors',
            'exportCountries',
            'brands',
            'primaryAddress.state',
            'primaryAddress.city',
        ]);

        $locale = $company->brief_locale ?: (string) config('app.fallback_locale');

        $input = [
            'name' => $company->getTranslation('name', $locale),
            'brief' => $company->brief,
            'brief_locale' => $locale,
            'legal_type' => $company->legal_type?->value,
            'established_at' => $company->established_at?->format('Y-m-d'),
            'employee_range' => $company->employee_range,
            'website' => $company->website,
            'categories' => $this->categoryPaths($company, $locale),
            'state' => $company->primaryAddress?->state?->getTranslation('name', $locale),
            'export_countries' => $company->exportCountries
                ->map(fn ($country) => $country->getTranslation('name', $locale))
                ->all(),
            'brands' => $company->brands
                ->map(fn ($brand) => $brand->getTranslation('name', $locale))
                ->all(),
        ];

        if (($address = $company->primaryAddress) !== null && $address->isRelation('city')) {
            $input['city'] = $address->city?->getTranslation('name', $locale);
        }

        // sort() so attach order never leaks into the payload or the hash.
        sort($input['categories']);
        sort($input['export_countries']);
        sort($input['brands']);

        return array_filter($input, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * sha256 over a canonically ordered encoding: ksort-ing recursively
     * means the digest depends only on the content, never on the order the
     * keys or entries happened to be assembled in — two structurally equal
     * payloads must always produce the same hash.
     */
    public function hash(array $input): string
    {
        return hash('sha256', (string) json_encode($this->ksortRecursive($input), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Full "Root > Branch > Leaf" path per attached category, in the given
     * locale. The adjacency-list CTE exposes a depth attribute on ancestor
     * results; sorting on it explicitly guarantees a root-first path
     * regardless of the CTE's result order.
     *
     * @return list<string>
     */
    private function categoryPaths(Company $company, string $locale): array
    {
        return $company->categories
            ->map(function (CompanyCategory $category) use ($locale): string {
                $titles = $category->ancestors
                    ->sortBy('depth')
                    ->map(fn (CompanyCategory $ancestor) => $ancestor->getTranslation('title', $locale))
                    ->push($category->getTranslation('title', $locale));

                return implode(' > ', $titles->all());
            })
            ->filter(fn (string $path) => $path !== '')
            ->values()
            ->all();
    }

    private function ksortRecursive(array $array): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = $this->ksortRecursive($value);
            }
        }

        ksort($array);

        return $array;
    }
}
