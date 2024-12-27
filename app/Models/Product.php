<?php

namespace App\Models;

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

    public function getDiscountPriceAttribute()
    {
        $totalDiscount = 0;
        $discount = Discount::where('is_active', 1)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('type', 'single')->where('product_id', $this->id);
                })
                    ->orWhere(function ($q) {
                        $q->where('type', 'category')->where('category_id', $this->category_id);
                    })
                    ->orWhere('type', 'global');
            })
            ->orderByDesc('discount_percentage')
            ->first();

        if ($discount) {
            $totalDiscount += $discount->discount_percentage;
        }

        if ($this->discount) {
            $totalDiscount += $this->discount;
        }

        if ($totalDiscount > 0) {
            return round($this->price * (1 - ($totalDiscount / 100)), 2);
        }

        return $this->price;
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
