<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserRole;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = "Roles";
        $emptyMessage = "No roles found";

        $query = UserRole::query();

        // 1. Dynamic Filtering (Name)
        if ($request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // 2. Status Filtering
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // 3. Dynamic Sorting
        $sortField = $request->get('sort_field', 'id');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['id', 'name', 'status'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        $data = $query->paginate(getPaginate())->appends($request->all());

        $sidenav = json_decode(file_get_contents(resource_path('views/admin/partials/sidenav.json')));

        return view('admin.roles.main', compact('data', 'pageTitle', 'sidenav', 'emptyMessage'));
    }

    // New Method for Bulk Enable/Disable
    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action_type' => 'required|in:enable,disable'
        ]);

        $status = $request->action_type == 'enable' ? 1 : 0;

        UserRole::whereIn('id', $request->ids)->update(['status' => $status]);

        $notify[] = ['success', 'Selected roles have been successfully updated.'];
        return back()->withNotify($notify);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if (!$request->permissions) {
            $notify[] = ['success', 'No selected permission, please try again.'];
            return back()->withNotify($notify);
        }
        $data['permissions'] = json_encode($request->permissions);
        UserRole::create($data);
        $notify[] = ['success', 'Role was added.'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $data = $request->except(['_token']);
        $data['permissions'] = json_encode($request->permissions);
        UserRole::where('id', $id)->update($data);
        $notify[] = ['success', 'Role was updated.'];
        return back()->withNotify($notify);
    }

    public function remove($id)
    {

        $query = UserRole::where('id', $id);
        if ($query->delete()) {
            $notify[] = ['success', 'Role was deleted.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['error', 'Posting failed.'];
        return back()->withNotify($notify);
    }
}
