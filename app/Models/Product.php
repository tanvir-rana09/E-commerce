<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Product extends Model
{
    protected $fillable = [
        'name', 'price', 'quantity', 'category_id', 'subcategory_id', 'images', 'banner', 'short_desc', 'long_desc'
    ];
    

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value); 
    }
    public function getImagesAttribute($value)
    {
        return json_decode($value, true);
    }

    public function category(){
        return $this->belongsTo(Category::class,"category_id",'id');
    }
    public function comment(){
        return $this->hasMany(Comment::class,"product_id",'id');
    }
    
    public function subcategory(){
        return $this->belongsTo(Category::class,"subcategory_id",'id');
    }
}
