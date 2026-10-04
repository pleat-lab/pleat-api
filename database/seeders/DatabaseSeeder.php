<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Solo para desarrollo: todos los usuarios usan la contraseña "password".
     * Se puede correr más de una vez: busca por correo y actualiza en vez de duplicar.
     */
    public function run(): void
    {
        $this->seedUser('Mei Lin Zhou', 'mei@pleat.app', UserRole::Admin);
        $this->seedUser('Tomás Rivera', 'tomas@pleat.app', UserRole::Staff);
        $this->seedUser('Aisha Bello', 'aisha@pleat.app', UserRole::Staff);
        $this->seedUser('Daniel Kwon', 'daniel@pleat.app', UserRole::Staff);
        $this->seedUser('Priya Raghavan', 'priya@correo.com', UserRole::Customer);

        $this->call(CategorySeeder::class);
    }

    private function seedUser(string $name, string $email, UserRole $role): void
    {
        // unguarded: 'role' no es fillable en User a propósito; aquí sí lo necesitamos.
        User::unguarded(fn () => User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        ));
    }
}
