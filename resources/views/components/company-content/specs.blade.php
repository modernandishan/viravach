@props(['data' => []])

@if (! empty($data))
    <div class="card mb-6 mb-xl-9">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>{{ __('companies.content_specs') }}</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle gy-3">
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td class="text-gray-600 fw-semibold w-250px">{{ $item['label'] ?? '' }}</td>
                                <td class="text-gray-800">{{ $item['value'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
