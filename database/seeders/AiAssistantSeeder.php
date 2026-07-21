<?php

namespace Database\Seeders;

use App\Models\AiAssistant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AiAssistantSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AiAssistant::firstOrCreate(['name' => 'ویرابات']);
    }
}
