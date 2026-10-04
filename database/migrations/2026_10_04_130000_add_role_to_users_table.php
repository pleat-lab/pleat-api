<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::Customer->value);
        });

        // Los roles válidos salen del enum, así hay una sola lista que mantener.
        $roles = implode(', ', array_map(fn (UserRole $role) => "'{$role->value}'", UserRole::cases()));

        // El check hace que la base rechace roles inválidos aunque falle la validación de la app.
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$roles}))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
