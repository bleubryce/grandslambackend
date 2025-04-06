<?php

namespace BaseballAnalytics\Analysis\Engine;

use BaseballAnalytics\Database\Connection;
use BaseballAnalytics\Utils\Logger;

class AnalysisEngine
{
    use AnalysisHelpers;

    private Connection $db;
    private Logger $logger;
    private array $config;
    private array $analyzers;
    private array $results;

    public function __construct(Connection $db, array $config = [])
    {
        $this->db = $db;
        $this->logger = new Logger('analysis_engine');
        $this->config = $config;
        $this->results = [];
        $this->initializeAnalyzers();
    }

    private function initializeAnalyzers(): void
    {
        $this->analyzers = [
            'team' => new TeamPerformanceAnalyzer($this->db, $this->config['team'] ?? []),
            'player' => new PlayerPerformanceAnalyzer($this->db, $this->config['player'] ?? []),
            'game' => new GameAnalyzer($this->db, $this->config['game'] ?? []),
            'ml' => new MachineLearningAnalyzer($this->db, $this->config['ml'] ?? [])
        ];
    }

    public function analyze(): bool
    {
        $this->logger->info("Starting comprehensive baseball analysis");
        $startTime = microtime(true);

        try {
            // Run team analysis
            if (!$this->analyzers['team']->analyze()) {
                $this->logger->error("Team performance analysis failed");
                return false;
            }
            $this->results['team'] = $this->analyzers['team']->getResults();

            // Run player analysis
            if (!$this->analyzers['player']->analyze()) {
                $this->logger->error("Player performance analysis failed");
                return false;
            }
            $this->results['player'] = $this->analyzers['player']->getResults();

            // Run game analysis
            if (!$this->analyzers['game']->analyze()) {
                $this->logger->error("Game analysis failed");
                return false;
            }
            $this->results['game'] = $this->analyzers['game']->getResults();

            // Run machine learning analysis
            if (!$this->analyzers['ml']->analyze()) {
                $this->logger->error("Machine learning analysis failed");
                return false;
            }
            $this->results['ml'] = $this->analyzers['ml']->getResults();

            // Generate comprehensive insights
            $this->generateInsights();

            $duration = round(microtime(true) - $startTime, 2);
            $this->logger->info("Analysis completed successfully in {$duration} seconds");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Analysis failed: " . $e->getMessage());
            return false;
        }
    }

    private function generateInsights(): void
    {
        $this->results['insights'] = [
            'team_insights' => $this->generateTeamInsights(),
            'player_insights' => $this->generatePlayerInsights(),
            'game_insights' => $this->generateGameInsights(),
            'predictions' => $this->generatePredictions()
        ];
    }

    private function generateTeamInsights(): array
    {
        $teamResults = $this->results['team'];
        $insights = [];

        // Analyze overall performance
        if (isset($teamResults['overall_performance'])) {
            $insights['performance_summary'] = $this->analyzeTeamPerformance($teamResults['overall_performance']);
        }

        // Analyze home/away splits
        if (isset($teamResults['home_away_performance'])) {
            $insights['home_away_analysis'] = $this->analyzeHomeAwayPerformance($teamResults['home_away_performance']);
        }

        // Analyze situational performance
        if (isset($teamResults['situational_performance'])) {
            $insights['situational_analysis'] = $this->analyzeSituationalPerformance($teamResults['situational_performance']);
        }

        return $insights;
    }

    private function generatePlayerInsights(): array
    {
        $playerResults = $this->results['player'];
        $insights = [];

        // Analyze batting performance
        if (isset($playerResults['batting_analysis'])) {
            $insights['batting'] = $this->analyzeBattingTrends($playerResults['batting_analysis']);
        }

        // Analyze pitching performance
        if (isset($playerResults['pitching_analysis'])) {
            $insights['pitching'] = $this->analyzePitchingTrends($playerResults['pitching_analysis']);
        }

        return $insights;
    }

