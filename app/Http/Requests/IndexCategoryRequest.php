<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class IndexCategoryRequest extends FormRequest
{
    // Regla del proyecto: si la acción tiene FormRequest, se autoriza en authorize().
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Category::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            // Tope de 50 para que nadie pida toda la tabla de golpe.
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }
}
