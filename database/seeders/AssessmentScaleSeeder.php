<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssessmentScale;

class AssessmentScaleSeeder extends Seeder
{
    public function run(): void
    {
        $scales = [

            [
                'value' => 1,
                'label' => 'Beginner',
                'description' => 'I have little or no experience with this skill.'
            ],

            [
                'value' => 2,
                'label' => 'Basic',
                'description' => 'I understand the basic concepts.'
            ],

            [
                'value' => 3,
                'label' => 'Intermediate',
                'description' => 'I can apply this skill in simple projects.'
            ],

            [
                'value' => 4,
                'label' => 'Advanced',
                'description' => 'I can use this skill confidently in real projects.'
            ],

            [
                'value' => 5,
                'label' => 'Expert',
                'description' => 'I can design complex solutions using this skill.'
            ]

        ];

        foreach ($scales as $scale) {
            AssessmentScale::create($scale);
        }
    }
}
