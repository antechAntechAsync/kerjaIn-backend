<?php

namespace App\Http\Controllers\Student;

use App\Contracts\AIServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckQuestion;
use App\Models\RoadmapNode;
use App\Models\UserSkillScore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KnowledgeCheckController extends Controller
{
    use ApiResponse;

    protected const PASS_THRESHOLD = 85;
    protected const COOLDOWN_SECONDS = 300; // 5 minutes

    public function __construct(
        protected AIServiceInterface $aiService,
    ) {}

    /**
     * Start a self assessment for a roadmap node.
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'roadmap_node_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $nodeId = $request->roadmap_node_id;

        // Verify node belongs to user's active roadmap
        $activeRoadmap = $user->activeRoadmap?->load('roadmap.nodes');
        if (!$activeRoadmap) {
            return $this->notFoundResponse('No active roadmap found');
        }

        $node = $activeRoadmap->roadmap->nodes->firstWhere('id', $nodeId);
        if (!$node) {
            return $this->notFoundResponse('Roadmap node not found');
        }

        // Check if node is locked (previous node not completed)
        $previousNode = $activeRoadmap->roadmap->nodes
            ->where('order_index', '<', $node->order_index)
            ->sortByDesc('order_index')
            ->first();

        if ($previousNode) {
            $prevProgress = $previousNode->userProgress($user->id);
            if (!$prevProgress || !$prevProgress->is_completed) {
                return $this->forbiddenResponse('Previous node must be completed first');
            }
        }

        // Check cooldown — last FAILED attempt within 5 minutes
        $lastFailedAttempt = KnowledgeCheckAttempt::where('user_id', $user->id)
            ->where('roadmap_node_id', $nodeId)
            ->where('is_passed', false)
            ->latest()
            ->first();

        if ($lastFailedAttempt && $lastFailedAttempt->completed_at) {
            $cooldownEnd = Carbon::parse($lastFailedAttempt->completed_at)
                ->addSeconds(self::COOLDOWN_SECONDS);

            if (Carbon::now()->lt($cooldownEnd)) {
                $remaining = Carbon::now()->diffInSeconds($cooldownEnd);

                return $this->errorResponse(
                    'Assessment cooldown active. Please wait before retrying.',
                    429,
                    [
                        'retry_after' => $cooldownEnd->toIso8601String(),
                        'remaining_seconds' => (int) $remaining,
                    ],
                    'COOLDOWN_ACTIVE'
                );
            }
        }

        // Generate questions via AI
        $questions = $this->aiService->generateKnowledgeQuestions($node->skill_name);

        if (empty($questions)) {
            return $this->serverErrorResponse('Failed to generate assessment questions. Please try again.');
        }

        // Create attempt
        $attempt = KnowledgeCheckAttempt::create([
            'user_id' => $user->id,
            'roadmap_node_id' => $nodeId,
            'score' => 0,
            'is_passed' => false,
            'started_at' => now(),
        ]);

        // Store questions
        $formattedQuestions = [];
        foreach ($questions as $index => $q) {
            $question = KnowledgeCheckQuestion::create([
                'knowledge_check_attempt_id' => $attempt->id,
                'roadmap_node_id' => $nodeId,
                'question' => $q['question'],
                'topic' => $q['topic'] ?? $node->skill_name,
                'difficulty' => $q['difficulty'] ?? 'medium',
                'weight' => $q['weight'] ?? 2,
                'options' => $q['options'],
                'correct_answer' => $q['correct_answer'],
            ]);

            $formattedQuestions[] = [
                'id' => $question->id,
                'question' => $question->question,
                'options' => $question->options,
            ];
        }

        return $this->successResponse([
            'attempt_id' => $attempt->id,
            'skill_name' => $node->skill_name,
            'questions' => $formattedQuestions,
            'total_questions' => count($formattedQuestions),
            'pass_threshold' => self::PASS_THRESHOLD,
        ], 'Assessment started');
    }

    /**
     * Submit assessment answers.
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'attempt_id' => ['required', 'integer'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:knowledge_check_questions,id'],
            'answers.*.selected_answer' => ['required', 'in:A,B,C,D'],
        ]);

        $user = $request->user();
        $attempt = KnowledgeCheckAttempt::where('user_id', $user->id)
            ->where('id', $request->attempt_id)
            ->whereNull('completed_at')
            ->firstOrFail();

        $questions = KnowledgeCheckQuestion::where('knowledge_check_attempt_id', $attempt->id)
            ->get()
            ->keyBy('id');

        // Check for duplicate answers
        $questionIds = collect($request->answers)->pluck('question_id');
        if ($questionIds->count() !== $questionIds->unique()->count()) {
            return $this->validationErrorResponse(['answers' => ['Duplicate answers detected']]);
        }

        // Calculate score (weighted)
        $totalWeight = 0;
        $earnedWeight = 0;
        $totalCorrect = 0;

        foreach ($request->answers as $answer) {
            $question = $questions->get($answer['question_id']);
            if (!$question) continue;

            $weight = $question->weight ?? 1;
            $totalWeight += $weight;

            if ($answer['selected_answer'] === $question->correct_answer) {
                $earnedWeight += $weight;
                $totalCorrect++;
            }
        }

        $score = $totalWeight > 0 ? (int) round(($earnedWeight / $totalWeight) * 100) : 0;
        $isPassed = $score >= self::PASS_THRESHOLD;

        // Update attempt
        $attempt->update([
            'score' => $score,
            'is_passed' => $isPassed,
            'completed_at' => now(),
            'feedback' => $isPassed
                ? "Excellent understanding of {$attempt->node->skill_name ?? 'this skill'}! You scored {$score}%."
                : "Keep learning {$attempt->node->skill_name ?? 'this skill'}. Review the resources and try again. You scored {$score}%.",
        ]);

        // If passed: update user skill score and unlock next node
        $nextNode = null;
        if ($isPassed) {
            $node = RoadmapNode::find($attempt->roadmap_node_id);
            if ($node) {
                // Update/create skill score
                UserSkillScore::updateOrCreate(
                    ['user_id' => $user->id, 'skill_name' => strtolower($node->skill_name)],
                    [
                        'score' => max($score, UserSkillScore::where('user_id', $user->id)
                            ->where('skill_name', strtolower($node->skill_name))
                            ->value('score') ?? 0),
                        'source' => 'self_assessment',
                        'roadmap_node_id' => $node->id,
                    ]
                );

                // Find next node
                $nextNode = RoadmapNode::where('roadmap_id', $node->roadmap_id)
                    ->where('order_index', '>', $node->order_index)
                    ->orderBy('order_index')
                    ->first();
            }
        }

        $response = [
            'is_passed' => $isPassed,
            'score' => $score,
            'total_correct' => $totalCorrect,
            'total_questions' => $questions->count(),
            'feedback' => $attempt->feedback,
            'skill_name' => $attempt->node?->skill_name,
        ];

        if ($isPassed && $nextNode) {
            $response['next_node'] = [
                'id' => $nextNode->id,
                'skill_name' => $nextNode->skill_name,
                'is_locked' => false,
            ];
        } elseif (!$isPassed) {
            $retryAfter = Carbon::now()->addSeconds(self::COOLDOWN_SECONDS);
            $response['retry_after'] = $retryAfter->toIso8601String();
            $response['remaining_seconds'] = self::COOLDOWN_SECONDS;
        }

        $message = $isPassed
            ? 'Congratulations! You passed the assessment'
            : 'Assessment not passed. Keep learning!';

        return $this->successResponse($response, $message);
    }

    /**
     * Get all assessment history.
     */
    public function history(Request $request): JsonResponse
    {
        $attempts = KnowledgeCheckAttempt::where('user_id', $request->user()->id)
            ->with('node')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->paginate($request->input('per_page', 15));

        $data = $attempts->through(fn ($a) => [
            'id' => $a->id,
            'skill_name' => $a->node?->skill_name,
            'roadmap_node_id' => $a->roadmap_node_id,
            'score' => $a->score,
            'total_correct' => null, // not stored, computed at submit time
            'total_questions' => $a->questions()->count(),
            'is_passed' => $a->is_passed,
            'completed_at' => $a->completed_at,
        ]);

        return $this->paginatedResponse($data, 'Assessment history retrieved');
    }

    /**
     * Get assessment history for a specific node.
     */
    public function nodeHistory(Request $request, int $nodeId): JsonResponse
    {
        $user = $request->user();
        $node = RoadmapNode::findOrFail($nodeId);

        $attempts = KnowledgeCheckAttempt::where('user_id', $user->id)
            ->where('roadmap_node_id', $nodeId)
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->get();

        $bestScore = $attempts->max('score');

        return $this->successResponse([
            'node' => [
                'id' => $node->id,
                'skill_name' => $node->skill_name,
                'is_completed' => $bestScore !== null && $bestScore >= self::PASS_THRESHOLD,
                'best_score' => $bestScore,
            ],
            'attempts' => $attempts->map(fn ($a) => [
                'id' => $a->id,
                'score' => $a->score,
                'is_passed' => $a->is_passed,
                'feedback' => $a->feedback,
                'completed_at' => $a->completed_at,
            ]),
        ], 'Node assessment history retrieved');
    }
}
