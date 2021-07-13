<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'unit_id'
    ];

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function products(){
        return $this->hasMany(Product::class, 'category_id');
    }
}
