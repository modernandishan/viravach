@props(['data' => []])

@if (! empty($data['heading']) || ! empty($data['body']) || ! empty($data['countries']))
    <div class="card mb-6 mb-xl-9">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>{{ $data['heading'] ?? __('companies.content_markets') }}</h2>
            </div>
        </div>
        <div class="card-body pt-0 fs-6 text-gray-700">
            @php
                $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', (string) ($data['body'] ?? ''))), fn (string $p): bool => $p !== ''));
            @endphp
            @foreach ($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach

            @if (! empty($data['countries']))
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @foreach ($data['countries'] as $code)
                        <span class="badge badge-light-primary fw-bold">{{ $code }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
