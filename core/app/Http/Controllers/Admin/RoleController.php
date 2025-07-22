<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserRole;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $pageTitle = "Roles";
        $data = UserRole::paginate(getPaginate());

        $sidenav = json_decode(file_get_contents(resource_path('views/admin/partials/sidenav.json')));


        return view('admin.roles.main', compact('data', 'pageTitle', 'sidenav'));
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
