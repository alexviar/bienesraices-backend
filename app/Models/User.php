<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'email',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password'
    ];

    public function setPasswordAttribute($value){
        $this->attributes["password"] = Hash::make($value);
    }

    public function isSuperUser(){
        return $this->hasRole("Super usuarios");
    }

    // public function checkPermissionTo($permission, $guardName = null): bool
    // {
    //     var_dump($permission);
    //     $result = parent::checkPermissionTo($permission, $guardName);
    //     while(!$result){
    //         $permissions = $this->getAllPermissions();
    //     }
    // }
}
