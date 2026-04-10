<?php

namespace App\Services;

use App\Models\AssessmentSession;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckQuestion;
use App\Models\RoadmapNode;
use App\Models\UserRoadmap;

use function count;

class KnowledgeCheckService
{
    protected $ai;

    public function __construct(GroqAIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Ambil pertanyaan.
     */
    public function getQuestions($userId)
    {
        $roadmapId = UserRoadmap::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->value('roadmap_id');

        if (!$roadmapId) {
            return [];
        }

        $nodes = RoadmapNode::where('roadmap_id', $roadmapId)
            ->orderBy('order_index')
            ->take(3)
            ->get();

        $allQuestions = collect();

        foreach ($nodes as $node) {
            $existing = KnowledgeCheckQuestion::where('roadmap_node_id', $node->id)
                ->get()
                ->groupBy('difficulty');

            $questions = collect()
                ->merge($existing['easy'] ?? collect())
                ->merge($existing['medium'] ?? collect())
                ->merge($existing['hard'] ?? collect())
                ->shuffle()
                ->take(5);

            if ($questions->count() < 5) {
                $generated = $this->ai->generateKnowledgeQuestions($node->skill_name);

                foreach ($generated as $q) {
                    if (!isset($q['question'], $q['options'], $q['correct_answer'])) {
                        continue;
                    }

                    $saved = KnowledgeCheckQuestion::create([
                        'roadmap_node_id' => $node->id,
                        'question' => $q['question'],
                        'topic' => $q['topic'] ?? $node->skill_name,
                        'options' => $q['options'],
                        'correct_answer' => $q['correct_answer'],
                        'difficulty' => $q['difficulty'] ?? 'medium',
                        'weight' => $q['weight'] ?? 2,
                    ]);

                    $questions->push($saved);

                    if ($questions->count() >= 5) {
                        break;
                    }
                }
            }

            $allQuestions = $allQuestions->merge($questions->take(5));
        }

        return $allQuestions->map(function ($q) {
            return [
                'id' => $q->id,
                'roadmap_node_id' => $q->roadmap_node_id,
                'question' => $q->question,
                'options' => $q->options,
            ];
        })->values();
    }

    /**
     * Submit jawaban.
     */
    public function submit($userId, $answers)
    {
        $roadmapId = UserRoadmap::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->value('roadmap_id');

        if (empty($answers)) {
            return [
                'score' => 0,
                'level' => 'beginner',
                'correct' => 0,
                'total' => 0,
                'skill_breakdown' => [],
            ];
        }

        $session = AssessmentSession::create([
            'roadmap_id' => $roadmapId,
            'user_id' => $userId,
            'type' => 'knowledge_check',
        ]);

        $correct = 0;
        $total = count($answers);

        $totalWeight = 0;
        $earnedWeight = 0;

        $perNodeScore = [];

        foreach ($answers as $ans) {
            $question = KnowledgeCheckQuestion::find($ans['question_id']);

            if (!$question) {
                continue;
            }

            $isCorrect = $ans['selected_answer'] == $question->correct_answer;

            if ($isCorrect) {
                $correct++;
            }

            $weight = $question->weight ?? 1;

            $totalWeight += $weight;

            if ($isCorrect) {
                $earnedWeight += $weight;
            }

            //  per skill tracking
            $nodeId = $question->roadmap_node_id;

            if (!isset($perNodeScore[$nodeId])) {
                $perNodeScore[$nodeId] = [
                    'earned' => 0,
                    'total' => 0,
                ];
            }

            $perNodeScore[$nodeId]['total'] += $weight;

            if ($isCorrect) {
                $perNodeScore[$nodeId]['earned'] += $weight;
            }

            KnowledgeCheckAttempt::create([
                'session_id' => $session->id,
                'question_id' => $question->id,
                'selected_answer' => $ans['selected_answer'],
                'is_correct' => $isCorrect,
            ]);
        }

        //  final weighted score
        $score = $totalWeight > 0
            ? ($earnedWeight / $totalWeight) * 100
            : 0;

        //  level mapping
        $level = match (true) {
            $score >= 80 => 'advanced',
            $score >= 50 => 'intermediate',
            default => 'beginner'
        };

        //  breakdown per skill
        $skillBreakdown = collect($perNodeScore)->map(function ($data, $nodeId) {
            return [
                'roadmap_node_id' => $nodeId,
                'score' => $data['total'] > 0
                    ? round(($data['earned'] / $data['total']) * 100)
                    : 0,
            ];
        })->values();

        return [
            'score' => round($score),
            'level' => $level,
            'correct' => $correct,
            'total' => $total,
            'skill_breakdown' => $skillBreakdown,
        ];
    }
}
