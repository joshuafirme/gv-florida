@extends('admin.layouts.app')

@section('panel')
    <div class="developer-page">
        <section class="developer-summary" aria-label="Webhook summary">
            <div>
                <span>Total Webhooks</span>
                <strong>{{ number_format($totalWebhooks) }}</strong>
            </div>
            <i class="las la-project-diagram" aria-hidden="true"></i>
        </section>

        <section class="developer-filters developer-filters--webhooks" aria-label="Webhook filters">
            <form action="{{ route('admin.developer.webhook.logs') }}" method="GET">
                <div class="developer-search">
                    <i class="las la-search" aria-hidden="true"></i>
                    <input type="search" name="search" value="{{ request('search') }}"
                        placeholder="Search request ID, transaction, event, payload, or error"
                        aria-label="Search webhook logs">
                </div>

                <select name="provider" class="form-control" aria-label="Filter by provider">
                    <option value="">All Providers</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider }}" @selected(request('provider') === $provider)>{{ $provider }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-control" aria-label="Filter by processing status">
                    <option value="">All Statuses</option>
                    @foreach ($webhookStatuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>

                <div class="developer-date">
                    <label for="webhookDateFrom">From</label>
                    <input id="webhookDateFrom" type="date" name="date_from" class="form-control"
                        value="{{ request('date_from') }}">
                </div>

                <div class="developer-date">
                    <label for="webhookDateTo">To</label>
                    <input id="webhookDateTo" type="date" name="date_to" class="form-control"
                        value="{{ request('date_to') }}">
                </div>

                <button class="btn btn--primary" type="submit" title="Apply filters">
                    <i class="las la-filter" aria-hidden="true"></i> Filter
                </button>
                <a class="btn btn--light" href="{{ route('admin.developer.webhook.logs') }}">Clear</a>
            </form>
        </section>

        <section class="developer-table-card">
            <div class="table-responsive">
                <table class="table table--light style--two developer-table developer-webhook-table">
                    <thead>
                        <tr>
                            <th>Received At</th>
                            <th>Provider</th>
                            <th>Event / Type</th>
                            <th>Origtrxid / Request ID</th>
                            <th>Status</th>
                            <th>HTTP</th>
                            <th>Transaction</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($webhooks as $webhook)
                            @php
                                $statusClass = match ($webhook->status) {
                                    'processed' => 'success',
                                    'failed' => 'danger',
                                    default => 'warning',
                                };
                                $httpClass = ($webhook->http_status ?? 0) >= 400 ? 'danger' : (($webhook->http_status ?? 0) >= 200 ? 'success' : 'warning');
                            @endphp
                            <tr>
                                <td data-label="Received At">
                                    <strong>{{ $webhook->received_at?->format('M d, Y') ?: 'N/A' }}</strong>
                                    <small>{{ $webhook->received_at?->format('h:i:s A') ?: 'N/A' }}</small>
                                </td>
                                <td data-label="Provider"><span class="badge badge--primary">{{ $webhook->provider }}</span></td>
                                <td data-label="Event / Type"><strong>{{ $webhook->event_type ?: 'N/A' }}</strong></td>
                                <td data-label="Origtrxid / Request ID" class="developer-identifiers">
                                    <small>Orig: <strong>{{ $webhook->original_transaction_id ?: 'N/A' }}</strong></small>
                                    <small>Request: <strong>{{ $webhook->request_id ?: 'N/A' }}</strong></small>
                                </td>
                                <td data-label="Status"><span class="badge badge--{{ $statusClass }}">{{ ucfirst($webhook->status) }}</span></td>
                                <td data-label="HTTP"><span class="badge badge--{{ $httpClass }}">{{ $webhook->http_status ?: 'N/A' }}</span></td>
                                <td data-label="Transaction">
                                    @if ($webhook->deposit)
                                        <strong>{{ $webhook->deposit->trx }}</strong>
                                        <small>{{ $webhook->deposit->bookedTicket?->pnr_number ?: 'Linked payment' }}</small>
                                    @else
                                        <span class="text-muted"><em>Not linked</em></span>
                                    @endif
                                </td>
                                <td data-label="Details" class="text-end">
                                    <button type="button" class="developer-details-button" data-bs-toggle="collapse"
                                        data-bs-target="#webhookDetails{{ $webhook->id }}"
                                        aria-controls="webhookDetails{{ $webhook->id }}" aria-expanded="false"
                                        title="View complete webhook details">
                                        <i class="las la-code" aria-hidden="true"></i>
                                        <span class="visually-hidden">View complete webhook details</span>
                                    </button>
                                </td>
                            </tr>
                            <tr class="developer-detail-row">
                                <td colspan="8" class="p-0 border-0">
                                    <div class="collapse" id="webhookDetails{{ $webhook->id }}">
                                        <div class="developer-webhook-details">
                                            <section>
                                                <h4><i class="las la-external-link-alt" aria-hidden="true"></i> Payload</h4>
                                                <pre>{{ json_encode($webhook->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </section>
                                            <section>
                                                <h4>Response</h4>
                                                <pre>{{ json_encode($webhook->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </section>
                                            @if ($webhook->error_message)
                                                <section class="developer-webhook-error">
                                                    <h4><i class="las la-exclamation-triangle" aria-hidden="true"></i> Error Message</h4>
                                                    <pre>{{ $webhook->error_message }}</pre>
                                                </section>
                                            @endif
                                            <section class="developer-webhook-headers">
                                                <h4>Request Metadata</h4>
                                                <pre>{{ json_encode(['ip_address' => $webhook->ip_address, 'headers' => $webhook->headers], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </section>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="las la-project-diagram developer-empty-icon" aria-hidden="true"></i>
                                    <span>No webhook logs match the selected filters.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($webhooks->hasPages())
                <div class="developer-pagination">{{ paginateLinks($webhooks) }}</div>
            @endif
        </section>
    </div>
@endsection

@push('style')
    @include('admin.developer.partials.styles')
@endpush
