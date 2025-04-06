-- Baseball Analytics System - Views
-- Migration: 002_views.sql

-- Player Season Batting Stats View
CREATE OR REPLACE VIEW player_season_batting_stats AS
SELECT 
    p.id AS player_id,
    p.first_name,
    p.last_name,
    t.id AS team_id,
    t.name AS team_name,
    EXTRACT(YEAR FROM g.game_date) AS season,
    COUNT(DISTINCT g.id) AS games_played,
    SUM(bs.plate_appearances) AS plate_appearances,
    SUM(bs.at_bats) AS at_bats,
    SUM(bs.hits) AS hits,
    SUM(bs.doubles) AS doubles,
    SUM(bs.triples) AS triples,
    SUM(bs.home_runs) AS home_runs,
    SUM(bs.runs_batted_in) AS rbi,
    SUM(bs.walks) AS walks,
    SUM(bs.strikeouts) AS strikeouts,
    SUM(bs.stolen_bases) AS stolen_bases,
    SUM(bs.caught_stealing) AS caught_stealing,
    CASE 
        WHEN SUM(bs.at_bats) = 0 THEN 0
        ELSE ROUND(CAST(SUM(bs.hits) AS DECIMAL) / SUM(bs.at_bats), 3)
    END AS batting_average,
    CASE 
        WHEN (SUM(bs.at_bats) + SUM(bs.walks) + SUM(bs.hit_by_pitch)) = 0 THEN 0
        ELSE ROUND(CAST(SUM(bs.hits) + SUM(bs.walks) + SUM(bs.hit_by_pitch) AS DECIMAL) / 
            (SUM(bs.at_bats) + SUM(bs.walks) + SUM(bs.hit_by_pitch)), 3)
    END AS on_base_percentage,
    CASE 
        WHEN SUM(bs.at_bats) = 0 THEN 0
        ELSE ROUND(CAST(SUM(bs.hits) + SUM(bs.doubles) + 2 * SUM(bs.triples) + 3 * SUM(bs.home_runs) AS DECIMAL) / 
            SUM(bs.at_bats), 3)
    END AS slugging_percentage
FROM 
    players p
    JOIN batting_stats bs ON p.id = bs.player_id
    JOIN games g ON bs.game_id = g.id
    JOIN teams t ON bs.team_id = t.id
GROUP BY 
    p.id, p.first_name, p.last_name, t.id, t.name, EXTRACT(YEAR FROM g.game_date);

-- Player Season Pitching Stats View
CREATE OR REPLACE VIEW player_season_pitching_stats AS
SELECT 
    p.id AS player_id,
    p.first_name,
    p.last_name,
    t.id AS team_id,
    t.name AS team_name,
    EXTRACT(YEAR FROM g.game_date) AS season,
    COUNT(DISTINCT g.id) AS games_pitched,
    SUM(ps.innings_pitched) AS innings_pitched,
    SUM(ps.hits_allowed) AS hits_allowed,
    SUM(ps.runs_allowed) AS runs_allowed,
    SUM(ps.earned_runs) AS earned_runs,
    SUM(ps.walks_allowed) AS walks,
    SUM(ps.strikeouts) AS strikeouts,
    SUM(ps.home_runs_allowed) AS home_runs_allowed,
    COUNT(CASE WHEN ps.win THEN 1 END) AS wins,
    COUNT(CASE WHEN ps.loss THEN 1 END) AS losses,
    COUNT(CASE WHEN ps.save THEN 1 END) AS saves,
    CASE 
        WHEN SUM(ps.innings_pitched) = 0 THEN 0
        ELSE ROUND(CAST(9 * SUM(ps.earned_runs) AS DECIMAL) / SUM(ps.innings_pitched), 2)
    END AS era,
    CASE 
        WHEN SUM(ps.innings_pitched) = 0 THEN 0
        ELSE ROUND(CAST(SUM(ps.walks_allowed) + SUM(ps.hits_allowed) AS DECIMAL) / SUM(ps.innings_pitched), 2)
    END AS whip
FROM 
    players p
    JOIN pitching_stats ps ON p.id = ps.player_id
    JOIN games g ON ps.game_id = g.id
    JOIN teams t ON ps.team_id = t.id
GROUP BY 
    p.id, p.first_name, p.last_name, t.id, t.name, EXTRACT(YEAR FROM g.game_date);

-- Player Season Fielding Stats View
CREATE OR REPLACE VIEW player_season_fielding_stats AS
SELECT 
    p.id AS player_id,
    p.first_name,
    p.last_name,
    t.id AS team_id,
    t.name AS team_name,
    fs.position,
    EXTRACT(YEAR FROM g.game_date) AS season,
    COUNT(DISTINCT g.id) AS games_played,
    SUM(fs.innings_played) AS innings_played,
    SUM(fs.putouts) AS putouts,
    SUM(fs.assists) AS assists,
    SUM(fs.errors) AS errors,
    SUM(fs.double_plays) AS double_plays,
    CASE 
        WHEN (SUM(fs.putouts) + SUM(fs.assists) + SUM(fs.errors)) = 0 THEN 0
        ELSE ROUND(CAST(SUM(fs.putouts) + SUM(fs.assists) AS DECIMAL) / 
            (SUM(fs.putouts) + SUM(fs.assists) + SUM(fs.errors)), 3)
    END AS fielding_percentage
