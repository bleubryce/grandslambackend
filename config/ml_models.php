<?php

return [
    'model_path' => __DIR__ . '/../storage/models',
    
    'models' => [
        'player_performance' => [
            'algorithms' => [
                'random_forest' => [
                    'enabled' => true,
                    'numTrees' => 100,
                    'numFeatures' => null // auto-select based on data
                ],
                'svr' => [
                    'enabled' => true,
                    'kernel' => 'rbf',
                    'degree' => 3
                ],
                'least_squares' => [
                    'enabled' => true
                ]
            ],
            'min_samples' => [
                'batting' => 300, // minimum plate appearances
                'pitching' => 50  // minimum innings pitched
            ]
        ],
        
        'game_outcome' => [
            'algorithms' => [
                'random_forest' => [
                    'enabled' => true,
                    'numTrees' => 100,
                    'numFeatures' => null
                ],
                'logistic_regression' => [
                    'enabled' => true,
                    'cost' => 1.0,
                    'kernel' => 'rbf'
                ]
            ],
            'min_samples' => 30 // minimum games for team stats
        ]
    ],

    'training' => [
        'validation_split' => 0.2,
        'cross_validation_folds' => 5,
        'max_epochs' => 100,
        'early_stopping' => [
            'enabled' => true,
            'patience' => 5,
            'min_delta' => 0.001
        ]
    ],

    'evaluation' => [
        'metrics' => [
            'regression' => [
                'mse',
                'rmse',
                'mae',
                'r2'
            ],
            'classification' => [
                'accuracy',
                'precision',
                'recall',
                'f1_score'
            ]
        ],
        'threshold' => [
            'r2_min' => 0.6,
            'accuracy_min' => 0.7
        ]
    ],

    'prediction' => [
        'ensemble_method' => 'weighted_average',
        'confidence_threshold' => 0.7,
        'max_prediction_horizon' => 365 // days
    ],

    'persistence' => [
        'save_format' => 'binary',
        'version_control' => true,
        'max_versions' => 5,
        'compression' => true
    ],

    'optimization' => [
        'batch_size' => 32,
        'parallel_training' => true,
        'max_threads' => 4,
        'gpu_acceleration' => false
    ]
]; 