<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{

    protected $casts = [
        'detail' => 'object'
    ];

    protected $hidden = ['detail'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(Admin::class, 'processed_by_admin_id');
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class, 'method_code', 'code');
    }

    public function bookedTicket()
    {
        return $this->hasOne(BookedTicket::class, 'id', 'booked_ticket_id');
    }

    public function userDiscount()
    {
        return $this->hasOne(UserDiscount::class, 'deposit_id');
    }

    public function methodName()
    {
        if ($this->method_code < 5000) {
            $methodName = @$this->gatewayCurrency()->name;
        } else {
            $methodName = 'Google Pay';
        }
        return $methodName;
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::PAYMENT_PENDING) {
                $html = '<span class="badge badge--warning">' . trans('Pending') . '</span>';
            } elseif ($this->status == Status::PAYMENT_SUCCESS && $this->method_code >= 1000 && $this->method_code <= 5000) {
                $html = '<span><span class="badge badge--success">' . trans('Approved') . '</span><br>' . diffForHumans($this->updated_at) . '</span>';
            } elseif ($this->status == Status::PAYMENT_SUCCESS && ($this->method_code < 1000 || $this->method_code >= 5000)) {
                $html = '<span class="badge badge--success">' . trans('Succeed') . '</span>';
            } elseif ($this->status == Status::PAYMENT_REJECT) {
                $html = '<span><span class="badge badge--danger">' . trans('Rejected') . '</span><br>' . diffForHumans($this->updated_at) . '</span>';
            } elseif ($this->status == Status::PAYMENT_EXPIRED) {
                $html = '<span><span class="badge badge--danger">' . trans('Expired') . '</span><br>' . diffForHumans($this->updated_at) . '</span>';
            } else {
                $html = '<span class="badge badge--dark">' . trans('Initiated') . '</span>';
            }
            return $html;
        });
    }

    public function statusString(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::PAYMENT_PENDING) {
                $html = trans('Pending');
            } elseif ($this->status == Status::PAYMENT_SUCCESS && $this->method_code >= 1000 && $this->method_code <= 5000) {
                $html = trans('Approved');
            } elseif ($this->status == Status::PAYMENT_SUCCESS && ($this->method_code < 1000 || $this->method_code >= 5000)) {
                $html = trans('Succeed');
            } elseif ($this->status == Status::PAYMENT_REJECT) {
                $html = trans('Rejected');
            } elseif ($this->status == Status::PAYMENT_EXPIRED) {
                $html = trans('Expired');
            } else {
                $html = trans('Initiated');
            }
            return $html;
        });
    }

    // scope
    public function gatewayCurrency()
    {
        return GatewayCurrency::where('method_code', $this->method_code)->where('currency', $this->method_currency)->first();
    }

    public function baseCurrency()
    {
        return @$this->gateway->crypto == Status::ENABLE ? 'USD' : $this->method_currency;
    }

    public function scopePending($query)
    {
        return $query->where('method_code', '>=', 1000)->where('status', Status::PAYMENT_PENDING);
    }

    public function scopeRejected($query)
    {
        return $query->where('method_code', '>=', 1000)->where('status', Status::PAYMENT_REJECT);
    }

    public function scopeApproved($query)
    {
        return $query->where('method_code', '>=', 1000)->where('method_code', '<', 5000)->where('status', Status::PAYMENT_SUCCESS);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', Status::PAYMENT_SUCCESS);
    }

    public function scopeInitiated($query)
    {
        return $query->where('status', Status::PAYMENT_INITIATE);
    }


    public function scopeExpired($query)
    {
        return $query->where('status', Status::PAYMENT_EXPIRED);
    }

    public function scopePaymentSearch($query, ?string $search = null, bool $includeReference = true)
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $like = '%' . addcslashes($search, '\\%_') . '%';

        return $query->where(function ($paymentQuery) use ($like, $includeReference) {
            $paymentQuery->where('trx', 'like', $like)
                ->orWhereHas('bookedTicket', function ($ticketQuery) use ($like) {
                $ticketQuery->where('pnr_number', 'like', $like)
                    ->orWhere('passenger_manifest', 'like', $like);
            })->orWhereHas('userDiscount', function ($discountQuery) use ($like) {
                $discountQuery->where('passenger_name', 'like', $like)
                    ->orWhere('passenger_manifest', 'like', $like);
            })->orWhereHas('user', function ($userQuery) use ($like) {
                $userQuery->where('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', firstname, lastname) LIKE ?", [$like]);
            });

            if ($includeReference) {
                $paymentQuery->orWhereHas('bookedTicket.slipSeriesNumbers', function ($slipQuery) use ($like) {
                    $slipQuery->where('id', 'like', $like);
                });
            }
        });
    }

    public function scopePaymentFilters($query, array $filters = [])
    {
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $source = strtolower(trim((string) ($filters['source'] ?? '')));
        $paymentMethod = trim((string) ($filters['payment_method'] ?? ''));
        $paymentStatus = $filters['payment_status'] ?? '';
        $processedBy = trim((string) ($filters['processed_by'] ?? ''));

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($source === 'kiosk') {
            $query->whereHas('bookedTicket', fn ($ticket) => $ticket->whereNotNull('kiosk_id'));
        } elseif ($source === 'online') {
            $query->whereNotNull('user_id')
                ->whereHas('bookedTicket', fn ($ticket) => $ticket->whereNull('kiosk_id'));
        } elseif ($source === 'counter') {
            $query->whereNull('user_id')
                ->whereHas('bookedTicket', fn ($ticket) => $ticket->whereNull('kiosk_id'));
        }

        if ($paymentMethod !== '') {
            [$methodType, $methodValue] = array_pad(explode(':', $paymentMethod, 2), 2, null);

            if ($methodType === 'channel' && filled($methodValue)) {
                $query->where('pchannel', $methodValue);
            } elseif ($methodType === 'method' && is_numeric($methodValue)) {
                $query->where('method_code', (int) $methodValue);
            } elseif (is_numeric($paymentMethod)) {
                // Keep existing bookmarked/export URLs using method_code working.
                $query->where('method_code', (int) $paymentMethod);
            }
        }

        if ($paymentStatus !== '' && $paymentStatus !== null) {
            $query->where('status', (int) $paymentStatus);
        }

        if ($processedBy === 'system') {
            $query->whereNull('processed_by_admin_id');
        } elseif (str_starts_with($processedBy, 'admin:')) {
            $adminId = substr($processedBy, strlen('admin:'));
            if (ctype_digit($adminId)) {
                $query->where('processed_by_admin_id', (int) $adminId);
            }
        }

        return $query;
    }
}
