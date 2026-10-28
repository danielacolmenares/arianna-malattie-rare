<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('demo.admin_email');
        $password = (string) config('demo.admin_password');

        if ($email === '' || $password === '') {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => 'Amministratrice demo', 'password' => $password],
        );
    }
}
