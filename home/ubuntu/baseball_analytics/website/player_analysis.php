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
    <title>Player Analysis - Baseball Analytics System</title>
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
        <a href="player_analysis.php" class="sidebar-link active">
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
            <h1 class="mb-0">Player Analysis</h1>
            <div>
                <button class="btn btn-outline-secondary me-2" id="exportBtn">
                    <i class="bi bi-download"></i> Export
                </button>
                <button class="btn btn-primary" id="comparePlayersBtn">
                    <i class="bi bi-people"></i> Compare Players
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar d-flex flex-wrap align-items-center mb-4">
            <div class="me-3 mb-2 mb-md-0">
                <label class="form-label mb-0 me-2">Player:</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="playerSelect">
                    <option value="">Select Player</option>
                    <option value="1">Aaron Judge</option>
                    <option value="2">Shohei Ohtani</option>
                    <option value="3">Juan Soto</option>
                    <option value="4">Mookie Betts</option>
                    <option value="5">Trea Turner</option>
                </select>
            </div>
            <div class="me-3 mb-2 mb-md-0">
                <label class="form-label mb-0 me-2">Season:</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="seasonSelect">
                    <option>2025</option>
                    <option>2024</option>
                    <option>2023</option>
                    <option>2022</option>
                </select>
            </div>
            <div class="me-3 mb-2 mb-md-0">
                <label class="form-label mb-0 me-2">Stat Type:</label>
                <select class="form-select form-select-sm d-inline-block w-auto" id="statTypeSelect">
                    <option>Standard</option>
                    <option>Advanced</option>
                    <option>Statcast</option>
                </select>
            </div>
            <button class="btn btn-sm btn-primary ms-auto" id="applyFiltersBtn">Apply Filters</button>
        </div>

        <!-- Player Profile -->
        <div class="row mb-4" id="playerProfile">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <div style="width: 120px; height: 120px; background-color: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2.5rem; font-weight: bold; color: var(--primary-color);">
                                    AJ
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h3 class="mb-1">Aaron Judge</h3>
                                <p class="text-muted mb-2">New York Yankees | RF | #99</p>
                                <div class="d-flex mb-3">
                                    <div class="me-4">
                                        <small class="text-muted d-block">Age</small>
                                        <span class="fw-bold">33</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Height</small>
                                        <span class="fw-bold">6'7"</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Weight</small>
                                        <span class="fw-bold">282 lbs</span>
                                    </div>
                                    <div class="me-4">
                                        <small class="text-muted d-block">Bats/Throws</small>
                                        <span class="fw-bold">R/R</span>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <div class="me-4">
                                        <small class="text-muted d-block">Draft</small>
                                        <span class="fw-bold">2013, 1st Round</span>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">MLB Debut</small>
                                        <span class="fw-bold">August 13, 2016</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <div class="border rounded p-2 mb-2">
                                            <h2 class="mb-0 text-primary">8.2</h2>
                                            <small class="text-muted">WAR</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="border rounded p-2 mb-2">
                                            <h2 class="mb-0 text-success">.300</h2>
                                            <small class="text-muted">AVG</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="border rounded p-2 mb-2">
                                            <h2 class="mb-0 text-danger">42</h2>
                                            <small class="text-muted">HR</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="border rounded p-2">
                                            <h2 class="mb-0 text-info">.420</h2>
                                            <small class="text-muted">OBP</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="border rounded p-2">
                                            <h2 class="mb-0 text-warning">.600</h2>
                                            <small class="text-muted">SLG</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="border rounded p-2">
                                            <h2 class="mb-0" style="color: #6f42c1">1.020</h2>
                                            <small class="text-muted">OPS</small>
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
                        Season Performance Trends
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="seasonPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Career Progression
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="careerProgressionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Stats -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Detailed Statistics</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active" data-stat-type="batting">Batting</button>
                            <button type="button" class="btn btn-outline-secondary" data-stat-type="advanced">Advanced</button>
                            <button type="button" class="btn btn-outline-secondary" data-stat-type="splits">Splits</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Season</th>
                                        <th>Team</th>
                                        <th>G</th>
                                        <th>AB</th>
                                        <th>R</th>
                                        <th>H</th>
                                        <th>2B</th>
                                        <th>3B</th>
                                        <th>HR</th>
                                        <th>RBI</th>
                                        <th>SB</th>
                                        <th>CS</th>
                                        <th>BB</th>
                                        <th>SO</th>
                                        <th>AVG</th>
                                        <th>OBP</th>
                                        <th>SLG</th>
                                        <th>OPS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2025</td>
                                        <td>NYY</td>
                                        <td>148</td>
                                        <td>550</td>
                                        <td>110</td>
                                        <td>165</td>
                                        <td>28</td>
                                        <td>3</td>
                                        <td>42</td>
                                        <td>102</td>
                                        <td>12</td>
                                        <td>3</td>
   
(Content truncated due to size limit. Use line ranges to read in chunks)