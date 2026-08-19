@extends('admin.layouts.app')

@section('panel')
    <div class="developer-page">
        <section class="developer-summary" aria-label="Payment transaction summary">
            <div>
                <span>Total Transactions</span>
                <strong>{{ number_format($totalTransactions) }}</strong>
            </div>
            <i class="las la-money-check-alt" aria-hidden="true"></i>
        </section>

        <section class="developer-filters" aria-label="Payment transaction filters">
            <form action="{{ route('admin.developer.payment.transactions') }}" method="GET">
                <div class="developer-search">
                    <i class="las la-search" aria-hidden="true"></i>
                    <input type="search" name="search" value="{{ request('search') }}"
                        placeholder="Search transaction, reference, PNR, channel, or customer"
                        aria-label="Search payment transactions">
                </div>

                <select name="status" class="form-control" aria-label="Filter by payment status">
                    <option value="">All Statuses</option>
                    @foreach ($paymentStatuses as $label => $value)
                        <option value="{{ $label }}" @selected(request('status') === $label)>{{ ucfirst($label) }}</option>
                    @endforeach
                </select>

                <div class="developer-date">
                    <label for="paymentDateFrom">From</label>
                    <input id="paymentDateFrom" type="date" name="date_from" class="form-control"
                        value="{{ request('date_from') }}">
                </div>

                <div class="developer-date">
                    <label for="paymentDateTo">To</label>
                    <input id="paymentDateTo" type="date" name="date_to" class="form-control"
                        value="{{ request('date_to') }}">
                </div>

                <button class="btn btn--primary" type="submit">
                    <i class="las la-filter" aria-hidden="true"></i> Filter
                </button>
                <a class="btn btn--light" href="{{ route('admin.developer.payment.transactions') }}">Clear</a>
                <a class="btn btn--success" href="{{ route('admin.developer.payment.transactions.export', request()->except('page')) }}">
                    <i class="las la-file-export" aria-hidden="true"></i> Export
                </a>
            </form>
        </section>

        <section class="developer-table-card">
            <div class="table-responsive">
                <table class="table table--light style--two developer-table developer-transaction-table">
                    <thead>
                        <tr>
                            <th>Transaction</th>
                            <th>Description &amp; Time</th>
                            <th>Dates Info</th>
                            <th class="text-end">Amount &amp; Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            @php
                                $status = match ((int) $transaction->status) {
                                    \App\Constants\Status::PAYMENT_SUCCESS => ['success', 'Success', 'Transaction is approved'],
                                    \App\Constants\Status::PAYMENT_PENDING => ['warning', 'Pending', 'Transaction is pending'],
                                    \App\Constants\Status::PAYMENT_REJECT => ['danger', 'Rejected', 'Transaction was rejected'],
                                    \App\Constants\Status::PAYMENT_EXPIRED => ['danger', 'Expired', 'Transaction has expired'],
                                    default => ['dark', 'Initiated', 'Transaction was initiated'],
                                };
                                $expiry = $transaction->expiry_limit
                                    ? rescue(fn () => \Carbon\Carbon::parse($transaction->expiry_limit), null, false)
                                    : null;
                            @endphp
                            <tr>
                                <td data-label="Transaction">
                                    <strong class="developer-transaction-id">
                                        <i class="las la-fingerprint" aria-hidden="true"></i>
                                        {{ $transaction->trx ?: 'N/A' }}
                                    </strong>
                                    <small>Orig: {{ $transaction->pay_reference ?: 'N/A' }}</small>
                                    @if ($transaction->bookedTicket?->pnr_number)
                                        <small>PNR: {{ $transaction->bookedTicket->pnr_number }}</small>
                                    @endif
                                </td>
                                <td data-label="Description & Time">
                                    <strong>{{ $status[2] }}</strong>
                                    <small>
                                        {{ $transaction->pmethod ? ucwords(str_replace('_', ' ', $transaction->pmethod)) : 'Paynamics' }}
                                        @if ($transaction->pchannel)
                                            &middot; {{ $channelNames->get($transaction->pchannel, $transaction->pchannel) }}
                                        @endif
                                    </small>
                                    <small><i class="las la-clock" aria-hidden="true"></i> {{ $transaction->created_at?->format('M d, Y h:i A') ?: 'N/A' }}</small>
                                </td>
                                <td data-label="Dates Info">
                                    <small>Created: <strong>{{ $transaction->created_at?->format('M d, Y') ?: 'N/A' }}</strong></small>
                                    <small>Last Tx: <strong>{{ $transaction->updated_at?->format('M d, Y') ?: 'N/A' }}</strong></small>
                                    <small>Expires: <strong>{{ $expiry?->format('M d, Y h:i A') ?: 'N/A' }}</strong></small>
                                </td>
                                <td data-label="Amount & Status" class="text-end">
                                    <strong class="developer-amount">{{ showAmount($transaction->final_amount) }}</strong>
                                    <div class="developer-status-actions">
                                        <span class="badge badge--{{ $status[0] }}">{{ $status[1] }}</span>
                                        <button type="button" class="developer-details-button" data-bs-toggle="collapse"
                                            data-bs-target="#transactionDetails{{ $transaction->id }}"
                                            aria-controls="transactionDetails{{ $transaction->id }}" aria-expanded="false"
                                            title="View transaction details">
                                            <i class="las la-code" aria-hidden="true"></i>
                                            <span class="visually-hidden">View transaction details</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="developer-detail-row">
                                <td colspan="4" class="p-0 border-0">
                                    <div class="collapse" id="transactionDetails{{ $transaction->id }}">
                                        <dl class="developer-detail-grid">
                                            <div><dt>Database ID</dt><dd>{{ $transaction->id }}</dd></div>
                                            <div><dt>Gateway</dt><dd>{{ $transaction->gateway?->name ?: 'Paynamics' }}</dd></div>
                                            <div><dt>Method Code</dt><dd>{{ $transaction->method_code }}</dd></div>
                                            <div><dt>Currency</dt><dd>{{ $transaction->method_currency ?: 'PHP' }}</dd></div>
                                            <div><dt>Payment Method</dt><dd>{{ $transaction->pmethod ?: 'N/A' }}</dd></div>
                                            <div><dt>Payment Channel</dt><dd>{{ $transaction->pchannel ?: 'N/A' }}</dd></div>
                                            <div><dt>Pay Reference</dt><dd>{{ $transaction->pay_reference ?: 'N/A' }}</dd></div>
                                            <div><dt>PNR</dt><dd>{{ $transaction->bookedTicket?->pnr_number ?: 'N/A' }}</dd></div>
                                        </dl>
                                        <div class="developer-payment-payloads">
                                            <section>
                                                <h4><i class="las la-paper-plane" aria-hidden="true"></i> JSON Request Payload</h4>
                                                @if ($transaction->request_payload !== null)
                                                    <pre>{{ is_string($transaction->request_payload) ? $transaction->request_payload : json_encode($transaction->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                @else
                                                    <div class="developer-json-empty">No stored request payload is available for this transaction.</div>
                                                @endif
                                            </section>
                                            <section>
                                                <h4><i class="las la-reply" aria-hidden="true"></i> Response JSON</h4>
                                                @if ($transaction->response_payload !== null)
                                                    <pre>{{ is_string($transaction->response_payload) ? $transaction->response_payload : json_encode($transaction->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                @else
                                                    <div class="developer-json-empty">No stored response JSON is available for this transaction.</div>
                                                @endif
                                            </section>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="las la-receipt developer-empty-icon" aria-hidden="true"></i>
                                    <span>No Paynamics transactions match the selected filters.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="developer-pagination">{{ paginateLinks($transactions) }}</div>
            @endif
        </section>
    </div>
@endsection

@push('style')
    @include('admin.developer.partials.styles')
@endpush
