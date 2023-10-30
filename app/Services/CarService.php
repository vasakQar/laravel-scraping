<?php

namespace App\Services;

use App\Models\Car;

class CarService
{
    public function initData($request)
    {
        $count = request('per_page', 50);
        $category = $request->input('category');
        $brand = $request->input('brand');

        $cars = Car::query()
            ->with('images')
            ->when($category, function ($query) use ($category) {
                $query->where('category_id', $category);
            })->when($brand, function ($query) use ($brand) {
                $query->where('brand_id', $brand);
            })
            ->paginate($count);
        return $cars;
    }
}