<?php
// Include authentication file
require_once 'auth.php';

// Include API functions
require_once 'api/functions.php';

// Require authentication for this page
requireAuth();

// Get current user
$user = getCurrentUser();

// Process API requests
$response = array('success' => false, 'message' => 'Invalid request', 'data' => null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get request data
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    if (isset($requestData['action'])) {
        switch ($requestData['action']) {
            case 'get_player_data':
                $playerId = $requestData['player_id'] ?? null;
                $response['data'] = getPlayerData($playerId);
                $response['success'] = !isset($response['data']['error']);
                if (isset($response['data']['error'])) {
                    $response['message'] = $response['data']['error'];
                    unset($response['data']['error']);
                } else {
                    $response['message'] = 'Player data retrieved successfully';
                }
                break;
                
            case 'get_team_data':
                $teamId = $requestData['team_id'] ?? null;
                $response['data'] = getTeamData($teamId);
                $response['success'] = !isset($response['data']['error']);
                if (isset($response['data']['error'])) {
                    $response['message'] = $response['data']['error'];
                    unset($response['data']['error']);
                } else {
                    $response['message'] = 'Team data retrieved successfully';
                }
                break;
                
            case 'get_statcast_data':
                $params = $requestData['params'] ?? array();
                $response['data'] = getStatcastData($params);
                $response['success'] = !isset($response['data']['error']);
                if (isset($response['data']['error'])) {
                    $response['message'] = $response['data']['error'];
                    unset($response['data']['error']);
                } else {
                    $response['message'] = 'Statcast data retrieved successfully';
                }
                break;
                
            case 'get_model_predictions':
                $modelType = $requestData['model_type'] ?? null;
                $parameters = $requestData['parameters'] ?? array();
                
                if (!$modelType) {
                    $response['message'] = 'Model type is required';
                } else {
                    $response['data'] = getModelPredictions($modelType, $parameters);
                    $response['success'] = !isset($response['data']['error']);
                    if (isset($response['data']['error'])) {
                        $response['message'] = $response['data']['error'];
                        unset($response['data']['error']);
                    } else {
                        $response['message'] = 'Model predictions retrieved successfully';
                    }
                }
                break;
                
            case 'run_custom_query':
                // Check if user is admin
                $isAdmin = ($user['role'] === 'admin');
                
                if (!$isAdmin) {
                    $response['message'] = 'Unauthorized access';
                } else {
                    $sql = $requestData['sql'] ?? '';
                    
                    if (empty($sql)) {
                        $response['message'] = 'SQL query is required';
                    } else if (!validateInput($sql, 'sql')) {
                        $response['message'] = 'Invalid SQL query';
                    } else {
                        $response['data'] = runCustomQuery($sql, true);
                        $response['success'] = !isset($response['data']['error']);
                        if (isset($response['data']['error'])) {
                            $response['message'] = $response['data']['error'];
                            unset($response['data']['error']);
                        } else {
                            $response['message'] = 'Query executed successfully';
                        }
                    }
                }
                break;
                
            case 'generate_report':
                $reportType = $requestData['report_type'] ?? null;
                $parameters = $requestData['parameters'] ?? array();
                
                if (!$reportType) {
                    $response['message'] = 'Report type is required';
                } else {
                    $response['data'] = generateReport($reportType, $parameters);
                    $response['success'] = !isset($response['data']['error']);
                    if (isset($response['data']['error'])) {
                        $response['message'] = $response['data']['error'];
                        unset($response['data']['error']);
                    } else {
                        $response['message'] = 'Report generated successfully';
                    }
                }
                break;
                
            case 'get_system_status':
                $response['data'] = getSystemStatus();
                $response['success'] = true;
                $response['message'] = 'System status retrieved successfully';
                break;
                
            case 'get_recent_activity':
                $limit = $requestData['limit'] ?? 5;
                $response['data'] = getRecentActivity($limit);
                $response['success'] = true;
                $response['message'] = 'Recent activity retrieved successfully';
                break;
                
            default:
                $response['message'] = 'Unknown action';
                break;
        }
    }
    
    // Log user activity
    if ($response['success']) {
        logUserActivity($user['id'], $requestData['action'] ?? 'unknown', json_encode($requestData));
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
