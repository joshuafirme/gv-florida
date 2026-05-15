<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\UserRole;
use Hash;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Admin Users';
        $emptyMessage = 'No admin users found';

        // Base Query
        $query = Admin::select('admins.*', 'ur.name as role', 'permissions')
            ->leftJoin('user_roles as ur', 'ur.id', '=', 'admins.role_id');

        // 1. Dynamic Filtering (Name, Username, Email)
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('admins.name', 'LIKE', "%$search%")
                    ->orWhere('admins.username', 'LIKE', "%$search%")
                    ->orWhere('admins.email', 'LIKE', "%$search%");
            });
        }

        // 2. Role Filtering
        if ($request->has('role_id') && $request->role_id != 'all') {
            $query->where('admins.role_id', $request->role_id);
        }

        // 3. Status Filtering
        if ($request->has('status') && $request->status != 'all') {
            $query->where('admins.status', $request->status);
        }

        // 4. Dynamic Sorting
        $sortField = $request->get('sort_field', 'admins.id');
        $sortOrder = $request->get('sort_order', 'desc');

        // Map allowed sort fields to DB columns to prevent SQL injection
        $allowedSorts = [
            'name' => 'admins.name',
            'email' => 'admins.email',
            'username' => 'admins.username',
            'role' => 'ur.name',
            'status' => 'admins.status',
            'id' => 'admins.id'
        ];

        if (array_key_exists($sortField, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder);
        }

        $data = $query->paginate(getPaginate())->appends($request->all());
        $roles = UserRole::where('status', 1)->get();

        return view('admin.admin-user.main', compact('pageTitle', 'data', 'roles', 'emptyMessage'));
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

        Admin::whereIn('id', $request->ids)->update(['status' => $status]);

        $notify[] = ['success', 'Selected users have been successfully updated.'];
        return back()->withNotify($notify);
    }


    public function store(Request $request)
    {
        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        $user = Admin::create($data);

        $notify[] = ['success', 'User was added.'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $user = Admin::find($id);
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->role_id = $request->role_id;
        $user->passcode = $request->passcode;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        if ($user->save()) {
            $notify[] = ['success', 'User was updated.'];
            return back()->withNotify($notify);
        }
        $notify[] = ['error', 'Posting failed.'];
        return back()->withNotify($notify);
    }

    public function remove($id)
    {

        $query = Admin::where('id', $id);
        if ($query->delete()) {
            $notify[] = ['success', 'User was deleted.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['error', 'Posting failed.'];
        return back()->withNotify($notify);
    }
}
