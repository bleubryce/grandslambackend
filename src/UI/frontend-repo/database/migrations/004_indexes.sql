-- Baseball Analytics System - Additional Indexes
-- Migration: 004_indexes.sql

-- Player Stats Indexes
CREATE INDEX idx_batting_stats_player_date ON batting_stats (player_id, (DATE(created_at)));
CREATE INDEX idx_pitching_stats_player_date ON pitching_stats (player_id, (DATE(created_at)));
CREATE INDEX idx_fielding_stats_player_date ON fielding_stats (player_id, (DATE(created_at)));

-- Team Stats Indexes
CREATE INDEX idx_batting_stats_team_date ON batting_stats (team_id, (DATE(created_at)));
CREATE INDEX idx_pitching_stats_team_date ON pitching_stats (team_id, (DATE(created_at)));
CREATE INDEX idx_fielding_stats_team_date ON fielding_stats (team_id, (DATE(created_at)));

-- Game Search Indexes
CREATE INDEX idx_games_teams ON games (home_team_id, away_team_id);
CREATE INDEX idx_games_date_teams ON games (game_date, home_team_id, away_team_id);

-- Player Team History Index
CREATE INDEX idx_player_teams_active ON player_teams (player_id, status) WHERE status = 'active';

-- Scouting Report Indexes
CREATE INDEX idx_scouting_reports_grades ON scouting_reports 
    (player_id, report_date, overall_grade, hitting_grade, power_grade);

-- Advanced Metrics Indexes
CREATE INDEX idx_advanced_metrics_type ON advanced_metrics (player_id, metric_type, calculation_date);

-- Injury Tracking Indexes
CREATE INDEX idx_injuries_active ON injuries (player_id, injury_date) 
    WHERE return_date IS NULL;

-- User Management Indexes
CREATE INDEX idx_users_role ON users (role);
CREATE INDEX idx_users_active ON users (id) WHERE is_active = true;

-- Full Text Search Indexes
CREATE INDEX idx_players_name_search ON players 
    USING gin(to_tsvector('english', first_name || ' ' || last_name));

CREATE INDEX idx_teams_name_search ON teams 
    USING gin(to_tsvector('english', name || ' ' || location)); 