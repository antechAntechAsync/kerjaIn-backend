<?php

namespace App\Services\Student;

use App\Models\JobApplication;
use Exception;

class JobApplicationService
{
    public function apply(int $userId, int $jobId, ?string $coverLetter = null): JobApplication
    {
        // cek sudah pernah apply
        $exists = JobApplication::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->exists();

        if ($exists) {
            throw new Exception('You have already applied to this job');
        }

        return JobApplication::create([
            'user_id' => $userId,
            'job_id' => $jobId,
            'cover_letter' => $coverLetter,
            'status' => 'pending',
        ]);
    }
}
