<?php
// Include authentication file
require_once 'auth.php';

// Require authentication for this page
requireAuth();

// Get current user
$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictive Models - Baseball Analytics System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Baseball Analytics</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                3
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#">New player data available</a></li>
                            <li><a class="dropdown-item" href="#">Model training complete</a></li>
                            <li><a class="dropdown-item" href="#">Weekly report generated</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?logout=1">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="dashboard.php" class="sidebar-link">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="player_analysis.php" class="sidebar-link">
            <i class="bi bi-person"></i>
            <span>Player Analysis</span>
        </a>
        <a href="team_analysis.php" class="sidebar-link">
            <i class="bi bi-people"></i>
            <span>Team Analysis</span>
        </a>
        <a href="statcast_data.php" class="sidebar-link">
            <i class="bi bi-graph-up"></i>
            <span>Statcast Data</span>
        </a>
        <a href="predictive_models.php" class="sidebar-link active">
            <i class="bi bi-lightning"></i>
            <span>Predictive Models</span>
        </a>
        <a href="reports.php" class="sidebar-link">
            <i class="bi bi-file-earmark-text"></i>
            <span>Reports</span>
        </a>
        <a href="player_search.php" class="sidebar-link">
            <i class="bi bi-search"></i>
            <span>Player Search</span>
        </a>
        <a href="settings.php" class="sidebar-link">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>
        <hr>
        <a href="help.php" class="sidebar-link">
            <i class="bi bi-question-circle"></i>
            <span>Help & Support</span>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Predictive Models</h1>
            <div>
                <button class="btn btn-outline-secondary me-2" id="exportBtn">
                    <i class="bi bi-download"></i> Export Results
                </button>
                <button class="btn btn-primary" id="newModelBtn">
                    <i class="bi bi-plus"></i> New Model
                </button>
            </div>
        </div>

        <!-- Model Selection -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        Select Model
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="modelType" class="form-label">Model Type</label>
                                <select class="form-select" id="modelType">
                                    <option value="war_prediction">WAR Prediction</option>
                                    <option value="player_development">Player Development Projection</option>
                                    <option value="injury_risk">Injury Risk Assessment</option>
                                    <option value="contract_value">Contract Value Optimization</option>
                                    <option value="draft_value">Draft Value Projection</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="dataSource" class="form-label">Data Source</label>
                                <select class="form-select" id="dataSource">
                                    <option value="all">All Available Data</option>
                                    <option value="statcast">Statcast Only</option>
                                    <option value="traditional">Traditional Stats Only</option>
                                    <option value="advanced">Advanced Metrics Only</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="timeframe" class="form-label">Timeframe</label>
                                <select class="form-select" id="timeframe">
                                    <option value="current">Current Season</option>
                                    <option value="1year">1 Year Projection</option>
                                    <option value="3year">3 Year Projection</option>
                                    <option value="5year">5 Year Projection</option>
                                    <option value="career">Career Projection</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-end">
                                <button class="btn btn-primary" id="runModelBtn">Run Model</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Model Results -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>WAR Prediction Model Results</span>
                        <span class="badge bg-success">92.7% Accuracy</span>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            This model predicts player WAR values based on current performance metrics, historical data, and Statcast indicators. Last updated: April 2, 2025.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Player</th>
                                        <th>Team</th>
                                        <th>Position</th>
                                        <th>Current WAR</th>
                                        <th>Predicted WAR</th>
                                        <th>Change</th>
                                        <th>Confidence</th>
                                        <th>Key Factors</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Aaron Judge</td>
                                        <td>NYY</td>
                                        <td>RF</td>
                                        <td>8.2</td>
                                        <td>8.5</td>
                                        <td><span class="text-success">+0.3</span></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" style="width: 95%">95%</div>
                                            </div>
                                        </td>
                                        <td>Exit Velocity, Barrel %</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>Shohei Ohtani</td>
                                        <td>LAD</td>
                                        <td>DH/P</td>
                                        <td>7.9</td>
                                        <td>8.2</td>
                                        <td><span class="text-success">+0.3</span></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" style="width: 93%">93%</div>
                                            </div>
                                        </td>
                                        <td>Two-way value, HR rate</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>Juan Soto</td>
                                        <td>NYY</td>
                                        <td>LF</td>
                                        <td>7.5</td>
                                        <td>7.8</td>
                                        <td><span class="text-success">+0.3</span></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" style="width: 92%">92%</div>
                                            </div>
                                        </td>
                                        <td>Plate discipline, OBP</td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>Mookie Betts</td>
                                        <td>LAD</td>
                                        <td>RF</td>
                                        <td>7.3</td>
                                        <td>7.0</td>
                                        <td><span class="text-danger">-0.3</span></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" style="width: 88%">88%</div>
                                            </div>
                                        </td>
                                        <td>Age regression, defensive metrics</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>Trea Turner</td>
                                        <td>PHI</td>
                                        <td>SS</td>
                                        <td>6.8</td>
                                        <td>6.5</td>
                                        <td><span class="text-danger">-0.3</span></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" style="width: 85%">85%</div>
                                            </div>
                                        </td>
                                        <td>Sprint speed decline, BABIP regression</td>
                                    </tr>
                                    <tr>
                                        <td>6</td>
                                        <td>Corbin Burnes</td>
                                        <td>BAL</td>
                                        <td>SP</td>
                                        <td>6.5</td>
                                        <td>6.8</td>
                                        <td><span class="text-success">+0.3</span></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" style="width: 90%">90%</div>
                                            </div>
                                        </td>
                                        <td>Spin rate, K/9 improvement</td>
                                    </tr>
                                    <tr>
                                        <td>7</td>
                                        <td>Freddie Freeman</td>
                                        <td>LAD</td>
                                        <td>1B</td>
                                        <td>6.2</td>
                                        <td>6.0</td>
                                        <td><span class="text-danger">-0.2</span></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" style="width: 87%">87%</div>
                                            </div>
                                        </td>
                                        <td>Age-related decline, contact quality</td>
                                    </tr>
                
(Content truncated due to size limit. Use line ranges to read in chunks)