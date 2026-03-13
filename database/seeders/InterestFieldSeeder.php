<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterestFieldSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('interest_fields')->insert([
            ['name' => 'Technology', 'description' => 'Interest in technology such as coding, robotics, engineering, etc.', 'created_at'=>now(),'updated_at'=>now()],
            ['name' => 'Creative', 'description' => 'Interest in creative fields such as art, music, writing, etc.', 'created_at'=>now(),'updated_at'=>now()],
            ['name' => 'Business', 'description' => 'Interest in business fields such as finance, marketing, management, etc.', 'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
