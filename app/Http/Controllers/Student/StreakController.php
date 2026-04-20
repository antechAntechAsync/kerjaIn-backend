<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\DailyCheckin;
use App\Models\DailyStreak;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreakController extends Controller
{
    use ApiResponse;

    /**
     * Get current streak info.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $streak = $user->dailyStreak ?? DailyStreak::create([
            'user_id' => $user->id,
            'current_streak' => 0,
            'longest_streak' => 0,
        ]);

        return $this->successResponse([
            'current_streak' => $streak->current_streak,
            'longest_streak' => $streak->longest_streak,
            'last_checkin_date' => $streak->last_checkin_date?->toDateString(),
            'checked_in_today' => $streak->last_checkin_date?->isToday() ?? false,
        ], 'Streak info retrieved');
    }

    /**
     * Daily check-in with activity description.
     */
    public function checkin(Request $request): JsonResponse
    {
        $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $user = $request->user();
        $today = Carbon::today();

        // Check if already checked in today
        $alreadyCheckedIn = DailyCheckin::where('user_id', $user->id)
            ->where('checkin_date', $today->toDateString())
            ->exists();

        if ($alreadyCheckedIn) {
            return $this->errorResponse(
                'You have already checked in today',
                400,
                errorCode: 'ALREADY_CHECKED_IN'
            );
        }

        // Create check-in record
        $checkin = DailyCheckin::create([
            'user_id' => $user->id,
            'description' => $request->description,
            'checkin_date' => $today->toDateString(),
        ]);

        // Update streak
        $streak = DailyStreak::firstOrCreate(
            ['user_id' => $user->id],
            ['current_streak' => 0, 'longest_streak' => 0]
        );

        $yesterday = Carbon::yesterday();

        if ($streak->last_checkin_date?->equalTo($yesterday)) {
            // Consecutive day → increment
            $streak->current_streak += 1;
        } elseif ($streak->last_checkin_date?->isToday()) {
            // Same day (shouldn't reach here, but safety)
        } else {
            // Streak broken or first check-in → reset to 1
            $streak->current_streak = 1;
        }

        // Update longest streak
        if ($streak->current_streak > $streak->longest_streak) {
            $streak->longest_streak = $streak->current_streak;
        }

        $streak->last_checkin_date = $today;
        $streak->save();

        return $this->createdResponse([
            'current_streak' => $streak->current_streak,
            'longest_streak' => $streak->longest_streak,
            'checked_in_today' => true,
            'checkin' => [
                'id' => $checkin->id,
                'description' => $checkin->description,
                'checkin_date' => $checkin->checkin_date->toDateString(),
            ],
        ], 'Check-in successful! Keep up the streak!');
    }

    /**
     * Get check-in history.
     */
    public function history(Request $request): JsonResponse
    {
        $checkins = DailyCheckin::where('user_id', $request->user()->id)
            ->orderByDesc('checkin_date')
            ->paginate($request->input('per_page', 15));

        return $this->paginatedResponse($checkins, 'Check-in history retrieved');
    }
}
