<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\UserRole;
use Hash;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(UserRole $roles)
    {
        $pageTitle = 'Admin Users';
        $query = Admin::select('admins.*', 'ur.name as role', 'permissions')
            ->leftJoin('user_roles as ur', 'ur.id', '=', 'admins.role_id');

        if (request()->key) {
            $query->orWhere('admins.name', 'LIKE', '%' . request()->key . '%');
            $query->orWhere('admins.email', 'LIKE', '%' . request()->key . '%');
        }

        $data = $query->paginate(getPaginate());
        $roles = UserRole::where('status', 1)->get();

        return view('admin.admin-user.main', compact('pageTitle', 'data', 'roles'));
    }


    public function store(Request $request)
    {
        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        Admin::create($data);

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
