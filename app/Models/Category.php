<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_code',
        'name'
    ];

    public function brands() {
        return $this->hasMany(Brand::class);
    }
}
