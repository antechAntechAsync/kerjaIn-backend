<?php

namespace App\Services\Student;

use App\Helper\FormatterRole;
use App\Models\CareerRecommendation;
use App\Models\CareerRole;
use App\Models\InterestMessage;
use App\Models\InterestOption;
use App\Models\InterestResult;
use App\Models\InterestSession;
use App\Services\GroqAI\GroqAIService;

use function count;

use Exception;

use function strlen;

class InterestAssessmentService
// {

//     protected $scoring;
//     protected $ai;
//     protected $roadmapBuilder;

//     public function __construct(
//         InterestScoringService $scoring,
//         GroqAIService $ai,
//         RoadmapBuilderService $roadmapBuilder
//     ) {
//         $this->scoring = $scoring;
//         $this->ai = $ai;
//         $this->roadmapBuilder = $roadmapBuilder;
//     }

//     public function processAssessment($studentId, $answers)
//     {

//         InterestResult::where('user_id', $studentId)->delete();
//         CareerRecommendation::where('user_id', $studentId)->delete();

//         $this->roadmapBuilder->clearUserRoadmaps($studentId);

//         foreach ($answers as $questionId => $optionId) {

//             $option = InterestOption::find($optionId);

//             if (!$option) {
//                 continue;
//             }

//             InterestResult::create([
//                 'user_id' => $studentId,
//                 'question_id' => $questionId,
//                 'option_id' => $optionId,
//                 'score' => $option->score
//             ]);
//         }

//         // hitung top subfields
//         $subfields = $this->scoring->calculateTopSubfields($answers);

//         if ($subfields->isEmpty()) {
//             return;
//         }

//         // ambil hanya subfield tertinggi
//         $topSubfield = $subfields->first();

//         // ambil maksimal 3 career dari subfield tersebut
//         $roles = CareerRole::where('subfield_id', $topSubfield->id)
//             ->limit(3)
//             ->get();

//         $roleNames = $roles->pluck('name')->toArray();

//         // generate roadmap
//         $recommendations = $this->ai->generateCareerRecommendation($roleNames);

//         foreach ($recommendations as $rec) {

//             if (!isset($rec['role'])) {
//                 continue;
//             }

//             CareerRecommendation::create([
//                 'user_id' => $studentId,
//                 'career_role' => $rec['role'],
//                 'roadmap' => $rec['roadmap']
//             ]);

//             $this->roadmapBuilder->build(
//                 $studentId,
//                 $rec['role'],
//                 $rec['roadmap']
//             );
//         }
//     }
// }
{
    protected $ai;

    public function __construct(GroqAIService $ai)
    {
        $this->ai = $ai;
    }

    // | START SESSION
    public function startSession($userId)
    {
        // reset session lama
        InterestSession::where('user_id', $userId)
            ->where('status', 'in_progress')
            ->update(['status' => 'abandoned']);

        $session = InterestSession::create([
            'user_id' => $userId,
            'status' => 'in_progress',
        ]);

        // pertanyaan awal (biar AI tidak kosong)
        $initialMessages = [];

        $response = $this->ai->generateInterestResponse($initialMessages);

        InterestMessage::create([
            'session_id' => $session->id,
            'sender' => 'ai',
            'message' => $response,
        ]);

        return [
            'session_id' => $session->id,
            'question' => $response,
        ];
    }

    // | SEND ANSWER
    public function sendAnswer($sessionId, $answer)
    {
        $session = InterestSession::findOrFail($sessionId);

        if ($session->status === 'completed') {
            throw new Exception('Session already completed');
        }

        // simpan jawaban user
        InterestMessage::create([
            'session_id' => $sessionId,
            'sender' => 'user',
            'message' => $answer,
        ]);

        // ambil history
        $messages = InterestMessage::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'sender' => $m->sender,
                'message' => $m->message,
            ])
            ->toArray();

        // batasi max 10 pertanyaan (20 message: ai + user)
        if (count($messages) >= 20) {
            $messages[] = [
                'sender' => 'user',
                'message' => 'Please conclude and provide FINAL_ROLE now.',
            ];
        }

        $response = $this->ai->generateInterestResponse($messages);

        // cek apakah final role
        if (str_contains($response, 'FINAL_ROLE:')) {
            preg_match('/FINAL_ROLE:\s*(.*)/', $response, $matches);

            $role = $matches[1] ?? null;

            $role = FormatterRole::normalizeRole($role);

            $role = explode('.', $role)[0];

            $role = trim($role);

            if (!$role || strlen($role) > 150) {
                throw new Exception('Invalid role from AI: ' . $response);
            }

            $session->update([
                'status' => 'completed',
                'result_role' => $role,
            ]);

            $careers = app(GroqAIService::class)
                ->generateCareerRecommendation([$role]);

            foreach ($careers as $career) {
                app(RoadmapBuilderService::class)
                    ->build(
                        $session->user_id,
                        $career['role'],
                        $career['roadmap'],
                    );
            }

            InterestMessage::create([
                'session_id' => $sessionId,
                'sender' => 'ai',
                'message' => $response,
            ]);

            return [
                'completed' => true,
                'role' => $role,
            ];
        }

        InterestMessage::create([
            'session_id' => $sessionId,
            'sender' => 'ai',
            'message' => $response,
        ]);

        return [
            'completed' => false,
            'question' => $response,
        ];
    }
}
