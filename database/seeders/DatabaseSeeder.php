<?php

namespace Database\Seeders;

use Database\Seeders\CountrySeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            CountrySeeder::class,
        ]);
    }
}
