<?php

namespace App\Http\Requests;

use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    // Regla del proyecto: si la acción tiene FormRequest, se autoriza en authorize();
    // si no, se usa Gate::authorize() en el controlador.
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                // Igual que en Store (comparación con lower()), pero whereKeyNot deja que la
                // categoría conserve su propio nombre.
                function (string $attribute, mixed $value, Closure $fail) {
                    $exists = Category::whereRaw('lower(name) = lower(?)', [$value])
                        ->whereKeyNot($this->route('category'))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe una categoría con ese nombre.');
                    }
                },
            ],
            'position' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
