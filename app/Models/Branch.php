<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'company_id',
    ];

    public function company() {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function units() {
        return $this->hasMany(Unit::class, 'branch_id');
    }
}
