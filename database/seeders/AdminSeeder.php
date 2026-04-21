<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name'     => 'Super Admin',
                'email'    => 'admin@nrhintelligence.com',
                'password' => 'Admin@1234',
                'role'     => 'super_admin',
                'status'   => 'active',
            ],
            [
                'name'     => 'Operations',
                'email'    => 'ops@nrhintelligence.com',
                'password' => 'Admin@1234',
                'role'     => 'operations',
                'status'   => 'active',
            ],
        ];

        foreach ($accounts as $account) {
            Admin::updateOrCreate(
                ['email' => $account['email']],
                $account
            );
        }
    }
}