FROM 
    players p
    JOIN fielding_stats fs ON p.id = fs.player_id
    JOIN games g ON fs.game_id = g.id
    JOIN teams t ON fs.team_id = t.id
GROUP BY 
    p.id, p.first_name, p.last_name, t.id, t.name, fs.position, EXTRACT(YEAR FROM g.game_date);

-- Team Season Stats View
CREATE OR REPLACE VIEW team_season_stats AS
SELECT 
    t.id AS team_id,
    t.name AS team_name,
    t.league,
    t.division,
    EXTRACT(YEAR FROM g.game_date) AS season,
    COUNT(DISTINCT g.id) AS games_played,
    COUNT(DISTINCT CASE WHEN 
        (g.home_team_id = t.id AND ps_home.win) OR 
        (g.away_team_id = t.id AND ps_away.win) 
    THEN g.id END) AS wins,
    COUNT(DISTINCT CASE WHEN 
        (g.home_team_id = t.id AND ps_home.loss) OR 
        (g.away_team_id = t.id AND ps_away.loss) 
    THEN g.id END) AS losses,
    ROUND(CAST(COUNT(DISTINCT CASE WHEN 
        (g.home_team_id = t.id AND ps_home.win) OR 
        (g.away_team_id = t.id AND ps_away.win) 
    THEN g.id END) AS DECIMAL) / 
    NULLIF(COUNT(DISTINCT g.id), 0), 3) AS winning_percentage
FROM 
    teams t
    LEFT JOIN games g ON t.id IN (g.home_team_id, g.away_team_id)
    LEFT JOIN pitching_stats ps_home ON g.id = ps_home.game_id AND g.home_team_id = ps_home.team_id
    LEFT JOIN pitching_stats ps_away ON g.id = ps_away.game_id AND g.away_team_id = ps_away.team_id
GROUP BY 
    t.id, t.name, t.league, t.division, EXTRACT(YEAR FROM g.game_date);

-- Player Career Stats View
CREATE OR REPLACE VIEW player_career_stats AS
SELECT 
    p.id AS player_id,
    p.first_name,
    p.last_name,
    COUNT(DISTINCT EXTRACT(YEAR FROM g.game_date)) AS seasons_played,
    COUNT(DISTINCT g.id) AS total_games,
    SUM(bs.hits) AS career_hits,
    SUM(bs.home_runs) AS career_home_runs,
    SUM(bs.runs_batted_in) AS career_rbi,
    CASE 
        WHEN SUM(bs.at_bats) = 0 THEN 0
        ELSE ROUND(CAST(SUM(bs.hits) AS DECIMAL) / SUM(bs.at_bats), 3)
    END AS career_batting_average,
    SUM(ps.wins) AS career_wins,
    SUM(ps.saves) AS career_saves,
    CASE 
        WHEN SUM(ps.innings_pitched) = 0 THEN 0
        ELSE ROUND(CAST(9 * SUM(ps.earned_runs) AS DECIMAL) / SUM(ps.innings_pitched), 2)
    END AS career_era
FROM 
    players p
    LEFT JOIN batting_stats bs ON p.id = bs.player_id
    LEFT JOIN pitching_stats ps ON p.id = ps.player_id
    LEFT JOIN games g ON bs.game_id = g.id OR ps.game_id = g.id
GROUP BY 
    p.id, p.first_name, p.last_name;

-- Recent Player Performance View (Last 30 Days)
CREATE OR REPLACE VIEW recent_player_performance AS
SELECT 
    p.id AS player_id,
    p.first_name,
    p.last_name,
    t.name AS current_team,
    COUNT(DISTINCT g.id) AS games_played_last_30_days,
    SUM(bs.hits) AS hits,
    SUM(bs.home_runs) AS home_runs,
    CASE 
        WHEN SUM(bs.at_bats) = 0 THEN 0
        ELSE ROUND(CAST(SUM(bs.hits) AS DECIMAL) / SUM(bs.at_bats), 3)
    END AS batting_average_last_30_days,
    SUM(ps.strikeouts) AS pitching_strikeouts,
    CASE 
        WHEN SUM(ps.innings_pitched) = 0 THEN 0
        ELSE ROUND(CAST(9 * SUM(ps.earned_runs) AS DECIMAL) / SUM(ps.innings_pitched), 2)
    END AS era_last_30_days
FROM 
    players p
    LEFT JOIN batting_stats bs ON p.id = bs.player_id
    LEFT JOIN pitching_stats ps ON p.id = ps.player_id
    LEFT JOIN games g ON bs.game_id = g.id OR ps.game_id = g.id
    LEFT JOIN teams t ON bs.team_id = t.id OR ps.team_id = t.id
WHERE 
    g.game_date >= CURRENT_DATE - INTERVAL '30 days'
GROUP BY 
    p.id, p.first_name, p.last_name, t.name; 