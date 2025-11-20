<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $fillable = [
        'name',
        'username',
        'email',
        'role_id',
        'passcode'
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    public function permissions()
    {
        return $this->hasOne(UserRole::class, 'id', 'role_id');
    }


    public function isPermitted($page = null)
    {
        $is_permitted = false;

        if (!$page) {
            $page = request()->module;
        }

        if (request()->user('admin')) {

            $permissions = UserRole::permissions('admin');
            if (in_array($page, $permissions)) {
                $is_permitted = true;
            }
        }

        $current_path = request()->path();

        $accessible_paths = [];

        foreach ($accessible_paths as $path) {
            if (str_contains($current_path, $path)) {
                $is_permitted = true;
                break;
            }
        }

        return $is_permitted;
    }

}
