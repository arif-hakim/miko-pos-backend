<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'website',
        'logo',
    ];

    public function branches(){
        return $this->hasMany('App\Models\Branch', 'company_id');
    }
    
    public function units() {
        return $this->hasManyThrough(Unit::class, Branch::class);
    }
}
