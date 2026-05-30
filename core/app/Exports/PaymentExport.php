<?php

namespace App\Exports;

use App\Models\Deposit;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PaymentExport implements FromCollection, WithHeadings, WithStrictNullComparison, ShouldAutoSize
{
    protected $results;

    public function collection()
    {
        $this->results = $this->getActionItems();

        return $this->results;
    }

    public function getActionItems()
    {
        $request = request();
        $scope = $request->status == 'all' ? 'query' : $request->status;

        if ($scope) {
            $deposits = Deposit::$scope()->with(['user', 'gateway', 'bookedTicket']);
        } else {
            $deposits = Deposit::with(['user', 'gateway', 'bookedTicket']);
        }
        $deposits = $deposits->searchable(['trx', 'user:username', 'bookedTicket:pnr_number'])->dateFilter();

        $user = $request->user('admin');

        if ($user->role?->name && str_contains(strtolower($user->role->name), 'cashier')) {
            $deposits->where('processed_by_admin_id', $user->id);
        }

        if ($request->method_code && $request->method_code != 'all') {
            $deposits->where('method_code', request('method_code'));
        }

        $deposits = $deposits->orderBy('id', 'desc')->get();

        $output = [];

        if ($request->is_template) {

        } else {
            foreach ($deposits as $deposit) {


                if ($deposit->user) {
                    $user = $deposit->user->username;
                } else {
                    $user = $deposit->bookedTicket->kiosk->name;
                }

                array_push($output, [
                    $deposit->gateway->name,
                    showDateTime($deposit->created_at),
                    $deposit->bookedTicket->pnr_number,
                    implodeSeriesNo($deposit),
                    $user,
                    $deposit->final_amount,
                    $deposit->statusString,
                ]);
            }
        }

        return collect($output);
    }

    public function headings(): array
    {
        return [
            'Gateway | Transaction',
            'Initiated',
            'PNR',
            'Reference No.',
            'User',
            'Amount',
            'Status',
        ];
    }



}