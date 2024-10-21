<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class images extends Model
{
    use HasFactory;
    protected $fillable=[
        "capsule_id",
        "images",
        "capsule_type"
];
    public function image()
    {
        return $this->morphTo();
    }
}
