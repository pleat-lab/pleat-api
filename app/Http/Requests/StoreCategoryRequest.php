<?php

namespace App\Http\Requests;

use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    // Regla del proyecto: si la acción tiene FormRequest, se autoriza en authorize().
    // Así un usuario sin permiso recibe 403 antes de ver errores de validación.
    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // "unique" distingue mayúsculas en Postgres y dejaría pasar "bao" si ya existe "Bao".
                // Se compara con lower() en ambos lados, igual que el índice único de la tabla.
                function (string $attribute, mixed $value, Closure $fail) {
                    if (Category::whereRaw('lower(name) = lower(?)', [$value])->exists()) {
                        $fail('Ya existe una categoría con ese nombre.');
                    }
                },
            ],
            'position' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
