@props(['data' => []])

@php
    $paragraphs = array_values(array_filter(
        array_map('trim', preg_split('/\R{2,}/u', (string) ($data['body'] ?? ''))),
        fn (string $paragraph): bool => $paragraph !== '',
    ));
@endphp

@if (! empty($data['heading']) || $paragraphs !== [])
    <div class="card mb-6 mb-xl-9">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>{{ $data['heading'] ?? __('companies.profile_about') }}</h2>
            </div>
        </div>
        <div class="card-body pt-0 fs-6 text-gray-700">
            @foreach ($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </div>
@endif
