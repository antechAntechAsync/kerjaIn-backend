<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqAIService
{

    public function generateCareerRecommendation(array $roles)
    {

        $roleList = implode(", ", $roles);

        $prompt = "
You are an AI career advisor.

Generate a learning roadmap for the following careers:

$roleList

For EACH career role generate:

- 5 important skills to learn
- 1 learning resource for each skill

Resources must come from real learning platforms such as:
YouTube, Coursera, freeCodeCamp, MDN, W3Schools, official documentation.

Return STRICT JSON in this format:

{
  \"careers\": [
    {
      \"role\": \"Career Name\",
      \"roadmap\": [
        {
          \"skill\": \"Skill name\",
          \"resources\": [
            {
              \"title\": \"Resource title\",
              \"url\": \"https://example.com\",
              \"type\": \"video/article/documentation/course\"
            }
          ]
        }
      ]
    }
  ]
}

Rules:
- Do NOT add explanations
- Do NOT wrap JSON in markdown
- Ensure valid JSON
- URLs must be inside double quotes
- Only return JSON
";

        $response = Http::timeout(20)
            ->retry(2, 2000)
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type' => 'application/json'
            ])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                "model" => "llama-3.3-70b-versatile",
                "messages" => [
                    [
                        "role" => "user",
                        "content" => $prompt
                    ]
                ]
            ]);

        $data = $response->json();

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid response from Groq: ' . json_encode($data));
        }

        $content = $data['choices'][0]['message']['content'];

        $content = trim($content);

        /*
        |--------------------------------------------------------------------------
        | Clean AI Response
        |--------------------------------------------------------------------------
        */

        $content = preg_replace('/```json|```/', '', $content);
        $content = trim($content);

        /*
        |--------------------------------------------------------------------------
        | Extract JSON if extra text exists
        |--------------------------------------------------------------------------
        */

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start !== false && $end !== false) {
            $content = substr($content, $start, $end - $start + 1);
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            Log::error("Groq JSON Error: " . json_last_error_msg());
            Log::error($content);

            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Handle JSON structure
        |--------------------------------------------------------------------------
        */

        if (isset($decoded['careers'])) {
            return $decoded['careers'];
        }

        if (isset($decoded[0])) {
            return $decoded;
        }

        return [];
    }
}
