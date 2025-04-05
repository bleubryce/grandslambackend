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
    <title>Dashboard - Baseball Analytics System</title>
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
        <a href="dashboard.php" class="sidebar-link active">
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
            <h1 class="mb-0">Analytics Dashboard</h1>
            <div>
                <button class="btn btn-outline-secondary me-2" id="exportBtn">
                    <i class="bi bi-download"></i> Export
                </button>
                <button class="btn btn-primary" id="refreshBtn">
                    <i class="bi bi-arrow-clockwise"></i> Refresh Data
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Players Tracked</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">1,250</div>
                                <div class="small text-muted mt-2">+125 from last month</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Data Points</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">8.2M</div>
                                <div class="small text-muted mt-2">+1.5M from last month</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-database fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Model Accuracy</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">92.7%</div>
                                <div class="small text-success mt-2">+2.3% from last version</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-graph-up fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Reports Generated</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">215</div>
                                <div class="small text-muted mt-2">+45 from last month</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-file-earmark-text fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity and System Status -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        Recent Activity
                    </div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <div class="activity-item d-flex">
                                <div class="activity-icon bg-primary text-white">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="d-flex justify-content-between">
                                        <strong>Player Analysis Updated</strong>
                                        <small class="text-muted">10 minutes ago</small>
                                    </div>
                                    <p>New Statcast data for Aaron Judge has been processed and analyzed.</p>
                                </div>
                            </div>
                            <div class="activity-item d-flex">
                                <div class="activity-icon bg-success text-white">
                                    <i class="bi bi-lightning"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="d-flex justify-content-between">
                                        <strong>Model Training Complete</strong>
                                        <small class="text-muted">45 minutes ago</small>
                                    </div>
                                    <p>WAR prediction model has been retrained with latest data. Accuracy improved by 2.3%.</p>
                                </div>
                            </div>
                            <div class="activity-item d-flex">
                                <div class="activity-icon bg-info text-white">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="d-flex justify-content-between">
                                        <strong>Weekly Report Generated</strong>
                                        <small class="text-muted">2 hours ago</small>
                                    </div>
                                    <p>Weekly performance report for all teams has been generated and is ready for review.</p>
                                </div>
                            </div>
                            <div class="activity-item d-flex">
                                <div class="activity-icon bg-warning text-white">
                                    <i class="bi bi-database"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="d-flex justify-content-between">
                                        <strong>Database Updated</strong>
                                        <small class="text-muted">5 hours ago</small>
                                    </div>
                                    <p>Latest game data has been imported and processed. 250,000 new data points added.</p>
                                </div>
                            </div>
                            <div class="activity-item d-flex">
                                <div class="activity-icon bg-danger text-white">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="d-flex justify-content-between">
                                        <strong>Team Comparison Updated</strong>
                                        <small class="text-muted">Yesterday</small>
                                    </div>
                                    <p>Team comparison metrics have been updated with the latest performance data.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        System Status
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Database</span>
                                <span class="text-success">Operational</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">25% storage used</small>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Data Collection</span>
                                <span class="text-success">Active</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">All sources connected</small>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Model Training</span>
                                <span class="text-success">Complete</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">Last run: 45 minutes ago</small>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>API Services</span>
                                <span class="text-success">Online</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">All endpoints responding</small>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Report Generation</span>
                                <span class="text-success">Ready</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">All templates up to date</small>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        Upcoming Tasks
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Weekly data backup
                                <span class="badge bg-primary rounded-pill">Today</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Model performance review
                                <span class="badge bg-primary rounded-pill">Tomorrow</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Monthly report generation
                                <span class="badge bg-warning rounded-pill">3 days</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                System maintenance
                                <span class="badge bg-info rounded-pill">5 days</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Data Collection Trends
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dataCollectionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Model Performance
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="modelPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        Quick Access
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="player_analysis.php" class="card h-100 quick-access-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-person-badge fs-1 mb-3"></i>
                                        <h5 class="card-title">Player Analysis</h5>
                                        <p class="card-text">Detailed player statistics and performance metrics</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="team_analysis.php" class="card h-100 quick-access-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-people fs-1 mb-3"></i>
                                        <h5 class="card-title">Team Analysis</h5>
                                        <p class="card-text">Comprehensive team performance and comparison tools</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="predictive_models.php" class="card h-100 quick-access-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-lightning fs-1 mb-3"></i>
                                        <h5 class="card-title">Predictive Models</h5>
                                        <p class="card-text">Advanced predictive analytics and projections</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="reports.php" class="card h-100 quick-access-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-file-earmark-text fs-1 mb-3"></i>
                                        <h5 class="card-title">Reports</h5>
                                        <p class="card-text">Generate and view comprehensive analytical reports</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Data Collection Chart
        const dataCollectionCtx = document.getElementById('dataCollectionChart').getContext('2d');
        const dataCollectionChart = new Chart(dataCollectionCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Game Data',
                    data: [1.2, 1.5, 1.8, 2.1, 2.4, 2.7, 3.0, 3.3, 3.6, 3.9, 4.2, 4.5],
                    borderColor: 'rgba(13, 110, 253, 1)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Statcast Data',
                    data: [0.8, 1.0, 1.2, 1.4, 1.6, 1.8, 2.0, 2.2, 2.4, 2.6, 2.8, 3.0],
                    borderColor: 'rgba(220, 53, 69, 1)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Player Metrics',
                    data: [0.5, 0.6, 0.7, 0.8, 0.9, 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6],
                    borderColor: 'rgba(25, 135, 84, 1)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Data Points (millions)'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });

        // Model Performance Chart
        const modelPerformanceCtx = document.getElementById('modelPerformanceChart').getContext('2d');
        const modelPerformanceChart = new Chart(modelPerformanceCtx, {
            type: 'bar',
            data: {
                labels: ['WAR Prediction', 'Player Development', 'Injury Risk', 'Contract Value', 'Draft Value'],
                datasets: [{
                    label: 'Accuracy',
                    data: [92.7, 88.5, 85.2, 90.1, 82.8],
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(13, 202, 240, 0.7)'
                    ],
                    borderColor: [
                        'rgba(13, 110, 253, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(13, 202, 240, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Accuracy (%)'
                        }
                    }
                }
            }
        });

        // Export button
        document.getElementById('exportBtn').addEventListener('click', function() {
            // In a real application, this would generate and download a report
            alert('Exporting dashboard data...');
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            // In a real application, this would refresh the dashboard data
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Refreshing...';
            
            setTimeout(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh Data';
                alert('Dashboard data refreshed successfully!');
            }, 1500);
        });

        // Style for activity feed
        document.querySelectorAll('.activity-item').forEach((item, index) => {
            if (index > 0) {
                item.style.marginTop = '15px';
            }
        });

        // Style for quick access cards
        document.querySelectorAll('.quick-access-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('shadow');
            });
            card.addEventListener('mouseleave', function() {
                this.classList.remove('shadow');
            });
        });
    </script>
</body>
</html>
