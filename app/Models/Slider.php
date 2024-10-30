<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = ["page", "files"];
    function getFilesAttribute($value)
    {
        return json_decode($value,true);
    }
}
