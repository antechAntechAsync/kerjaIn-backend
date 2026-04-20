<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            if (empty($googleUser->email)) {
                return redirect($frontendUrl . '/auth/callback?error=' . urlencode('No email returned from Google account.'));
            }

            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if (!$user) {
                // New user via Google — defaults to student, needs profile completion
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'provider' => 'google',
                    'avatar' => $googleUser->avatar,
                    'password' => bcrypt(Str::random(24)),
                    'role' => 'student',
                    'is_profile_completed' => false,
                ]);
            } else {
                // Existing user — link Google account if needed
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'provider' => 'google',
                    ]);
                }

                // Update avatar from Google if user doesn't have one
                if (!$user->avatar && $googleUser->avatar) {
                    $user->update(['avatar' => $googleUser->avatar]);
                }
            }

            // Revoke old tokens
            $user->tokens()->delete();

            // Generate new Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;
            $isProfileCompleted = $user->is_profile_completed ? '1' : '0';

            return redirect(
                $frontendUrl . '/auth/callback?token=' . urlencode($token) .
                '&is_profile_completed=' . $isProfileCompleted
            );
        } catch (Exception $e) {
            Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            return redirect($frontendUrl . '/auth/callback?error=' . urlencode('Authentication failed. Please try again.'));
        }
    }
}
