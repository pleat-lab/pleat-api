<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

// Cómo se autoriza en este proyecto: si la acción tiene FormRequest, se autoriza en su
// authorize(); si no, se usa Gate::authorize() aquí en el controlador. Ambos usan CategoryPolicy.
class CategoryController extends Controller
{
    public function index(IndexCategoryRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated('per_page') ?? 15);
        $search = $request->validated('search');

        $categories = Category::query()
            ->when($search, function ($query, $search) {
                // En LIKE, "%" y "_" son comodines; se escapan para buscar el texto tal cual.
                $term = addcslashes($search, '%_\\');

                // En Postgres "like" distingue mayúsculas; "ilike" no, y es lo que se espera al buscar.
                $query->where('name', 'ilike', "%{$term}%");
            })
            ->orderBy('position')
            // Desempate: sin él, categorías con la misma position pueden cambiar de orden
            // entre páginas y aparecer repetidas o saltarse.
            ->orderBy('id')
            ->paginate($perPage);

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        // Laravel ya responde 201 al crear un modelo; se deja explícito para que se lea.
        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        Gate::authorize('view', $category);

        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    public function destroy(Category $category): Response
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return response()->noContent();
    }
}
