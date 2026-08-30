@props(['data' => []])

@if (! empty($data['headline']) || ! empty($data['subheadline']))
    <div class="card mb-6 mb-xl-9">
        <div class="card-body pt-6 text-center">
            <h2 class="text-gray-800 fs-1 fw-bold mb-3">{{ $data['headline'] ?? '' }}</h2>
            @if (! empty($data['subheadline']))
                <p class="text-gray-600 fs-5 fw-semibold mb-0">{{ $data['subheadline'] }}</p>
            @endif
        </div>
    </div>
@endif
