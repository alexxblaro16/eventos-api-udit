<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Listado público de categorías (para que el cliente pueda filtrar eventos).
     */
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        return $this->jsonOk(CategoryResource::collection($categories), 'Categorías disponibles.');
    }
}
