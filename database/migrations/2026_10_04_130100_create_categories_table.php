<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Índice único sobre lower(name): en Postgres un unique normal deja convivir "Bao" y "bao".
        DB::statement('CREATE UNIQUE INDEX categories_name_lower_unique ON categories (lower(name))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS categories_name_lower_unique');

        Schema::dropIfExists('categories');
    }
};
