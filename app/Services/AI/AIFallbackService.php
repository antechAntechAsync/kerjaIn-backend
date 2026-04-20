<?php

namespace App\Services\AI;

use App\Contracts\AIServiceInterface;
use App\Services\GeminiAI\GeminiAIService;
use App\Services\GroqAI\GroqAIService;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * AI Fallback Service
 *
 * Tries Groq first as primary AI provider.
 * If Groq fails, falls back to Gemini.
 * Only throws exception if BOTH providers fail.
 */
class AIFallbackService implements AIServiceInterface
{
    public function __construct(
        protected GroqAIService $groq,
        protected GeminiAIService $gemini,
    ) {}

    public function generateInterestResponse(array $messages): string
    {
        return $this->tryWithFallback(
            fn () => $this->groq->generateInterestResponse($messages),
            fn () => $this->gemini->generateInterestResponse($messages),
            'generateInterestResponse'
        );
    }

    public function generateCareerRecommendation(array $roles): array
    {
        return $this->tryWithFallback(
            fn () => $this->groq->generateCareerRecommendation($roles),
            fn () => $this->gemini->generateCareerRecommendation($roles),
            'generateCareerRecommendation'
        );
    }

    public function generateKnowledgeQuestions(string $skillName): array
    {
        return $this->tryWithFallback(
            fn () => $this->groq->generateKnowledgeQuestions($skillName),
            fn () => $this->gemini->generateKnowledgeQuestions($skillName),
            'generateKnowledgeQuestions'
        );
    }

    public function generateHighDemandSkills(string $role, string $level): array
    {
        return $this->tryWithFallback(
            fn () => $this->groq->generateHighDemandSkills($role, $level),
            fn () => $this->gemini->generateHighDemandSkills($role, $level),
            'generateHighDemandSkills'
        );
    }

    /**
     * Try primary provider, fallback to secondary on failure.
     *
     * @template T
     * @param callable(): T $primary
     * @param callable(): T $fallback
     * @param string $method
     * @return T
     *
     * @throws Exception When both providers fail
     */
    protected function tryWithFallback(callable $primary, callable $fallback, string $method): mixed
    {
        // Try Groq (primary)
        try {
            $result = $primary();

            Log::debug("AI [{$method}]: Groq succeeded");

            return $result;
        } catch (Exception $groqError) {
            Log::warning("AI [{$method}]: Groq failed, trying Gemini fallback", [
                'error' => $groqError->getMessage(),
            ]);
        }

        // Try Gemini (fallback)
        try {
            $result = $fallback();

            Log::info("AI [{$method}]: Gemini fallback succeeded");

            return $result;
        } catch (Exception $geminiError) {
            Log::error("AI [{$method}]: Both Groq and Gemini failed", [
                'groq_error' => $groqError->getMessage(),
                'gemini_error' => $geminiError->getMessage(),
            ]);

            throw new Exception(
                "AI service unavailable. Both providers failed. Groq: {$groqError->getMessage()}, Gemini: {$geminiError->getMessage()}"
            );
        }
    }
}
