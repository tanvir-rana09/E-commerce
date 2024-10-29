<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable =["title", "desc","user_id","product_id"];

    public function user(){
        return $this->belongsTo(User::class,);
    }
}
