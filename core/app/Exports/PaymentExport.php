<?php

namespace App\Exports;

use App\Constants\Status;
use App\Models\Deposit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentExport implements FromCollection, WithHeadings, WithStrictNullComparison, ShouldAutoSize, WithStyles
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

        $relations = [
            'user',
            'gateway',
            'userDiscount',
            'bookedTicket.kiosk',
            'bookedTicket.pickup:id,name,km_post',
            'bookedTicket.drop:id,name,km_post',
            'bookedTicket.trip.schedule',
            'bookedTicket.slipSeriesNumbers',
        ];

        if ($scope) {
            $deposits = Deposit::$scope()->with($relations);
        } else {
            $deposits = Deposit::with($relations);
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
                    $this->formatReferenceNumbers($deposit),
                    formatDate($deposit->bookedTicket->date_of_journey, false, 'd-M-y'),
                    date('h:i A', strtotime($deposit->bookedTicket->trip?->schedule?->start_from)),
                    $deposit->bookedTicket?->drop?->km_post,
                    $user,
                    $deposit->final_amount,
                    $deposit->userDiscount?->description,
                    $deposit->userDiscount?->passenger_name,
                    $deposit->userDiscount?->id_number,
                    $deposit->statusString,
                ]);
            }
        }

        return collect($output);
    }

    private function formatReferenceNumbers(Deposit $deposit): ?string
    {
        if ($deposit->status != Status::PAYMENT_SUCCESS) {
            return null;
        }

        if (!$deposit->bookedTicket?->slipSeriesNumbers) {
            return null;
        }

        return $deposit->bookedTicket->slipSeriesNumbers
            ->pluck('id')
            ->chunk(3)
            ->map(fn ($seriesNumbers) => $seriesNumbers->implode(', '))
            ->implode("\n");
    }

    public function headings(): array
    {
        return [
            'Gateway | Transaction',
            'Initiated',
            'PNR',
            'Reference No.',
            'Travel Date',
            'Departure Time',
            'KM Post',
            'User',
            'Amount',
            'Type',
            'Passenger name',
            'Passenger ID',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('D')->getAlignment()->setWrapText(true);

        return [];
    }



}
