<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterestSubfieldSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('interest_subfields')->insert([

            // Technology
            ['field_id' => 1, 'name' => 'Software Development', 'created_at' => now(), 'updated_at' => now()],
            ['field_id' => 1, 'name' => 'Data', 'created_at' => now(), 'updated_at' => now()],

            // Creative
            ['field_id' => 2, 'name' => 'UI/UX Design', 'created_at' => now(), 'updated_at' => now()],
            ['field_id' => 2, 'name' => 'Graphic Design', 'created_at' => now(), 'updated_at' => now()],

            // Business
            ['field_id' => 3, 'name' => 'Digital Marketing', 'created_at' => now(), 'updated_at' => now()],
            ['field_id' => 3, 'name' => 'Product Management', 'created_at' => now(), 'updated_at' => now()],

        ]);
    }
}
