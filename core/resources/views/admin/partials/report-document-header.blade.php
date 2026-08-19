@php
    $headerFilters = collect($reportFilters ?? [])->filter(fn ($value) => filled($value));
@endphp

<header class="report-document-header">
    <div class="report-document-header__brand">
        {{ strtoupper(gs('site_name') ?: 'GV FLORIDA TRANSPORT, INC.') }}
    </div>
    <div class="report-document-header__identity">
        <h1>{{ $reportTitle }}</h1>
        <p class="report-document-header__context">
            {{ $reportDateLabel ?? 'Report date' }}: {{ $reportDate->format('l, F j, Y') }}
            @if (!empty($reportSubject))
                <span>&middot;</span> {{ $reportSubject }}
            @endif
        </p>
        @if ($headerFilters->isNotEmpty())
            <p class="report-document-header__filters">
                Filters: {{ $headerFilters->map(fn ($value, $label) => $label . ': ' . $value)->implode(' | ') }}
            </p>
        @endif
    </div>
    <div class="report-document-header__generated">
        <span>Generated</span>
        <strong>{{ now()->format('M j, Y h:i A') }}</strong>
    </div>
</header>
