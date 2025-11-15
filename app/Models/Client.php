<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;


class Client extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = ['name', 'email','code', 'password', 'last_login_at'];
    protected $hidden = ['password'];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['type' => 'client'];
    }
}

