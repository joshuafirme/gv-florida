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
        $scope = request()->status;

        if ($scope) {
            $deposits = Deposit::$scope()->with(['user', 'gateway', 'bookedTicket']);
        } else {
            $deposits = Deposit::with(['user', 'gateway', 'bookedTicket']);
        }
        $deposits = $deposits->searchable(['trx', 'user:username', 'bookedTicket:pnr_number'])->dateFilter();
        $deposits = $deposits->orderBy('id', 'desc')->paginate(getPaginate());

        $output = [];

        if (request()->is_template) {

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
                    $user,
                    showAmount($deposit->final_amount),
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
            'User',
            'Amount',
            'Status',
        ];
    }



}