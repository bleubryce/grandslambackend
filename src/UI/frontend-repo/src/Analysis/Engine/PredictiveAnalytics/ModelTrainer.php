<?php

namespace BaseballAnalytics\Analysis\Engine\PredictiveAnalytics;

use Phpml\Classification\RandomForest;
use Phpml\Classification\SVC;
use Phpml\Regression\SVR;
use Phpml\Regression\LeastSquares;
use Phpml\ModelManager;
use Phpml\Preprocessing\Normalizer;
use Phpml\CrossValidation\StratifiedRandomSplit;
use Phpml\Metric\Regression as RegressionMetric;
use Phpml\Metric\ClassificationReport;

class ModelTrainer
{
    private const MODEL_TYPES = [
        'classification' => [
            'random_forest' => RandomForest::class,
            'svc' => SVC::class
        ],
        'regression' => [
            'svr' => SVR::class,
            'least_squares' => LeastSquares::class
        ]
    ];

    private string $modelPath;
    private Normalizer $normalizer;
    private array $trainedModels = [];
    private array $modelMetrics = [];

    public function __construct(string $modelPath)
    {
        $this->modelPath = $modelPath;
        $this->normalizer = new Normalizer();
    }

    public function trainModel(
        string $modelType,
        string $algorithm,
        array $features,
        array $labels,
        array $settings = []
    ): array {
        // Normalize features
        $normalizedFeatures = $this->normalizer->transform($features);

        // Split data for training and validation
        $split = new StratifiedRandomSplit($normalizedFeatures, $labels, 0.2);
        $trainFeatures = $split->getTrainSamples();
        $trainLabels = $split->getTrainLabels();
        $testFeatures = $split->getTestSamples();
        $testLabels = $split->getTestLabels();

        // Create and train model
        $modelClass = self::MODEL_TYPES[$modelType][$algorithm];
        $model = $this->createModel($modelClass, $settings);
        $model->train($trainFeatures, $trainLabels);

        // Generate predictions for test set
        $predictions = $model->predict($testFeatures);

        // Calculate metrics
        $metrics = $this->calculateMetrics($modelType, $testLabels, $predictions);

        // Save model
        $modelKey = "{$modelType}_{$algorithm}_" . time();
        $this->saveModel($model, $modelKey);

        $this->trainedModels[$modelKey] = $model;
        $this->modelMetrics[$modelKey] = $metrics;

        return [
            'model_key' => $modelKey,
            'metrics' => $metrics,
            'feature_importance' => $this->getFeatureImportance($model)
        ];
    }

    private function createModel(string $modelClass, array $settings): object
    {
        switch ($modelClass) {
            case RandomForest::class:
                return new RandomForest(
                    $settings['numTrees'] ?? 100,
                    $settings['numFeatures'] ?? null
                );
            case SVC::class:
                return new SVC(
                    $settings['kernel'] ?? SVC::KERNEL_RBF,
                    $settings['cost'] ?? 1.0
                );
            case SVR::class:
                return new SVR(
                    $settings['kernel'] ?? SVR::KERNEL_RBF,
                    $settings['degree'] ?? 3
                );
            case LeastSquares::class:
                return new LeastSquares();
            default:
                throw new \InvalidArgumentException("Unsupported model class: {$modelClass}");
        }
    }

    private function calculateMetrics(string $modelType, array $actual, array $predicted): array
    {
        if ($modelType === 'regression') {
            return [
                'mse' => RegressionMetric::meanSquaredError($actual, $predicted),
                'rmse' => sqrt(RegressionMetric::meanSquaredError($actual, $predicted)),
                'mae' => RegressionMetric::meanAbsoluteError($actual, $predicted),
                'r2' => RegressionMetric::r2Score($actual, $predicted)
            ];
        } else {
            $report = new ClassificationReport($actual, $predicted);
            return [
                'accuracy' => $report->getAccuracy(),
                'precision' => $report->getPrecision(),
                'recall' => $report->getRecall(),
                'f1_score' => $report->getF1score()
            ];
        }
    }

    private function saveModel(object $model, string $modelKey): void
    {
        $modelManager = new ModelManager();
        $modelPath = $this->modelPath . "/{$modelKey}.model";
        $modelManager->saveToFile($model, $modelPath);
    }

    public function loadModel(string $modelKey): object
    {
        if (isset($this->trainedModels[$modelKey])) {
            return $this->trainedModels[$modelKey];
        }

        $modelManager = new ModelManager();
        $modelPath = $this->modelPath . "/{$modelKey}.model";
        
        if (!file_exists($modelPath)) {
            throw new \RuntimeException("Model not found: {$modelKey}");
        }

        $model = $modelManager->restoreFromFile($modelPath);
        $this->trainedModels[$modelKey] = $model;
        return $model;
    }

    private function getFeatureImportance(object $model): ?array
    {
        if ($model instanceof RandomForest) {
            return $model->getFeatureImportances();
        }
        return null;
    }

    public function getModelMetrics(string $modelKey): array
    {
        return $this->modelMetrics[$modelKey] ?? [];
    }

    public function getNormalizer(): Normalizer
    {
        return $this->normalizer;
    }
} 