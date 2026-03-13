<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CareerRole;

class CareerRoleSeeder extends Seeder
{
    public function run(): void
    {

        $roles = [

            // Software Development (subfield_id = 1)
            [
                'name' => 'Frontend Developer',
                'description' => 'Build user interfaces for web applications',
                'subfield_id' => 1
            ],
            [
                'name' => 'Backend Developer',
                'description' => 'Develop server-side logic and APIs',
                'subfield_id' => 1
            ],
            [
                'name' => 'Mobile Developer',
                'description' => 'Develop Android or iOS applications',
                'subfield_id' => 1
            ],

            // Data Science (subfield_id = 2)
            [
                'name' => 'Data Analyst',
                'description' => 'Analyze datasets to generate insights',
                'subfield_id' => 2
            ],
            [
                'name' => 'Data Scientist',
                'description' => 'Build predictive models and machine learning systems',
                'subfield_id' => 2
            ],

            // UI/UX
            [
                'name' => 'UI Designer',
                'description' => 'Design visual interfaces',
                'subfield_id' => 3
            ],
            [
                'name' => 'UX Designer',
                'description' => 'Design user experiences',
                'subfield_id' => 3
            ],

            // Graphic Design
            [
                'name' => 'Graphic Designer',
                'description' => 'Create visual assets and branding',
                'subfield_id' => 4
            ],

            // Digital Marketing
            [
                'name' => 'Digital Marketer',
                'description' => 'Promote products through digital channels',
                'subfield_id' => 5
            ],

            // Product Management
            [
                'name' => 'Product Manager',
                'description' => 'Manage product development lifecycle',
                'subfield_id' => 6
            ],

        ];

        foreach ($roles as $role) {
            CareerRole::create($role);
        }
    }
}
