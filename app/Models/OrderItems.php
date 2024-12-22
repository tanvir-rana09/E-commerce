<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    protected $fillable = ['order_id','product_id','quantity','price','total_price','size','status'];

    function product(){
        return $this->belongsTo(Product::class);
    }
    function order(){
        return $this->belongsTo(Order::class);
    }
}
