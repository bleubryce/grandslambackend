<?php

return [
    'statistical_analysis' => [
        'enabled' => true,
        'batch_size' => 1000,
        'confidence_level' => 0.95,
        'metrics' => [
            'correlation' => [
                'enabled' => true,
                'min_sample_size' => 30,
                'variables' => [
                    'batting' => ['avg', 'obp', 'slg', 'ops', 'war'],
                    'pitching' => ['era', 'whip', 'k_per_9', 'war'],
                    'fielding' => ['fielding_pct', 'range_factor']
                ]
            ],
            'regression' => [
                'enabled' => true,
                'min_r_squared' => 0.7,
                'models' => [
                    'linear' => true,
                    'polynomial' => true,
                    'multiple' => true
                ]
            ],
            'time_series' => [
                'enabled' => true,
                'window_sizes' => [7, 14, 30, 60],
                'analysis_types' => [
                    'trend' => true,
                    'seasonality' => true,
                    'moving_average' => true,
                    'exponential_smoothing' => true
                ]
            ]
        ]
    ],
    'predictive_modeling' => [
        'enabled' => true,
        'models' => [
            'player_performance' => [
                'enabled' => true,
                'features' => [
                    'historical_stats' => true,
                    'matchup_history' => true,
                    'park_factors' => true,
                    'weather_conditions' => true
                ],
                'algorithms' => [
                    'random_forest' => [
                        'enabled' => true,
                        'n_estimators' => 100,
                        'max_depth' => 10
                    ],
                    'gradient_boosting' => [
                        'enabled' => true,
                        'learning_rate' => 0.1,
                        'n_estimators' => 100
                    ],
                    'neural_network' => [
                        'enabled' => true,
                        'hidden_layers' => [64, 32],
                        'dropout_rate' => 0.2
                    ]
                ]
            ],
            'game_outcome' => [
                'enabled' => true,
                'features' => [
                    'team_stats' => true,
                    'player_stats' => true,
                    'head_to_head' => true,
                    'recent_form' => true
                ],
                'algorithms' => [
                    'logistic_regression' => [
                        'enabled' => true,
                        'regularization' => 'l2',
                        'max_iterations' => 1000
                    ],
                    'xgboost' => [
                        'enabled' => true,
                        'max_depth' => 6,
                        'learning_rate' => 0.1
                    ]
                ]
            ]
        ],
        'training' => [
            'test_size' => 0.2,
            'validation_size' => 0.1,
            'cross_validation_folds' => 5,
            'early_stopping_rounds' => 10
        ],
        'evaluation' => [
            'metrics' => [
                'mae' => true,
                'rmse' => true,
                'r2' => true,
                'accuracy' => true,
                'precision' => true,
                'recall' => true,
                'f1' => true
            ],
            'threshold' => 0.7
        ]
    ],
    'output' => [
        'format' => 'json',
        'storage' => [
            'type' => 'database',
            'cache_results' => true,
            'cache_ttl' => 3600
        ],
        'export' => [
            'enabled' => true,
            'formats' => ['json', 'csv'],
            'compression' => false
        ]
    ],
    'performance' => [
        'parallel_processing' => true,
        'max_threads' => 4,
        'batch_size' => 1000,
        'memory_limit' => '1G'
    ]
]; 