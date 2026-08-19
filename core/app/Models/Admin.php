<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $appends = [
        'has_authorization_code',
        'has_viewable_authorization_code',
    ];

    protected $fillable = [
        'name',
        'username',
        'email',
        'role_id',
        'passcode',
        'authorization_code_hash',
        'authorization_code_lookup',
        'authorization_code_encrypted',
        'password'
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'authorization_code_hash',
        'authorization_code_lookup',
        'authorization_code_encrypted',
        'remember_token'
    ];

    protected $casts = [
        'authorization_code_encrypted' => 'encrypted',
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

    public function role()
    {
        return $this->belongsTo(UserRole::class);
    }

    public function getHasAuthorizationCodeAttribute(): bool
    {
        return filled($this->authorization_code_hash)
            && filled($this->authorization_code_lookup);
    }

    public function getHasViewableAuthorizationCodeAttribute(): bool
    {
        return $this->has_authorization_code
            && filled($this->authorization_code_encrypted);
    }

}
