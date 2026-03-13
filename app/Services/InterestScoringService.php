<?php

namespace App\Services;

use App\Models\InterestOption;
use App\Models\InterestQuestion;
use App\Models\InterestSubfield;

class InterestScoringService
{

    public function calculateTopSubfields($answers)
{
    $scores = [];
    $weights = [];

    foreach ($answers as $questionId => $optionId) {

        $option = InterestOption::find($optionId);
        if (!$option) {
            continue;
        }

        $question = InterestQuestion::find($questionId);
        if (!$question) {
            continue;
        }

        $subfieldId = $question->subfield_id;
        $weight = $question->weight ?? 1;

        if (!isset($scores[$subfieldId])) {
            $scores[$subfieldId] = 0;
            $weights[$subfieldId] = 0;
        }

        $scores[$subfieldId] += $option->score * $weight;
        $weights[$subfieldId] += $weight;
    }

    // NORMALIZATION (average score)
    foreach ($scores as $subfieldId => $score) {
        if ($weights[$subfieldId] > 0) {
            $scores[$subfieldId] = $score / $weights[$subfieldId];
        }
    }

    arsort($scores);

    $topSubfields = array_slice(array_keys($scores), 0, 3);

    return InterestSubfield::whereIn('id', $topSubfields)
        ->orderByRaw("FIELD(id," . implode(',', $topSubfields) . ")")
        ->get();
}
}
