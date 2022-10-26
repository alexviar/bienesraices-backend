<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
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
        'vendedor_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password'
    ];

    protected $appends = [
        "estado_text"
    ];

    public function setPasswordAttribute($value){
        $this->attributes["password"] = Hash::make($value);
    }

    #region Accessors
    public function getEstadoTextAttribute(){
        if($this->estado == 1) return "Activo";
        if($this->estado == 2) return "Inactivo";
    }
    #endregion

    public function isSuperUser(){
        return $this->hasRole("Super usuarios");
    }

    #region Relationships
    /**
     * 
     * @return BelongsTo
     */
    public function vendedor(){
        return $this->belongsTo(Vendedor::class);
    }

    /**
     * 
     * @return BelongsToMany
     */
    public function proyectos(){
        return $this->belongsToMany(Proyecto::class);
    }
    #endregion
}
