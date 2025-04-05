<?php

return [
    'player_stats' => [
        'enabled' => true,
        'batch_size' => 1000,
        'metrics' => [
            'batting' => [
                'avg' => true,
                'obp' => true,
                'slg' => true,
                'ops' => true,
                'woba' => true,
                'iso' => true,
                'babip' => true
            ],
            'pitching' => [
                'era' => true,
                'whip' => true,
                'fip' => true,
                'k_per_9' => true,
                'bb_per_9' => true,
                'hr_per_9' => true,
                'k_per_bb' => true
            ],
            'advanced' => [
                'war' => true,
                'wrc_plus' => true,
                'ops_plus' => true,
                'era_plus' => true
            ]
        ],
        'aggregation_periods' => [
            'game' => true,
            'last_7_days' => true,
            'last_30_days' => true,
            'season' => true,
            'career' => true
        ]
    ],
    'team_stats' => [
        'enabled' => true,
        'batch_size' => 500,
        'metrics' => [
            'offense' => [
                'runs_per_game' => true,
                'batting_avg' => true,
                'on_base_pct' => true,
                'slugging_pct' => true,
                'ops' => true
            ],
            'pitching' => [
                'era' => true,
                'whip' => true,
                'k_per_9' => true,
                'bb_per_9' => true,
                'hr_per_9' => true
            ],
            'defense' => [
                'fielding_pct' => true,
                'defensive_efficiency' => true
            ],
            'advanced' => [
                'run_differential' => true,
                'pythagorean_win_pct' => true
            ]
        ],
        'aggregation_periods' => [
            'game' => true,
            'last_7_days' => true,
            'last_30_days' => true,
            'season' => true
        ]
    ],
    'game_stats' => [
        'enabled' => true,
        'batch_size' => 100,
        'metrics' => [
            'game_score' => true,
            'win_probability' => true,
            'leverage_index' => true,
            'run_expectancy' => true
        ],
        'analysis' => [
            'situational_splits' => true,
            'matchup_analysis' => true,
            'pitch_sequences' => true
        ]
    ],
    'performance_metrics' => [
        'enabled' => true,
        'batch_size' => 200,
        'metrics' => [
            'player_trends' => true,
            'team_trends' => true,
            'matchup_analysis' => true,
            'park_factors' => true
        ],
        'analysis_periods' => [
            'rolling_7_days' => true,
            'rolling_30_days' => true,
            'season_to_date' => true
        ]
    ],
    'processing_rules' => [
        'min_plate_appearances' => 3,
        'min_innings_pitched' => 1,
        'exclude_incomplete_games' => true,
        'handle_missing_data' => 'skip', // Options: skip, interpolate, default
        'data_validation' => [
            'enabled' => true,
            'strict_mode' => false
        ],
        'error_handling' => [
            'max_errors_per_batch' => 50,
            'stop_on_critical' => true
        ]
    ],
    'output' => [
        'format' => 'json',
        'compression' => false,
        'storage' => [
            'type' => 'database',
            'cache_results' => true,
            'cache_ttl' => 3600
        ]
    ]
]; 