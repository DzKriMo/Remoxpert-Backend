<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientRequest extends Model
{
    protected $fillable = [
        'client_name',
        'email',
        'phone_number',
        'company_name',
        'company_code',
        'status',
        'admin_comment'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
