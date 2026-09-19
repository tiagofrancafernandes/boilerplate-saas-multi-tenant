<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        /** @var User $admin */
        $admin = User::updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('power@123'),
                'email_verified_at' => now(),
                'preferences' => [
                    'locale' => 'pt_BR',
                    'timezone' => 'UTC',
                    'color_scheme' => 'dark',
                ],
            ],
        );

        $admin->syncRoles(['super-admin']);
    }
}
