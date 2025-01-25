<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->insert([
            [
                'name' => 'one-week-free',
                'price' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'one-month',
                'price' => 100000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'two-months',
                'price' => 200000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'six-months',
                'price' => 300000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'one-year',
                'price' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
