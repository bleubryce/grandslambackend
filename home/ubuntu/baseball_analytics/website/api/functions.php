<?php
// Database connection configuration
$db_config = array(
    'host' => 'localhost',
    'username' => 'postgres',
    'password' => 'baseball_analytics',
    'database' => 'baseball_analytics'
);

// API endpoints for data retrieval
$api_endpoints = array(
    'player_data' => '/api/players',
    'team_data' => '/api/teams',
    'statcast_data' => '/api/statcast',
    'model_predictions' => '/api/predictions'
);

// Function to connect to the database
function connectToDatabase($config) {
    try {
        $dsn = "pgsql:host={$config['host']};dbname={$config['database']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

// Function to fetch player data
function getPlayerData($playerId = null) {
    global $db_config;
    
    $db = connectToDatabase($db_config);
    if (!$db) {
        return array('error' => 'Database connection failed');
    }
    
    try {
        if ($playerId) {
            $stmt = $db->prepare("SELECT * FROM players WHERE player_id = :player_id");
            $stmt->bindParam(':player_id', $playerId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->query("SELECT * FROM players ORDER BY war DESC LIMIT 100");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return array('error' => 'Data retrieval failed');
    }
}

// Function to fetch team data
function getTeamData($teamId = null) {
    global $db_config;
    
    $db = connectToDatabase($db_config);
    if (!$db) {
        return array('error' => 'Database connection failed');
    }
    
    try {
        if ($teamId) {
            $stmt = $db->prepare("SELECT * FROM teams WHERE team_id = :team_id");
            $stmt->bindParam(':team_id', $teamId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->query("SELECT * FROM teams ORDER BY win_percentage DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return array('error' => 'Data retrieval failed');
    }
}

// Function to fetch Statcast data
function getStatcastData($params = array()) {
    global $db_config;
    
    $db = connectToDatabase($db_config);
    if (!$db) {
        return array('error' => 'Database connection failed');
    }
    
    try {
        $query = "SELECT * FROM statcast WHERE 1=1";
        $parameters = array();
        
        if (isset($params['player_id'])) {
            $query .= " AND player_id = :player_id";
            $parameters[':player_id'] = $params['player_id'];
        }
        
        if (isset($params['date_from'])) {
            $query .= " AND game_date >= :date_from";
            $parameters[':date_from'] = $params['date_from'];
        }
        
        if (isset($params['date_to'])) {
            $query .= " AND game_date <= :date_to";
            $parameters[':date_to'] = $params['date_to'];
        }
        
        $query .= " ORDER BY game_date DESC LIMIT 1000";
        
        $stmt = $db->prepare($query);
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return array('error' => 'Data retrieval failed');
    }
}

// Function to get model predictions
function getModelPredictions($modelType, $parameters = array()) {
    global $db_config;
    
    $db = connectToDatabase($db_config);
    if (!$db) {
        return array('error' => 'Database connection failed');
    }
    
    try {
        $query = "SELECT * FROM model_predictions WHERE model_type = :model_type";
        $queryParams = array(':model_type' => $modelType);
        
        if (isset($parameters['player_id'])) {
            $query .= " AND player_id = :player_id";
            $queryParams[':player_id'] = $parameters['player_id'];
        }
        
        if (isset($parameters['team_id'])) {
            $query .= " AND team_id = :team_id";
            $queryParams[':team_id'] = $parameters['team_id'];
        }
        
        $query .= " ORDER BY confidence DESC LIMIT 100";
        
        $stmt = $db->prepare($query);
        foreach ($queryParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return array('error' => 'Prediction retrieval failed');
    }
}

// Function to run custom SQL query (admin only)
function runCustomQuery($sql, $isAdmin = false) {
    global $db_config;
    
    if (!$isAdmin) {
        return array('error' => 'Unauthorized access');
    }
    
    $db = connectToDatabase($db_config);
    if (!$db) {
        return array('error' => 'Database connection failed');
    }
    
    try {
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Custom query failed: " . $e->getMessage());
        return array('error' => 'Query execution failed: ' . $e->getMessage());
    }
}

// Function to generate reports
function generateReport($reportType, $parameters = array()) {
    global $db_config;
    
    $db = connectToDatabase($db_config);
    if (!$db) {
        return array('error' => 'Database connection failed');
    }
    
    $reportData = array();
    
    try {
        switch ($reportType) {
            case 'player_performance':
                $playerId = $parameters['player_id'] ?? null;
                if (!$playerId) {
                    return array('error' => 'Player ID is required');
                }
                
                // Get player basic info
                $stmt = $db->prepare("SELECT * FROM players WHERE player_id = :player_id");
                $stmt->bindParam(':player_id', $playerId);
                $stmt->execute();
                $reportData['player_info'] = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get player statistics
                $stmt = $db->prepare("SELECT * FROM player_stats WHERE player_id = :player_id ORDER BY season DESC");
                $stmt->bindParam(':player_id', $playerId);
                $stmt->execute();
                $reportData['player_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get player Statcast data
                $stmt = $db->prepare("SELECT * FROM statcast WHERE player_id = :player_id ORDER BY game_date DESC LIMIT 100");
                $stmt->bindParam(':player_id', $playerId);
                $stmt->execute();
                $reportData['statcast_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get player predictions
                $stmt = $db->prepare("SELECT * FROM model_predictions WHERE player_id = :player_id");
                $stmt->bindParam(':player_id', $playerId);
                $stmt->execute();
                $reportData['predictions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                break;
                
            case 'team_performance':
                $teamId = $parameters['team_id'] ?? null;
                if (!$teamId) {
                    return array('error' => 'Team ID is required');
                }
                
                // Get team basic info
                $stmt = $db->prepare("SELECT * FROM teams WHERE team_id = :team_id");
                $stmt->bindParam(':team_id', $teamId);
                $stmt->execute();
                $reportData['team_info'] = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get team statistics
                $stmt = $db->prepare("SELECT * FROM team_stats WHERE team_id = :team_id ORDER BY season DESC");
                $stmt->bindParam(':team_id', $teamId);
                $stmt->execute();
                $reportData['team_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get team players
                $stmt = $db->prepare("SELECT * FROM players WHERE team_id = :team_id ORDER BY war DESC");
                $stmt->bindParam(':team_id', $teamId);
                $stmt->execute();
                $reportData['team_players'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                break;
                
            case 'league_overview':
                // Get all teams
                $stmt = $db->query("SELECT * FROM teams ORDER BY win_percentage DESC");
                $reportData['teams'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get top players by WAR
                $stmt = $db->query("SELECT * FROM players ORDER BY war DESC LIMIT 50");
                $reportData['top_players'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get league averages
                $stmt = $db->query("SELECT AVG(runs_scored) as avg_runs, AVG(runs_allowed) as avg_runs_allowed, AVG(home_runs) as avg_hr, AVG(batting_average) as avg_ba, AVG(era) as avg_era FROM team_stats WHERE season = 2025");
                $reportData['league_averages'] = $stmt->fetch(PDO::FETCH_ASSOC);
                
                break;
                
            default:
                return array('error' => 'Invalid report type');
        }
        
        return $reportData;
    } catch (PDOException $e) {
        error_log("Report generation failed: " . $e->getMessage());
        return array('error' => 'Report generation failed');
    }
}

// Function to log user activity
function logUserActivity($userId, $action, $details = '') {
    global $db_config;
    
    $db = connectToDatabase($db_config);
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO user_activity_log (user_id, action, details, timestamp) VALUES (:user_id, :action, :details, NOW())");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Activity logging failed: " . $e->getMessage());
        return false;
    }
}

// Function to get system status
function getSystemStatus() {
    global $db_config;
    
    $status = array(
        'database' => false,
        'data_collection' => false,
        'model_training' => false,
        'api_services' => false,
        'report_generation' => false
    );
    
    // Check database connection
    $db = connectToDatabase($db_config);
    if ($db) {
        $status['database'] = true;
        
        // Check data collection status
        try {
            $stmt = $db->query("SELECT status FROM system_status WHERE component = 'data_collection'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $status['data_collection'] = ($result && $result['status'] === 'active');
            
            // Check model training status
            $stmt = $db->query("SELECT status FROM system_status WHERE component = 'model_training'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $status['model_training'] = ($result && $result['status'] === 'complete');
            
            // Check API services status
            $stmt = $db->query("SELECT status FROM system_status WHERE component = 'api_services'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $status['api_services'] = ($result && $result['status'] === 'online');
            
            // Check report generation status
            $stmt = $db->query("SELECT status FROM system_status WHERE component = 'report_generation'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $status['report_generation'] = ($result && $result['status'] === 'ready');
        } catch (PDOException $e) {
            error_log("Status check failed: " . $e->getMessage());
        }
    }
    
    return $status;
}

// Function to get recent activity
function getRecentActivity($limit = 5) {
    global $db_config;
    
    $db = connectToDatabase($db_config);
    if (!$db) {
        return array();
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM system_activity_log ORDER BY timestamp DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Recent activity retrieval failed: " . $e->getMessage());
        return array();
    }
}

// Function to export data to CSV
function exportToCSV($data, $filename) {
    if (empty($data)) {
        return false;
    }
    
    $output = fopen('php://output', 'w');
    
    // Output headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // Output column headers
    fputcsv($output, array_keys($data[0]));
    
    // Output data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    return true;
}

// Function to validate user input
function validateInput($input, $type) {
    switch ($type) {
        case 'player_id':
            return preg_match('/^[a-zA-Z0-9_]+$/', $input);
            
        case 'team_id':
            return preg_match('/^[A-Z]{2,3}$/', $input);
            
        case 'date':
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $input);
            
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
            
        case 'sql':
            // Basic SQL injection prevention
            $blacklist = array('DROP', 'DELETE', 'UPDATE', 'INSERT', 'TRUNCATE', 'ALTER', '--');
            foreach ($blacklist as $term) {
                if (stripos($input, $term) !== false) {
                    return false;
                }
            }
            return true;
            
        default:
            return true;
    }
}
