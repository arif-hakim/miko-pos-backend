<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'date',
        'operator',
        'description',
        'supply_id',
        'source',
        'source_id'
    ];

    public function supply() {
        return $this->belongsTo(Supply::class, 'supply_id');
    }

    public function source() {
        $class = null;
        $relations = [];
        $source = $this->source;
        $source_id = $this->source_id;
        
        if ($source == 'supplier') $class = Supplier::class;
        else if ($source == 'supplier') {
            $class = Unit::class;
            $relations = ['branch'];
        }

        if (!$class) return null;
        return $class::where('id', $source_id)->with($relations)->first();
    }
}
