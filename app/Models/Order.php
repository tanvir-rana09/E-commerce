<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'products',
        'subtotal',
        'discount_amount',
        'shipping_cost',
        'total_price',
        'total_items',
        'shipping_address',
        'payment_method',
        'payment_number',
        'trx_id',
        'payment_status',
        'coupon_code',
        'delivery_status',
        'order_notes',
    ];

    function getProductsAttribute($value)
    {
        return json_decode($value, true);
    }
    function getShippingAddressAttribute($value)
    {
        return json_decode($value, true);
    }
    public function orderItems(){
        return $this->hasOne(OrderItems::class,);
    }
}
