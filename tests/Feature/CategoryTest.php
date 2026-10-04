<?php

use App\Models\Category;
use App\Models\User;

it('lista categorías a un usuario autenticado, ordenadas por position', function () {
    Category::factory()->create(['name' => 'Bao', 'position' => 2]);
    Category::factory()->create(['name' => 'Gyozas', 'position' => 1]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/categories')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Gyozas')
        ->assertJsonPath('data.1.name', 'Bao')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('responde 401 al listar sin token', function () {
    $this->getJson('/api/categories')->assertUnauthorized();
});

it('acepta un per_page válido', function () {
    Category::factory()->count(3)->create();

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/categories?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2);
});

it('rechaza un per_page inválido con 422', function (string $perPage) {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson("/api/categories?per_page={$perPage}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
})->with(['0', '51', 'abc']);

it('rechaza un search que no es texto con 422', function () {
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/categories?search[]=bao')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('search');
});

it('busca por nombre sin importar mayúsculas (ilike)', function () {
    Category::factory()->create(['name' => 'Gyozas']);
    Category::factory()->create(['name' => 'Bebidas']);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/categories?search=GYO')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Gyozas');
});

it('trata % en la búsqueda como texto y no como comodín', function () {
    Category::factory()->create(['name' => 'Gyozas']);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson('/api/categories?search=%25')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('muestra una categoría a un usuario autenticado', function () {
    $category = Category::factory()->create(['name' => 'Caldos']);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.name', 'Caldos');
});

it('responde 401 al crear, editar y borrar sin token', function () {
    $category = Category::factory()->create();

    $this->postJson('/api/categories', ['name' => 'Postres'])->assertUnauthorized();
    $this->putJson("/api/categories/{$category->id}", ['name' => 'Otro'])->assertUnauthorized();
    $this->deleteJson("/api/categories/{$category->id}")->assertUnauthorized();
});

it('deja al admin crear una categoría', function () {
    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->postJson('/api/categories', ['name' => 'Postres', 'position' => 6])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Postres')
        ->assertJsonPath('data.active', true);

    $this->assertDatabaseHas('categories', ['name' => 'Postres', 'position' => 6]);
});

it('deja al admin editar una categoría', function () {
    $category = Category::factory()->create(['name' => 'Bao']);

    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->putJson("/api/categories/{$category->id}", ['name' => 'Bao al vapor'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Bao al vapor');

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Bao al vapor']);
});

it('deja conservar el mismo nombre al editar', function () {
    $category = Category::factory()->create(['name' => 'Bao']);

    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->putJson("/api/categories/{$category->id}", ['name' => 'Bao', 'position' => 9])
        ->assertOk();
});

it('deja al admin borrar una categoría', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->deleteJson("/api/categories/{$category->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('responde 403 a staff y customer al crear, editar y borrar', function (string $role) {
    $user = $role === 'staff' ? User::factory()->staff()->create() : User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/categories', ['name' => 'Postres'])->assertForbidden();
    $this->putJson("/api/categories/{$category->id}", ['name' => 'Otro'])->assertForbidden();
    $this->deleteJson("/api/categories/{$category->id}")->assertForbidden();

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => $category->name]);
})->with(['staff', 'customer']);

it('exige el nombre al crear', function () {
    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->postJson('/api/categories', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name' => 'El campo nombre es obligatorio.']);
});

it('no permite nombres repetidos', function () {
    Category::factory()->create(['name' => 'Bao']);

    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->postJson('/api/categories', ['name' => 'Bao'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name' => 'Ya existe una categoría con ese nombre.']);
});

it('no permite "bao" si ya existe "Bao"', function () {
    Category::factory()->create(['name' => 'Bao']);

    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->postJson('/api/categories', ['name' => 'bao'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('no deja editar con el nombre de otra categoría', function () {
    Category::factory()->create(['name' => 'Bao']);
    $gyozas = Category::factory()->create(['name' => 'Gyozas']);

    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->putJson("/api/categories/{$gyozas->id}", ['name' => 'BAO'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('rechaza una position mayor a 9999', function () {
    $this->actingAs(User::factory()->admin()->create(), 'sanctum')
        ->postJson('/api/categories', ['name' => 'Postres', 'position' => 10000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('position');
});
