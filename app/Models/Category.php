<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ["name", "slug","parent_id"];

    function setNameAttribute($value)
    {
        $this->attributes["name"] = $value;
        $this->attributes["slug"] = Str::slug($value);
    }

    function subcategory(){
        return $this->hasMany(Category::class,"parent_id")->with("subcategory");
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
