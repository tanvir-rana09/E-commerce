<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isEmpty;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'stock',
        'category_id',
        'subcategory_id',
        'images',
        'banner',
        'short_desc',
        'long_desc',
        'rating',
        'sells',
        'gender',
        'status',
        'discount',
        'size',
        'sku'
    ];
    protected $casts = [
        'size' => 'array',
    ];
    protected $appends = ['discount_price'];
    public $timestamps = false;
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }
    public function getImagesAttribute($value)
    {
        $images = json_decode($value, true);
        if ($images && is_array($images)) {
            return array_map(function ($image) {
                if (preg_match('/^http?:\/\//', $image)) {
                    return $image;
                }
                return url('storage/' . $image);
            }, $images);
        }
        return [];
    }

    public function getDiscountAttribute()
    {
        $currentDate = Carbon::now()->startOfMinute();
        $currentDate->setTimezone('UTC');

        $discount = Discount::where(function ($query) {
            $query->where('type', 'single')->where('product_id', $this->id)
                ->orWhere(function ($q) {
                    $q->where('type', 'category')->where('category_id', $this->category_id);
                })
                ->orWhere('type', 'global');
        })
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->orderByDesc('discount_percentage')
            ->first();

        $existingDiscount = $this->attributes['discount'] ?? 0;

        return $discount ? $existingDiscount + $discount->discount_percentage : $existingDiscount;
    }


    public function getDiscountPriceAttribute()
    {
        $discountPercentage = 0;
        return round($this->price * (1 - ($discountPercentage / 100)), 2);
    }


    public function getBannerAttribute($value)
    {
        return url('storage/' . $value);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, "category_id", 'id');
    }
    public function comment()
    {
        return $this->hasMany(Comment::class, "product_id", 'id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, "subcategory_id", 'id');
    }
}
