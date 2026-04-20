<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteProfileRequest;
use App\Http\Traits\ApiResponse;
use App\Models\ProfessionalProfile;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * Complete profile after registration (mandatory step).
     */
    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->is_profile_completed) {
            return $this->errorResponse('Profile already completed. Use PUT /profile to update.', 400);
        }

        $validated = $request->validated();

        // Update shared user fields
        $user->update([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'industry' => $validated['industry'],
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'is_profile_completed' => true,
        ]);

        // Create role-specific profile
        if ($user->isStudent()) {
            StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_name' => $validated['school_name'],
                    'bio' => $validated['bio'],
                    'instagram_url' => $validated['instagram_url'] ?? null,
                    'youtube_url' => $validated['youtube_url'] ?? null,
                    'tiktok_url' => $validated['tiktok_url'] ?? null,
                ]
            );
        } elseif ($user->isProfessional()) {
            ProfessionalProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $validated['company_name'],
                    'social_media_links' => $validated['social_media_links'] ?? null,
                ]
            );
        }

        $user->load(['studentProfile', 'professionalProfile']);

        return $this->successResponse([
            'user' => $user,
            'is_profile_completed' => true,
        ], 'Profile completed successfully');
    }

    /**
     * Get own profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = null;

        if ($user->isStudent()) {
            $profile = $user->studentProfile;
        } elseif ($user->isProfessional()) {
            $profile = $user->professionalProfile;
        }

        return $this->successResponse([
            'user' => $user,
            'profile' => $profile,
        ], 'Profile retrieved successfully');
    }

    /**
     * Update own profile.
     */
    public function update(CompleteProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Update shared fields
        $userFields = array_intersect_key($validated, array_flip([
            'name', 'phone_number', 'industry', 'linkedin_url',
        ]));
        $user->update($userFields);

        // Update role-specific profile
        if ($user->isStudent() && $user->studentProfile) {
            $profileFields = array_intersect_key($validated, array_flip([
                'school_name', 'bio', 'instagram_url', 'youtube_url', 'tiktok_url',
            ]));
            $user->studentProfile->update($profileFields);
        } elseif ($user->isProfessional() && $user->professionalProfile) {
            $profileFields = array_intersect_key($validated, array_flip([
                'company_name', 'social_media_links',
            ]));
            $user->professionalProfile->update($profileFields);
        }

        $user->load(['studentProfile', 'professionalProfile']);

        return $this->successResponse([
            'user' => $user,
            'profile' => $user->isStudent() ? $user->studentProfile : $user->professionalProfile,
        ], 'Profile updated successfully');
    }

    /**
     * Upload avatar/profile photo.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return $this->successResponse([
            'avatar_url' => Storage::disk('public')->url($path),
        ], 'Avatar uploaded successfully');
    }
}
