<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Counter;

class CounterController extends Controller
{
    public function counters()
    {
        $pageTitle = 'All Counter';
        $counters = Counter::searchable(['name', 'city', 'mobile'])->paginate(getPaginate());
        return view('admin.counter.list', compact('pageTitle', 'counters'));
    }

    public function counterStore(Request $request, $id = 0)
    {

        $request->validate([
            'name' => 'required|unique:counters,name,' . $id,
            'city' => 'required',
            'mobile' => 'required|numeric|unique:counters,mobile,' . $id
        ]);

        if ($id) {
            $counter = Counter::findOrFail($id);
            $message = 'Counter updated successfully';
        } else {
            $counter = new Counter();
            $message = 'Counter added successfully';
        }

        $counter->name      =  $request->name;
        $counter->city      =  $request->city;
        $counter->location  =  $request->location;
        $counter->mobile    =  $request->mobile;
        $counter->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Counter::changeStatus($id);
    }
}
