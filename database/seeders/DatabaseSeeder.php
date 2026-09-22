<?php

namespace Database\Seeders;

use App\Models\LetterCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $divisiUmum = User::factory()->create(['name' => 'Divisi Umum', 'division_name' => 'Divisi Umum', 'email' => 'demo@esurat.local', 'role' => 'divisi']);
        User::factory()->create(['name' => 'Divisi Keuangan', 'division_name' => 'Divisi Keuangan', 'email' => 'keuangan@esurat.local', 'role' => 'divisi']);
        User::factory()->create(['name' => 'Administrasi Umum', 'email' => 'admin@esurat.local', 'role' => 'admin_umum']);
        User::factory()->create(['name' => 'Direktur Utama', 'email' => 'director@esurat.local', 'role' => 'direktur']);
        $umum = LetterCategory::create(['name' => 'Umum', 'number_format' => 'UM/{year}/{number}']);
        $umum->children()->create(['name' => 'Administrasi']);
        LetterCategory::create(['name' => 'Keuangan', 'number_format' => 'KEU/{year}/{number}']);
    }
}
