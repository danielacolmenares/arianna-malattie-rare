<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('demo.password');

        if ($password === '') {
            return;
        }

        foreach (config('demo.users', []) as $account) {
            if (($account['email'] ?? '') === '') {
                continue;
            }

            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => $password,
                ],
            );
        }
    }
}
