<?php

namespace App\Http\Controllers;

use App\Http\Requests\HomePageRequest;
use App\Models\Brand;
use App\Models\Car;
use App\Services\CarService;
use App\Services\CatService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $carService = new CarService();
        $cars = $carService->initData($request);

        $catService = new CatService();
        $categories = $catService->getCategories();

        return view('home', compact('cars', 'categories'));
    }

    public function getBrands($category)
    {
        $brands = Brand::where('category_id', $category)->get();
        return response()->json(['brands' => $brands]);
    }
}