    private function generateGameInsights(): array
    {
        $gameResults = $this->results['game'];
        $insights = [];

        // Analyze scoring patterns
        if (isset($gameResults['scoring_analysis'])) {
            $insights['scoring_patterns'] = $this->analyzeScoringPatterns($gameResults['scoring_analysis']);
        }

        // Analyze game outcomes
        if (isset($gameResults['game_outcomes'])) {
            $insights['outcome_analysis'] = $this->analyzeGameOutcomes($gameResults['game_outcomes']);
        }

        return $insights;
    }

    private function generatePredictions(): array
    {
        $mlResults = $this->results['ml'];
        $predictions = [];

        // Process player performance predictions
        if (isset($mlResults['player_predictions'])) {
            $predictions['player'] = $this->processPlayerPredictions($mlResults['player_predictions']);
        }

        // Process game outcome predictions
        if (isset($mlResults['game_predictions'])) {
            $predictions['game'] = $this->processGamePredictions($mlResults['game_predictions']);
        }

        return $predictions;
    }

    private function analyzeTeamPerformance(array $performance): array
    {
        return [
            'top_teams' => array_slice($performance, 0, 5, true),
            'statistical_leaders' => $this->identifyStatisticalLeaders($performance),
            'performance_trends' => $this->identifyPerformanceTrends($performance)
        ];
    }

    private function analyzeHomeAwayPerformance(array $performance): array
    {
        return [
            'home_field_advantage' => $this->calculateHomeFieldAdvantage($performance),
            'road_performance' => $this->analyzeRoadPerformance($performance),
            'split_analysis' => $this->analyzePerformanceSplits($performance)
        ];
    }

    private function analyzeSituationalPerformance(array $performance): array
    {
        return [
            'close_games' => $this->analyzeCloseGamePerformance($performance),
            'high_leverage' => $this->analyzeHighLeveragePerformance($performance),
            'matchup_analysis' => $this->analyzeMatchupPerformance($performance)
        ];
    }

    private function analyzeBattingTrends(array $batting): array
    {
        return [
            'league_leaders' => $this->identifyBattingLeaders($batting),
            'statistical_trends' => $this->analyzeBattingStatistics($batting),
            'performance_indicators' => $this->identifyBattingIndicators($batting)
        ];
    }

    private function analyzePitchingTrends(array $pitching): array
    {
        return [
            'league_leaders' => $this->identifyPitchingLeaders($pitching),
            'statistical_trends' => $this->analyzePitchingStatistics($pitching),
            'performance_indicators' => $this->identifyPitchingIndicators($pitching)
        ];
    }

    private function analyzeScoringPatterns(array $scoring): array
    {
        return [
            'run_distribution' => $this->analyzeRunDistribution($scoring),
            'inning_patterns' => $this->analyzeInningPatterns($scoring),
            'scoring_trends' => $this->identifyScoringTrends($scoring)
        ];
    }

    private function analyzeGameOutcomes(array $outcomes): array
    {
        return [
            'outcome_distribution' => $this->analyzeOutcomeDistribution($outcomes),
            'situational_analysis' => $this->analyzeSituationalOutcomes($outcomes),
            'trend_analysis' => $this->identifyOutcomeTrends($outcomes)
        ];
    }

    private function processPlayerPredictions(array $predictions): array
    {
        return [
            'batting_projections' => $this->processBattingProjections($predictions),
            'pitching_projections' => $this->processPitchingProjections($predictions),
            'performance_trends' => $this->processPerformanceTrends($predictions)
        ];
    }

    private function processGamePredictions(array $predictions): array
    {
        return [
            'win_probability' => $this->processWinProbability($predictions),
            'run_expectancy' => $this->processRunExpectancy($predictions),
            'outcome_projections' => $this->processOutcomeProjections($predictions)
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getInsights(): array
    {
        return $this->results['insights'] ?? [];
    }
} 