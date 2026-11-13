<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ranking weights
    |--------------------------------------------------------------------------
    | Components whose data is unavailable for a period are removed and their
    | weight is redistributed proportionally among the remaining components.
    */
    'weights' => [
        'exams'      => (float) env('RANKING_WEIGHT_EXAMS', 0.40),
        'exercises'  => (float) env('RANKING_WEIGHT_EXERCISES', 0.25),
        'attendance' => (float) env('RANKING_WEIGHT_ATTENDANCE', 0.20),
        'manual'     => (float) env('RANKING_WEIGHT_MANUAL', 0.15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual points
    |--------------------------------------------------------------------------
    | Manual points are normalised across the students in the same ranking
    | scope. When every student has the same value, the neutral score is used.
    */
    'manual_points' => [
        'neutral_score' => (float) env('RANKING_MANUAL_NEUTRAL', 50),
    ],

];
