<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ["name", "slug","parent_id"];
    protected $appends = ['formatted_created_at'];

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
    public function getFormattedCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('F j, Y');
    }
}
