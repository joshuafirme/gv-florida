<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\UserRole;
use App\Services\TransactionAuthorizationService;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $request->merge([
            'authorization_code' => $this->normalizedAuthorizationCode($request),
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:admins,email',
            'username' => 'required|string|max:255|unique:admins,username',
            'role_id' => 'required|integer|exists:user_roles,id',
            'passcode' => 'nullable|string|max:100',
            'authorization_code' => 'nullable|string|min:6|max:100',
            'password' => 'required|string|min:5',
        ]);

        if (!empty($data['authorization_code'])) {
            $this->ensureUniqueAuthorizationCode($data['authorization_code']);
            $data['authorization_code_hash'] = TransactionAuthorizationService::hash($data['authorization_code']);
            $data['authorization_code_lookup'] = TransactionAuthorizationService::lookup($data['authorization_code']);
            $data['authorization_code_encrypted'] = $data['authorization_code'];
        }

        $data['password'] = Hash::make($data['password']);
        unset($data['authorization_code']);

        Admin::create($data);

        $notify[] = ['success', 'User was added.'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $user = Admin::findOrFail($id);
        $request->merge([
            'authorization_code' => $this->normalizedAuthorizationCode($request),
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('admins', 'username')->ignore($user->id)],
            'role_id' => 'required|integer|exists:user_roles,id',
            'passcode' => 'nullable|string|max:100',
            'authorization_code' => 'nullable|string|min:6|max:100',
            'remove_authorization_code' => 'nullable|boolean',
            'password' => 'nullable|string|min:5',
        ]);

        $user->fill(collect($data)->only([
            'name',
            'username',
            'email',
            'role_id',
            'passcode',
        ])->all());

        if (!empty($data['authorization_code'])) {
            $this->ensureUniqueAuthorizationCode($data['authorization_code'], $user->id);
            $user->authorization_code_hash = TransactionAuthorizationService::hash($data['authorization_code']);
            $user->authorization_code_lookup = TransactionAuthorizationService::lookup($data['authorization_code']);
            $user->authorization_code_encrypted = $data['authorization_code'];
        } elseif (!empty($data['remove_authorization_code'])) {
            $user->authorization_code_hash = null;
            $user->authorization_code_lookup = null;
            $user->authorization_code_encrypted = null;
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($user->save()) {
            $notify[] = ['success', 'User was updated.'];
            return back()->withNotify($notify);
        }
        $notify[] = ['error', 'Posting failed.'];
        return back()->withNotify($notify);
    }

    public function authorizationCode($id)
    {
        $user = Admin::findOrFail($id);

        if (!$user->has_authorization_code) {
            return response()->json([
                'message' => 'This user does not have an Authorization Code assigned.',
            ], 404);
        }

        if (!$user->authorization_code_encrypted) {
            return response()->json([
                'message' => 'This existing code was stored before secure viewing was available. Enter a new code once to make Show/Hide available.',
            ], 409);
        }

        return response()->json([
            'authorization_code' => $user->authorization_code_encrypted,
        ])->header('Cache-Control', 'no-store, private');
    }

    private function ensureUniqueAuthorizationCode(string $code, ?int $exceptAdminId = null): void
    {
        $exists = Admin::query()
            ->where('authorization_code_lookup', TransactionAuthorizationService::lookup($code))
            ->when($exceptAdminId, fn ($query) => $query->where('id', '!=', $exceptAdminId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'authorization_code' => 'This authorization code is already assigned to another user.',
            ]);
        }
    }

    private function normalizedAuthorizationCode(Request $request): ?string
    {
        $code = trim((string) $request->input('authorization_code'));

        return $code !== '' ? $code : null;
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
