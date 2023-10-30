<?php

namespace App\Services;

use App\Models\Category;

class CatService
{
    public function getCategories()
    {
        $categories = Category::query()->get();
        return $categories;
    }    
}