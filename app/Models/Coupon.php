<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'discount', 'expires_at', 'usage_limit', 'times_used'];

    protected $appends = ['formatted_expires_at'];
    
    public function getFormattedExpiresAtAttribute()
    {
        return Carbon::parse($this->attributes['expires_at'])->format('F j, Y');
    }
    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('F j, Y');
    }
}
