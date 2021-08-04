<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'branch_id',
        'qrcode',
        'qrcode_content',
        'tax'
    ];

    protected $appends = [
        'qrcode_url'
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    
    public function getQrcodeUrlAttribute(){
        $base_url = config('app.url');
        return array_key_exists('qrcode', $this->attributes) ? $base_url . '/storage/qrcodes/' . $this->attributes['qrcode'] : null;
    }
}
