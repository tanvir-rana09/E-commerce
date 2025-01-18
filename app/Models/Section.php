<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ["name", "file", "type", 'description', 'button_text', 'button_link', 'status','title'];
    public function getFileAttribute($value)
    {
        return url('storage/' . $value);
    }
    
}
