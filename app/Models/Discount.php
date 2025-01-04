<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'type',
        'product_id',
        'category_id',
        'discount_percentage',
        'start_date',
        'end_date',
    ];
    protected $appends = ['formatted_start_date', 'formatted_end_date'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function getFormattedStartDateAttribute()
    {
        return Carbon::parse($this->start_date)->format('F j, Y');
    }

    public function getFormattedEndDateAttribute()
    {
        return Carbon::parse($this->end_date)->format('F j, Y');
    }
}
