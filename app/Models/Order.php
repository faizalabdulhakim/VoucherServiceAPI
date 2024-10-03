<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Order extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'user_id',
        'voucher_code',
        'total_price',
        'discount',
        'final_price'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_product')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // find voucher by voucher_code
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_code', 'code');
    }
}
