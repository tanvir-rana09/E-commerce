<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use function PHPSTORM_META\map;

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
    

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }
    public function getImagesAttribute($value)
    {
        // Decode the JSON string into an array
        $images = json_decode($value, true);

        // Check if the decoding was successful and the images array is not empty
        if ($images && is_array($images)) {
            // Map each image to a full URL if it doesn't already have one
            return array_map(function ($image) {
                // Check if the image path already starts with 'http' or 'https'
                if (preg_match('/^http?:\/\//', $image)) {
                    return $image; // Return as-is if it's already a URL
                }
                return url('storage/' . $image); // Otherwise, prepend the storage URL
            }, $images);
        }

        // Return an empty array if there are no images
        return [];
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
