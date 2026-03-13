<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterestQuestionSeeder extends Seeder
{
    public function run(): void
    {

        $questions = [

            // =========================
            // Software Development
            // =========================

            [
                'subfield_id' => 1,
                'question' => 'I enjoy building web or mobile applications using programming languages.',
                'weight' => 3
            ],
            [
                'subfield_id' => 1,
                'question' => 'I enjoy solving logical problems using algorithms or coding challenges.',
                'weight' => 3
            ],
            [
                'subfield_id' => 1,
                'question' => 'I am interested in developing interactive applications for users.',
                'weight' => 2
            ],
            [
                'subfield_id' => 1,
                'question' => 'I enjoy learning new programming frameworks or development tools.',
                'weight' => 1
            ],
            [
                'subfield_id' => 1,
                'question' => 'I enjoy debugging code and fixing issues in software systems.',
                'weight' => 2
            ],

            // =========================
            // Data Science
            // =========================

            [
                'subfield_id' => 2,
                'question' => 'I enjoy analyzing datasets to discover patterns or insights.',
                'weight' => 3
            ],
            [
                'subfield_id' => 2,
                'question' => 'I enjoy using statistics or mathematical models to understand problems.',
                'weight' => 3
            ],
            [
                'subfield_id' => 2,
                'question' => 'I enjoy working with large datasets to extract meaningful information.',
                'weight' => 2
            ],
            [
                'subfield_id' => 2,
                'question' => 'I am interested in predicting future outcomes using data.',
                'weight' => 2
            ],
            [
                'subfield_id' => 2,
                'question' => 'I enjoy creating charts, dashboards, or data visualizations.',
                'weight' => 1
            ],

            // =========================
            // UI / UX Design
            // =========================

            [
                'subfield_id' => 3,
                'question' => 'I enjoy designing intuitive interfaces for mobile or web applications.',
                'weight' => 3
            ],
            [
                'subfield_id' => 3,
                'question' => 'I enjoy improving how users interact with digital products.',
                'weight' => 3
            ],
            [
                'subfield_id' => 3,
                'question' => 'I enjoy creating wireframes or prototypes for applications.',
                'weight' => 2
            ],
            [
                'subfield_id' => 3,
                'question' => 'I enjoy analyzing user behavior to improve usability.',
                'weight' => 2
            ],
            [
                'subfield_id' => 3,
                'question' => 'I enjoy organizing layouts and user flows for better experience.',
                'weight' => 1
            ],

            // =========================
            // Graphic Design
            // =========================

            [
                'subfield_id' => 4,
                'question' => 'I enjoy creating digital graphics using tools like Photoshop or Illustrator.',
                'weight' => 3
            ],
            [
                'subfield_id' => 4,
                'question' => 'I enjoy designing posters, branding materials, or promotional visuals.',
                'weight' => 3
            ],
            [
                'subfield_id' => 4,
                'question' => 'I enjoy expressing creative ideas through visual compositions.',
                'weight' => 2
            ],
            [
                'subfield_id' => 4,
                'question' => 'I enjoy editing images or creating digital illustrations.',
                'weight' => 2
            ],
            [
                'subfield_id' => 4,
                'question' => 'I enjoy selecting colors, typography, and visual styles.',
                'weight' => 1
            ],

            // =========================
            // Digital Marketing
            // =========================

            [
                'subfield_id' => 5,
                'question' => 'I enjoy creating strategies to promote products through digital platforms.',
                'weight' => 3
            ],
            [
                'subfield_id' => 5,
                'question' => 'I enjoy analyzing marketing campaign performance or engagement metrics.',
                'weight' => 3
            ],
            [
                'subfield_id' => 5,
                'question' => 'I enjoy writing engaging content for online audiences.',
                'weight' => 2
            ],
            [
                'subfield_id' => 5,
                'question' => 'I enjoy studying how businesses attract customers online.',
                'weight' => 2
            ],
            [
                'subfield_id' => 5,
                'question' => 'I enjoy managing social media accounts or online promotions.',
                'weight' => 1
            ],

            // =========================
            // Cybersecurity
            // =========================

            [
                'subfield_id' => 6,
                'question' => 'I enjoy learning how to protect systems from cyber attacks.',
                'weight' => 3
            ],
            [
                'subfield_id' => 6,
                'question' => 'I enjoy understanding how hackers exploit system vulnerabilities.',
                'weight' => 3
            ],
            [
                'subfield_id' => 6,
                'question' => 'I enjoy analyzing security risks in computer systems.',
                'weight' => 2
            ],
            [
                'subfield_id' => 6,
                'question' => 'I enjoy investigating suspicious digital activities.',
                'weight' => 2
            ],
            [
                'subfield_id' => 6,
                'question' => 'I enjoy learning techniques to secure networks and applications.',
                'weight' => 1
            ],
        ];


        // Likert Scale Options
        $options = [
            ['option_text' => 'Strongly Disagree', 'score' => 1],
            ['option_text' => 'Disagree', 'score' => 2],
            ['option_text' => 'Neutral', 'score' => 3],
            ['option_text' => 'Agree', 'score' => 4],
            ['option_text' => 'Strongly Agree', 'score' => 5],
        ];


        foreach ($questions as $q) {

            $questionId = DB::table('interest_questions')->insertGetId([
                'subfield_id' => $q['subfield_id'],
                'question' => $q['question'],
                'weight' => $q['weight'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($options as $opt) {
                DB::table('interest_options')->insert([
                    'question_id' => $questionId,
                    'option_text' => $opt['option_text'],
                    'score' => $opt['score'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
