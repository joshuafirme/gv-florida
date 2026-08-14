@php
    $reasonParts = preg_split('/;\s*/', $transaction->report_reason) ?: [];
@endphp

<div class="transaction-reason">
    @foreach ($reasonParts as $reasonPart)
        <span>{{ $reasonPart }}</span>
    @endforeach
</div>
