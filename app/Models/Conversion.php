<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_unit_measurement',
        'operator',
        'amount',
        'end_unit_measurement',
        'unit_id',
    ];
}
