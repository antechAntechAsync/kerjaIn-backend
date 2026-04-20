<?php

namespace App\Services\Professional;

use App\Models\JobListing;
use Illuminate\Support\Facades\DB;

class JobService
{
    public function createJob(array $data, int $professionalId): JobListing
    {
        return DB::transaction(function () use ($data, $professionalId) {
            // 1. Create Job
            $job = JobListing::create([
                'professional_id' => $professionalId,
                'title' => $data['title'],
                'description' => $data['description'],
                'location' => $data['location'],
                'level' => $data['level'],
            ]);

            // 2. Handle required_skills → langsung insert ke table baru
            if (!empty($data['required_skills'])) {
                $requirements = [];

                foreach ($data['required_skills'] as $skillName) {
                    $requirements[] = [
                        'job_listing_id' => $job->id,
                        'name' => $skillName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                DB::table('job_skill_requirements')->insert($requirements);
            }

            return $job;
        });
    }

    public function getJobs(int $professionalId)
    {
        return JobListing::where('professional_id', $professionalId);
    }

    public function getJobById(int $id, int $professionalId)
    {
        return JobListing::where('id', $id)
            ->where('professional_id', $professionalId)
            ->first();
    }
}
