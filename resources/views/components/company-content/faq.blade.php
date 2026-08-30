@props(['data' => []])

@if (! empty($data))
    <div class="card mb-6 mb-xl-9">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>{{ __('companies.content_faq') }}</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="accordion accordion-icon-toggle" id="companyFaqAccordion">
                @foreach ($data as $index => $item)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="companyFaqHeading{{ $index }}">
                            <button
                                class="accordion-button fs-4 fw-semibold {{ $index === 0 ? '' : 'collapsed' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#companyFaqBody{{ $index }}"
                                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                            >
                                {{ $item['q'] ?? '' }}
                            </button>
                        </h2>
                        <div
                            id="companyFaqBody{{ $index }}"
                            class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                            aria-labelledby="companyFaqHeading{{ $index }}"
                            data-bs-parent="#companyFaqAccordion"
                        >
                            <div class="accordion-body fs-6 text-gray-700">
                                @php
                                    $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', (string) ($item['a'] ?? ''))), fn (string $p): bool => $p !== ''));
                                @endphp
                                @foreach ($paragraphs as $paragraph)
                                    <p class="mb-0 mb-1 last-mb-0">{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
