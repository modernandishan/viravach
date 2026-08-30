@props(['data' => []])

@if (! empty($data['heading']) || ! empty($data['body']))
    <div class="card mb-6 mb-xl-9">
        <div class="card-body text-center py-9">
            <h2 class="text-gray-800 fs-2 fw-bold mb-3">{{ $data['heading'] ?? '' }}</h2>
            @php
                $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', (string) ($data['body'] ?? ''))), fn (string $p): bool => $p !== ''));
            @endphp
            @foreach ($paragraphs as $paragraph)
                <p class="text-gray-600 fs-5 fw-semibold mb-0 mb-1 last-mb-0">{{ $paragraph }}</p>
            @endforeach
        </div>
    </div>
@endif
