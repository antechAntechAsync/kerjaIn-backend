<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dynamic validation rules based on user role.
     */
    public function rules(): array
    {
        $user = $this->user();
        $role = $user?->role;

        // Shared rules
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone_number' => ['required', 'string', 'min:10', 'max:15'],
            'industry' => ['required', 'string', 'min:2', 'max:100'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ];

        // Student-specific rules
        if ($role === 'student') {
            $rules['school_name'] = ['required', 'string', 'min:2', 'max:200'];
            $rules['bio'] = ['required', 'string', 'min:10', 'max:1000'];
            $rules['instagram_url'] = ['nullable', 'url', 'max:255'];
            $rules['youtube_url'] = ['nullable', 'url', 'max:255'];
            $rules['tiktok_url'] = ['nullable', 'url', 'max:255'];
        }

        // Professional-specific rules
        if ($role === 'professional' || $role === 'hr') {
            $rules['company_name'] = ['required', 'string', 'min:2', 'max:200'];
            $rules['social_media_links'] = ['nullable', 'array'];
            $rules['social_media_links.*'] = ['nullable', 'url', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'phone_number.required' => 'Phone number is required',
            'industry.required' => 'Industry/field is required',
            'school_name.required' => 'School name is required for students',
            'bio.required' => 'Bio/description is required for students',
            'bio.min' => 'Bio must be at least 10 characters',
            'company_name.required' => 'Company name is required for professionals',
        ];
    }
}
