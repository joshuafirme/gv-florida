<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function discounts()
    {
        $pageTitle = 'All Discount';
        $discounts = Discount::searchable(['name', 'uid'])->paginate(getPaginate());
        $counters = Counter::where('status', Status::ENABLE)->get();
        return view('admin.discount.list', compact('pageTitle', 'discounts', 'counters'));
    }

    public function discountStore(Request $request, $id = 0)
    {

        if ($id) {
            $discount = Discount::findOrFail($id);
            $message = 'Discount updated successfully';
        } else {
            $discount = new Discount();
            $message = 'Discount added successfully';
        }

        $discount->name = $request->name;
        $discount->percentage = $request->percentage;
        $discount->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }


    public function status($id)
    {
        return Discount::changeStatus($id);
    }
}
