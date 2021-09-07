<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'unit_measurement',
        'base_price',
        'selling_price',
        'unit_id',
        'category_id',
        'picture',
    ];

    protected $appends = [
        'default_picture'
    ];

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function category(){
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function stockHistories(){
        return $this->hasMany(rawMaterialStockHistory::class, 'product_id');
    }

    public function recipe(){
        return $this->hasMany(Recipe::class, 'product_id');
    }

    public function getPictureAttribute($value) {
        return $value ? config('app.url') . '/storage/products/' . $value : null;
    }

    public function getDefaultPictureAttribute() {
        return config('app.url') . '/default.png';
    }

    public function useRawMaterial($transaction_code = '', $employee_unit_id = '') {
        if (!$this->recipe) return false;
        $sufficient = true;
        foreach($this->recipe as $recipe) {
            $raw_material = $recipe->raw_material;
            $product_name = $this->attributes['name'];

            $amount = null;
            if ($raw_material->conversion->operator == '*') $amount = $recipe->amount / $raw_material->conversion->amount; 
            else if ($raw_material->conversion->operator == '/') $amount = $recipe->amount * $raw_material->conversion->amount; 

            $rawMaterialStockHistory = new RawMaterialStockHistory();
            $rawMaterialStockHistory->raw_material_id = $raw_material->id;
            $rawMaterialStockHistory->from = $raw_material->stock;
            $rawMaterialStockHistory['changes'] = -1 * $amount;
            $rawMaterialStockHistory->to = $raw_material->stock - $amount;
            $rawMaterialStockHistory->description = "$product_name | #$transaction_code";
            if ($employee_unit_id) {
                $rawMaterialStockHistory->source = 'unit';
                $rawMaterialStockHistory->source_id = $employee_unit_id;
            }
            $rawMaterialStockHistory->save();
            
            $raw_material->stock -= $amount;
            $raw_material->save();
        }
    }

    public function restoreRawMaterial($transaction_code = '', $employee_unit_id = '') {
        if (!$this->recipe) return false;
        foreach($this->recipe as $recipe) {
            $raw_material = $recipe->raw_material;
            $product_name = $this->attributes['name'];

            $amount = null;
            if ($raw_material->conversion->operator == '*') $amount = $recipe->amount / $raw_material->conversion->amount; 
            else if ($raw_material->conversion->operator == '/') $amount = $recipe->amount * $raw_material->conversion->amount; 

            $rawMaterialStockHistory = new RawMaterialStockHistory();
            $rawMaterialStockHistory->raw_material_id = $raw_material->id;
            $rawMaterialStockHistory->from = $raw_material->stock;
            $rawMaterialStockHistory['changes'] = 1 * $amount;
            $rawMaterialStockHistory->to = $raw_material->stock + $amount;
            $rawMaterialStockHistory->description = "$product_name | #$transaction_code";
            if ($employee_unit_id) {
                $rawMaterialStockHistory->source = 'unit';
                $rawMaterialStockHistory->source_id = $employee_unit_id;
            }
            $rawMaterialStockHistory->save();
            
            $raw_material->stock += $amount;
            $raw_material->save();
        }
    }
}
