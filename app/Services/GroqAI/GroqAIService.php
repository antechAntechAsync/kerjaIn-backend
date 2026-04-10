<?php

namespace App\Services\GroqAI;

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

        - A short description explaining the ability someone should have for that skill
        - 1 high quality learning resource for each skill that is publicly available and accessible

        For EACH career role generate:

        - EXACTLY 5 skills

        STRICT RULES FOR SKILLS:
        - Skill MUST be a short keyword (1–2 words MAX)
        - DO NOT include explanations inside skill name
        - DO NOT use phrases longer than 3 words
        - Skill must be reusable across different jobs
        - Skill must be commonly used in job requirements

        GOOD examples:
        - Node.js
        - REST API
        - Docker
        - OAuth
        - MongoDB

        BAD examples:
        - Building REST API with Node.js
        - Authentication and authorization with OAuth 2.0
        - API design with scalable architecture

        Descriptions:
        - Explain practical ability (1–2 sentences)
        - Do NOT repeat skill name

        For EACH skill provide:
        - Each skill must be relevant to current industry demand (2025+)
        - Avoid outdated technologies unless foundational

        Normalize skill naming:
        - Use lowercase
        - Remove unnecessary words like 'with', 'and', 'using'
        - Prefer standard industry naming


        The description must describe:
        - what someone should be able to do with the skill
        - practical capability, not a dictionary definition
        - keep it concise (1-2 sentence)

        Example:

        Skill: SEO Optimization
        Description: Ability to optimize website content and structure to improve search engine rankings using basic SEO techniques.

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
                \"description\": \"Short explanation of the skill\",
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

        $content = preg_replace('/```json|```/', '', $content);
        $content = trim($content);

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

    public function generateInterestResponse(array $messages)
    {
        $systemPrompt = "
        You are a professional career consultant AI.

        Your task is to ask questions to determine the user's most suitable career role.

        Rules:
        - Ask only ONE question at a time
        - Maximum 10 questions
        - Be adaptive based on previous answers
        - Consider student's major and interest
        - Consider industry demand

        If you already have enough information, STOP asking questions and return ONLY:

        CRITICAL RULE:
        - OUTPUT MUST BE EXACTLY IN THIS FORMAT:
        FINAL_ROLE: <role name>

        - NO additional sentences
        - NO explanation
        - NO punctuation after role
        - If you add anything else, the response is INVALID
        ";

        $formattedMessages = [
            [
                "role" => "system",
                "content" => $systemPrompt
            ]
        ];

        foreach ($messages as $msg) {
            $formattedMessages[] = [
                "role" => $msg['sender'] === 'user' ? 'user' : 'assistant',
                "content" => $msg['message']
            ];
        }

        $response = Http::timeout(20)
            ->retry(2, 2000)
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type' => 'application/json'
            ])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                "model" => "llama-3.3-70b-versatile",
                "messages" => $formattedMessages
            ]);

        $data = $response->json();

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid AI response');
        }

        return trim($data['choices'][0]['message']['content']);
    }


    public function generateHighDemandSkills($role, $level)
    {
        $prompt = "
    You are an industry expert.

    Based on this role:
    $role

    And skill level:
    $level

    Generate 3-5 HIGH DEMAND skills in current industry (2025+).

    Rules:
    - Only skill names
    - No explanation
    - Return JSON array

    Example:
    [\"Docker\", \"Redis\", \"Microservices\"]
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
            throw new \Exception('Invalid AI response');
        }

        $content = trim($data['choices'][0]['message']['content']);

        // 🔧 bersihin kalau AI nakal (kadang kasih ```json)
        $content = preg_replace('/```json|```/', '', $content);
        $content = trim($content);

        // 🔧 ambil hanya array JSON
        $start = strpos($content, '[');
        $end = strrpos($content, ']');

        if ($start !== false && $end !== false) {
            $content = substr($content, $start, $end - $start + 1);
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function generateKnowledgeQuestions($skillName)
    {
        $prompt = "
    You are a technical interviewer.

    Create 5 multiple choice questions for the skill:
    $skillName

    Rules:
    - Each question must test practical understanding
    - 4 options (A, B, C, D)
    - Only ONE correct answer
    - Avoid trivial definition questions

    Also include:
    - difficulty: easy / medium / hard
    - weight:
        easy = 1
        medium = 2
        hard = 3

    Return STRICT JSON:

    [
      {
        \"question\": \"...\",
        \"topic\": \"$skillName\",
        \"difficulty\": \"medium\",
        \"weight\": 2,
        \"options\": {
          \"A\": \"...\",
          \"B\": \"...\",
          \"C\": \"...\",
          \"D\": \"...\"
        },
        \"correct_answer\": \"A\"
      }
    ]

    NO explanation. Only JSON.
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
                    ["role" => "user", "content" => $prompt]
                ]
            ]);

        $content = $response->json()['choices'][0]['message']['content'] ?? '';

        // 🔧 Clean JSON (anti AI nakal)
        $content = preg_replace('/```json|```/', '', $content);
        $content = trim($content);

        $start = strpos($content, '[');
        $end = strrpos($content, ']');

        if ($start !== false && $end !== false) {
            $content = substr($content, $start, $end - $start + 1);
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            Log::error("AI Question JSON Invalid", ['content' => $content]);
            return [];
        }

        return $decoded;
    }
}
