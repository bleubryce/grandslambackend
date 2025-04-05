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
    <title>Statcast Data - Baseball Analytics System</title>
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
        <a href="statcast_data.php" class="sidebar-link active">
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
            <h1 class="mb-0">Statcast Data</h1>
            <div>
                <button class="btn btn-outline-secondary me-2" id="exportBtn">
                    <i class="bi bi-download"></i> Export Data
                </button>
                <button class="btn btn-primary" id="customQueryBtn">
                    <i class="bi bi-code-slash"></i> Custom Query
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar d-flex flex-wrap align-items-center mb-4">
            <div class="me-3 mb-2 mb-md-0">
                <label class="form-label mb-0 me-2">Data Type:</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="dataTypeSelect">
                    <option value="batting">Batting</option>
                    <option value="pitching">Pitching</option>
                    <option value="fielding">Fielding</option>
                </select>
            </div>
            <div class="me-3 mb-2 mb-md-0">
                <label class="form-label mb-0 me-2">Season:</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="seasonSelect">
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                    <option value="2023">2023</option>
                    <option value="2022">2022</option>
                </select>
            </div>
            <div class="me-3 mb-2 mb-md-0">
                <label class="form-label mb-0 me-2">Player:</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="playerSelect">
                    <option value="">All Players</option>
                    <option value="aaron_judge">Aaron Judge</option>
                    <option value="shohei_ohtani">Shohei Ohtani</option>
                    <option value="juan_soto">Juan Soto</option>
                    <option value="mookie_betts">Mookie Betts</option>
                </select>
            </div>
            <button class="btn btn-sm btn-primary ms-auto" id="applyFiltersBtn">Apply Filters</button>
        </div>

        <!-- Statcast Visualizations -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Exit Velocity vs. Launch Angle
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="exitVeloChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Spray Chart
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="sprayChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Pitch Movement
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="pitchMovementChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Pitch Velocity Distribution
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="velocityDistChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statcast Metrics -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Statcast Leaderboard</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active" data-metric-type="exit_velo">Exit Velocity</button>
                            <button type="button" class="btn btn-outline-secondary" data-metric-type="barrel">Barrel %</button>
                            <button type="button" class="btn btn-outline-secondary" data-metric-type="hard_hit">Hard Hit %</button>
                            <button type="button" class="btn btn-outline-secondary" data-metric-type="sprint">Sprint Speed</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Player</th>
                                        <th>Team</th>
                                        <th>Exit Velocity (mph)</th>
                                        <th>Max Exit Velocity</th>
                                        <th>Launch Angle (°)</th>
                                        <th>Sweet Spot %</th>
                                        <th>Barrel %</th>
                                        <th>Hard Hit %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Aaron Judge</td>
                                        <td>NYY</td>
                                        <td>96.8</td>
                                        <td>118.4</td>
                                        <td>16.5</td>
                                        <td>42.3%</td>
                                        <td>25.4%</td>
                                        <td>62.8%</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>Giancarlo Stanton</td>
                                        <td>NYY</td>
                                        <td>95.9</td>
                                        <td>119.2</td>
                                        <td>12.8</td>
                                        <td>38.5%</td>
                                        <td>22.7%</td>
                                        <td>60.2%</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>Vladimir Guerrero Jr.</td>
                                        <td>TOR</td>
                                        <td>94.7</td>
                                        <td>117.8</td>
                                        <td>10.2</td>
                                        <td>40.1%</td>
                                        <td>19.5%</td>
                                        <td>56.3%</td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>Yordan Alvarez</td>
                                        <td>HOU</td>
                                        <td>94.2</td>
                                        <td>116.9</td>
                                        <td>14.3</td>
                                        <td>41.2%</td>
                                        <td>17.2%</td>
                                        <td>55.8%</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>Shohei Ohtani</td>
                                        <td>LAD</td>
                                        <td>93.8</td>
                                        <td>118.2</td>
                                        <td>15.7</td>
                                        <td>39.8%</td>
                                        <td>18.9%</td>
                                        <td>54.2%</td>
                                    </tr>
                                    <tr>
                                        <td>6</td>
                                        <td>Kyle Schwarber</td>
                                        <td>PHI</td>
                                        <td>93.5</td>
                                        <td>116.3</td>
                                        <td>18.2</td>
                                        <td>38.7%</td>
                                        <td>19.8%</td>
                                        <td>53.9%</td>
                                    </tr>
                                    <tr>
                                        <td>7</td>
                                        <td>Juan Soto</td>
                                        <td>NYY</td>
                                        <td>93.2</td>
                                        <td>115.8</td>
                                        <td>13.5</td>
                                        <td>42.8%</td>
                                        <td>16.4%</td>
                                        <td>52.7%</td>
                                    </tr>
                                    <tr>
                                        <td>8</td>
                                        <td>Pete Alonso</td>
                                        <td>NYM</td>
                                        <td>92.9</td>
                                        <td>116.5</td>
                                        <td>16.8</td>
                                        <td>37.5%</td>
                                        <td>17.8%</td>
                                        <td>51.9%</td>
                                    </tr>
                                    <tr>
                                        <td>9</td>
                                        <td>Matt Olson</td>
                                        <td>ATL</td>
                                        <td>92.7</td>
                                        <td>115.2</td>
                                        <td>15.3</td>
                                        <td>39.2%</td>
                                        <td>16.9%</td>
                                        <td>51.2%</td>
                                    </tr>
                                    <tr>
                                   
(Content truncated due to size limit. Use line ranges to read in chunks)