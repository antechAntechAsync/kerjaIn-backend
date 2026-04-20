<?php

namespace App\Contracts;

interface AIServiceInterface
{
    /**
     * Generate an interactive interest assessment response.
     *
     * @param array $messages Conversation history [{sender, message}, ...]
     * @return string AI response text (question or FINAL_ROLE: <role>)
     */
    public function generateInterestResponse(array $messages): string;

    /**
     * Generate career recommendation with learning roadmap.
     *
     * @param array $roles List of career role names
     * @return array Array of career objects with roadmap nodes and resources
     */
    public function generateCareerRecommendation(array $roles): array;

    /**
     * Generate knowledge check questions for a specific skill.
     *
     * @param string $skillName The skill to generate questions for
     * @return array Array of question objects with options and correct answers
     */
    public function generateKnowledgeQuestions(string $skillName): array;

    /**
     * Generate a list of high-demand skills for a role and level.
     *
     * @param string $role Career role name
     * @param string $level Skill level (beginner, intermediate, advanced)
     * @return array Array of skill name strings
     */
    public function generateHighDemandSkills(string $role, string $level): array;
}
