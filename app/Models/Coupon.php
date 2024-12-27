<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'discount', 'expires_at', 'usage_limit', 'times_used'];


    public function scopeValidCoupon($query, $couponCode)
    {
        return $query->where('code', $couponCode)
                     ->where('expires_at', '>', now())
                     ->firstOrFail();
    }
}
