@props(['data' => []])

@if (! empty($data))
    <div class="card mb-6 mb-xl-9">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>{{ __('companies.content_offerings') }}</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="row g-4">
                @foreach ($data as $item)
                    <div class="col-md-6">
                        <div class="bg-light rounded p-5 h-100">
                            <div class="fw-bold text-gray-800 fs-5 mb-2">{{ $item['title'] ?? '' }}</div>
                            @php
                                $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', (string) ($item['body'] ?? ''))), fn (string $p): bool => $p !== ''));
                            @endphp
                            @foreach ($paragraphs as $paragraph)
                                <p class="text-gray-600 mb-0 mb-1 last-mb-0">{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
