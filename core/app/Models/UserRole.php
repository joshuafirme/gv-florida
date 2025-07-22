<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Utils;

class UserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'permissions',
        'status'
    ];

    
    public static function permissions() {
        $role_id = isset(Auth::user()->role_id) ? Auth::user()->role_id : '';
        $permissions = self::where('id', $role_id)->value('permissions');
        return $permissions ? json_decode($permissions) : [];
    } 
}
