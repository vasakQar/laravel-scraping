<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_code',
        'category_id',
        'brand_id',
        'name',
        'year',
        'amount',
        'currency_code'
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function brand() {
        return $this->belongsTo(Brand::class);
    }

    public function images() {
        return $this->hasMany(CarImage::class);
    }
}
