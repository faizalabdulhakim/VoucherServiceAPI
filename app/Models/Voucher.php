<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Voucher extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'code',
        'discount',
        'expiry_date',
        'activation_date',
        'is_active',
    ];
}
