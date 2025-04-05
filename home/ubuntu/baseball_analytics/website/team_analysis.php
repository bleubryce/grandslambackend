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
    <title>Team Analysis - Baseball Analytics System</title>
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
        <a href="team_analysis.php" class="sidebar-link active">
            <i class="bi bi-people"></i>
            <span>Team Analysis</span>
        </a>
        <a href="statcast_data.php" class="sidebar-link">
            <i class="bi bi-graph-up"></i>
            <span>Statcast Data</span>
        </a>
        <a href="predictive_models.php" class="sidebar-link">
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
            <h1 class="mb-0">Team Analysis</h1>
            <div>
                <button class="btn btn-outline-secondary me-2" id="exportBtn">
                    <i class="bi bi-download"></i> Export
                </button>
                <button class="btn btn-primary" id="compareTeamsBtn">
                    <i class="bi bi-bar-chart"></i> Compare Teams
                </button>
            </div>
        </div>

        <!-- Team Selection -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        Select Team
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="teamSelect" class="form-label">Team</label>
                                <select class="form-select" id="teamSelect">
                                    <option value="NYY">New York Yankees</option>
                                    <option value="LAD">Los Angeles Dodgers</option>
                                    <option value="BOS">Boston Red Sox</option>
                                    <option value="CHC">Chicago Cubs</option>
                                    <option value="ATL">Atlanta Braves</option>
                                    <option value="HOU">Houston Astros</option>
                                    <option value="PHI">Philadelphia Phillies</option>
                                    <option value="SD">San Diego Padres</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="seasonSelect" class="form-label">Season</label>
                                <select class="form-select" id="seasonSelect">
                                    <option value="2025">2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023">2023</option>
                                    <option value="2022">2022</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="analysisType" class="form-label">Analysis Type</label>
                                <select class="form-select" id="analysisType">
                                    <option value="overall">Overall Performance</option>
                                    <option value="offense">Offensive Analysis</option>
                                    <option value="pitching">Pitching Analysis</option>
                                    <option value="defense">Defensive Analysis</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-end">
                                <button class="btn btn-primary" id="loadTeamBtn">Load Team Data</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Overview -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        New York Yankees - 2025 Season Overview
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center mb-4">
                                <div style="width: 150px; height: 150px; margin: 0 auto;">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="50" cy="50" r="45" fill="#0d2b56" />
                                        <text x="50" y="65" font-family="Arial" font-size="40" font-weight="bold" fill="white" text-anchor="middle">NY</text>
                                    </svg>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h3 class="mb-3">New York Yankees</h3>
                                <div class="d-flex mb-2">
                                    <div class="me-4">
                                        <small class="text-muted d-block">Record</small>
                                        <span class="fw-bold">90-72</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Win %</small>
                                        <span class="fw-bold">.556</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Division</small>
                                        <span class="fw-bold">AL East</span>
                                    </div>
                                </div>
                                <div class="d-flex mb-2">
                                    <div class="me-4">
                                        <small class="text-muted d-block">Runs Scored</small>
                                        <span class="fw-bold">760</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Runs Allowed</small>
                                        <span class="fw-bold">645</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Run Diff</small>
                                        <span class="fw-bold text-success">+115</span>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <div class="me-4">
                                        <small class="text-muted d-block">Home</small>
                                        <span class="fw-bold">48-33</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Away</small>
                                        <span class="fw-bold">42-39</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Last 10</small>
                                        <span class="fw-bold">7-3</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="row">
                                    <div class="col-6 text-center">
                                        <div class="border rounded p-2 mb-2">
                                            <h2 class="mb-0 text-primary">88%</h2>
                                            <small class="text-muted">Playoff Odds</small>
                                        </div>
                                    </div>
                                    <div class="col-6 text-center">
                                        <div class="border rounded p-2 mb-2">
                                            <h2 class="mb-0 text-success">42.5</h2>
                                            <small class="text-muted">Team WAR</small>
                                        </div>
                                    </div>
                                    <div class="col-6 text-center">
                                        <div class="border rounded p-2">
                                            <h2 class="mb-0 text-danger">112</h2>
                                            <small class="text-muted">wRC+</small>
                                        </div>
                                    </div>
                                    <div class="col-6 text-center">
                                        <div class="border rounded p-2">
                                            <h2 class="mb-0 text-info">3.65</h2>
                                            <small class="text-muted">Team ERA</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Charts -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Run Differential by Month
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="runDiffChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Team Performance Metrics
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="radarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roster Analysis -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Roster Analysis</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active" data-roster-type="position">By Position</button>
                            <button type="button" class="btn btn-outline-secondary" data-roster-type="war">By WAR</button>
                            <button type="button" class="btn btn-outline-secondary" data-roster-type="salary">By Salary</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="rosterChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Comparison -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
        
(Content truncated due to size limit. Use line ranges to read in chunks)