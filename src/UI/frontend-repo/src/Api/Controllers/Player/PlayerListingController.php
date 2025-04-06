
<?php
namespace BaseballAnalytics\Api\Controllers\Player;

use BaseballAnalytics\Api\ApiResponse;
use PDO;

class PlayerListingController extends BasePlayerController {
    
    public function listPlayers(): void {
        if (!$this->checkAuth()) {
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                p.id,
                p.first_name,
                p.last_name,
                p.jersey_number,
                p.position,
                p.active,
                t.name as team_name,
                t.id as team_id
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.id
            ORDER BY p.active DESC, p.last_name, p.first_name
        ");

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch players')->send();
            return;
        }

        $players = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['players' => $players])->send();
    }

    public function searchPlayers(): void {
        if (!$this->checkAuth()) {
            return;
        }

        if (!$this->middleware->validateContentType()) {
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data) {
            return;
        }

        $conditions = [];
        $params = [];

        if (isset($data['name'])) {
            $conditions[] = "(p.first_name ILIKE :name OR p.last_name ILIKE :name)";
            $params[':name'] = '%' . $data['name'] . '%';
        }

        if (isset($data['position'])) {
            $conditions[] = "p.position = :position";
            $params[':position'] = $data['position'];
        }

        if (isset($data['team_id'])) {
            $conditions[] = "p.team_id = :team_id";
            $params[':team_id'] = $data['team_id'];
        }

        if (isset($data['active'])) {
            $conditions[] = "p.active = :active";
            $params[':active'] = $data['active'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $query = $this->db->prepare("
            SELECT 
                p.id,
                p.first_name,
                p.last_name,
                p.jersey_number,
                p.position,
                p.active,
                t.name as team_name,
                t.id as team_id
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.id
            {$whereClause}
            ORDER BY p.last_name, p.first_name
        ");

        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to search players')->send();
            return;
        }

        $players = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['players' => $players])->send();
    }
}
