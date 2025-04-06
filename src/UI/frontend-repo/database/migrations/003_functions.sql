-- Baseball Analytics System - Functions
-- Migration: 003_functions.sql

-- Calculate Player Batting Average
CREATE OR REPLACE FUNCTION calculate_batting_average(
    p_hits INTEGER,
    p_at_bats INTEGER
) RETURNS DECIMAL(4,3) AS $$
BEGIN
    IF p_at_bats = 0 THEN
        RETURN 0.000;
    END IF;
    RETURN ROUND(CAST(p_hits AS DECIMAL) / p_at_bats, 3);
END;
$$ LANGUAGE plpgsql;

-- Calculate ERA (Earned Run Average)
CREATE OR REPLACE FUNCTION calculate_era(
    p_earned_runs INTEGER,
    p_innings_pitched DECIMAL
) RETURNS DECIMAL(4,2) AS $$
BEGIN
    IF p_innings_pitched = 0 THEN
        RETURN 0.00;
    END IF;
    RETURN ROUND((p_earned_runs * 9) / p_innings_pitched, 2);
END;
$$ LANGUAGE plpgsql;

-- Get Player Current Team
CREATE OR REPLACE FUNCTION get_player_current_team(p_player_id UUID)
RETURNS TABLE (
    team_id UUID,
    team_name VARCHAR,
    jersey_number INTEGER,
    start_date DATE
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        t.id,
        t.name,
        pt.jersey_number,
        pt.start_date
    FROM player_teams pt
    JOIN teams t ON pt.team_id = t.id
    WHERE pt.player_id = p_player_id
    AND pt.end_date IS NULL
    AND pt.status = 'active'
    ORDER BY pt.start_date DESC
    LIMIT 1;
END;
$$ LANGUAGE plpgsql;

-- Calculate Player Age
CREATE OR REPLACE FUNCTION calculate_player_age(
    p_birth_date DATE,
    p_reference_date DATE DEFAULT CURRENT_DATE
) RETURNS INTEGER AS $$
BEGIN
    RETURN date_part('year', age(p_reference_date, p_birth_date));
END;
$$ LANGUAGE plpgsql;

-- Get Player Season Stats
CREATE OR REPLACE FUNCTION get_player_season_stats(
    p_player_id UUID,
    p_season INTEGER DEFAULT EXTRACT(YEAR FROM CURRENT_DATE)
) RETURNS TABLE (
    games_played INTEGER,
    batting_average DECIMAL(4,3),
    home_runs INTEGER,
    rbi INTEGER,
    ops DECIMAL(4,3),
    era DECIMAL(4,2),
    wins INTEGER,
    losses INTEGER,
    saves INTEGER,
    innings_pitched DECIMAL(4,1)
) AS $$
BEGIN
    RETURN QUERY
    WITH batting AS (
        SELECT 
            COUNT(DISTINCT bs.game_id) AS games,
            calculate_batting_average(SUM(bs.hits), SUM(bs.at_bats)) AS avg,
            SUM(bs.home_runs) AS hr,
            SUM(bs.runs_batted_in) AS rbi_count,
            CASE 
                WHEN SUM(bs.at_bats) = 0 THEN 0
                ELSE ROUND(
                    (CAST(SUM(bs.hits) + SUM(bs.walks) + SUM(bs.hit_by_pitch) AS DECIMAL) / 
                    NULLIF(SUM(bs.at_bats) + SUM(bs.walks) + SUM(bs.hit_by_pitch), 0)) +
                    (CAST(SUM(bs.hits) + SUM(bs.doubles) + 2 * SUM(bs.triples) + 3 * SUM(bs.home_runs) AS DECIMAL) / 
                    NULLIF(SUM(bs.at_bats), 0)), 3)
            END AS ops_calc
        FROM batting_stats bs
        JOIN games g ON bs.game_id = g.id
        WHERE bs.player_id = p_player_id
        AND EXTRACT(YEAR FROM g.game_date) = p_season
    ),
    pitching AS (
        SELECT 
            COUNT(DISTINCT ps.game_id) FILTER (WHERE ps.win) AS win_count,
            COUNT(DISTINCT ps.game_id) FILTER (WHERE ps.loss) AS loss_count,
            COUNT(DISTINCT ps.game_id) FILTER (WHERE ps.save) AS save_count,
            SUM(ps.innings_pitched) AS ip,
            calculate_era(SUM(ps.earned_runs), SUM(ps.innings_pitched)) AS era_calc
        FROM pitching_stats ps
        JOIN games g ON ps.game_id = g.id
        WHERE ps.player_id = p_player_id
        AND EXTRACT(YEAR FROM g.game_date) = p_season
    )
    SELECT
        GREATEST(batting.games, COUNT(DISTINCT fs.game_id)),
        batting.avg,
        batting.hr,
        batting.rbi_count,
        batting.ops_calc,
        pitching.era_calc,
        pitching.win_count,
        pitching.loss_count,
        pitching.save_count,
        pitching.ip
    FROM batting
    CROSS JOIN pitching
    LEFT JOIN fielding_stats fs ON fs.player_id = p_player_id
    LEFT JOIN games g ON fs.game_id = g.id
    WHERE EXTRACT(YEAR FROM g.game_date) = p_season
    GROUP BY 
        batting.games, batting.avg, batting.hr, batting.rbi_count, batting.ops_calc,
        pitching.era_calc, pitching.win_count, pitching.loss_count, pitching.save_count, pitching.ip;
END;
$$ LANGUAGE plpgsql;

-- Get Team Season Record
CREATE OR REPLACE FUNCTION get_team_season_record(
    p_team_id UUID,
    p_season INTEGER DEFAULT EXTRACT(YEAR FROM CURRENT_DATE)
) RETURNS TABLE (
    wins INTEGER,
    losses INTEGER,
    winning_percentage DECIMAL(4,3)
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(DISTINCT CASE WHEN 
            (g.home_team_id = p_team_id AND ps_home.win) OR 
            (g.away_team_id = p_team_id AND ps_away.win) 
        THEN g.id END) AS win_count,
        COUNT(DISTINCT CASE WHEN 
            (g.home_team_id = p_team_id AND ps_home.loss) OR 
            (g.away_team_id = p_team_id AND ps_away.loss) 
        THEN g.id END) AS loss_count,
        ROUND(CAST(COUNT(DISTINCT CASE WHEN 
            (g.home_team_id = p_team_id AND ps_home.win) OR 
            (g.away_team_id = p_team_id AND ps_away.win) 
        THEN g.id END) AS DECIMAL) / 
        NULLIF(COUNT(DISTINCT g.id), 0), 3) AS win_pct
    FROM games g
    LEFT JOIN pitching_stats ps_home ON g.id = ps_home.game_id AND g.home_team_id = ps_home.team_id
    LEFT JOIN pitching_stats ps_away ON g.id = ps_away.game_id AND g.away_team_id = ps_away.team_id
    WHERE (g.home_team_id = p_team_id OR g.away_team_id = p_team_id)
    AND EXTRACT(YEAR FROM g.game_date) = p_season;
END;
$$ LANGUAGE plpgsql;

-- Calculate Player Streak
CREATE OR REPLACE FUNCTION calculate_player_streak(
    p_player_id UUID,
    p_stat_type VARCHAR -- 'hitting', 'pitching_win', etc.
) RETURNS INTEGER AS $$
DECLARE
    current_streak INTEGER := 0;
    max_streak INTEGER := 0;
    last_game_date DATE;
    has_stat BOOLEAN;
BEGIN
    FOR last_game_date, has_stat IN
        SELECT 
            g.game_date,
            CASE 
                WHEN p_stat_type = 'hitting' THEN
                    bs.hits > 0
                WHEN p_stat_type = 'pitching_win' THEN
                    ps.win
                ELSE FALSE
            END AS stat_check
        FROM games g
        LEFT JOIN batting_stats bs ON g.id = bs.game_id AND bs.player_id = p_player_id
        LEFT JOIN pitching_stats ps ON g.id = ps.game_id AND ps.player_id = p_player_id
        WHERE bs.player_id = p_player_id OR ps.player_id = p_player_id
        ORDER BY g.game_date DESC
    LOOP
        IF has_stat THEN
            current_streak := current_streak + 1;
            max_streak := GREATEST(max_streak, current_streak);
        ELSE
            EXIT;
        END IF;
    END LOOP;
    
    RETURN current_streak;
END;
$$ LANGUAGE plpgsql;

-- Add New Player
CREATE OR REPLACE FUNCTION add_new_player(
    p_first_name VARCHAR,
    p_last_name VARCHAR,
    p_birth_date DATE,
    p_nationality VARCHAR,
    p_height_cm INTEGER,
    p_weight_kg DECIMAL,
    p_bats VARCHAR,
    p_throws VARCHAR,
    p_primary_position VARCHAR,
    p_team_id UUID,
    p_jersey_number INTEGER
) RETURNS UUID AS $$
DECLARE
    new_player_id UUID;
BEGIN
    -- Insert player
    INSERT INTO players (
        first_name, last_name, birth_date, nationality,
        height_cm, weight_kg, bats, throws, primary_position
    ) VALUES (
        p_first_name, p_last_name, p_birth_date, p_nationality,
        p_height_cm, p_weight_kg, p_bats, p_throws, p_primary_position
    ) RETURNING id INTO new_player_id;
    
    -- Add to team if specified
    IF p_team_id IS NOT NULL THEN
        INSERT INTO player_teams (
            player_id, team_id, jersey_number, start_date, status
        ) VALUES (
            new_player_id, p_team_id, p_jersey_number, CURRENT_DATE, 'active'
        );
    END IF;
    
    RETURN new_player_id;
END;
$$ LANGUAGE plpgsql;

-- Record Game Stats
CREATE OR REPLACE FUNCTION record_game_stats(
    p_game_id UUID,
    p_player_id UUID,
    p_team_id UUID,
    p_batting_stats JSON DEFAULT NULL,
    p_pitching_stats JSON DEFAULT NULL,
    p_fielding_stats JSON DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    -- Record batting stats if provided
    IF p_batting_stats IS NOT NULL THEN
        INSERT INTO batting_stats (
            game_id, player_id, team_id,
            plate_appearances, at_bats, runs, hits,
            doubles, triples, home_runs, runs_batted_in,
            walks, strikeouts, stolen_bases, caught_stealing,
            hit_by_pitch, sacrifice_hits, sacrifice_flies
        ) VALUES (
            p_game_id, p_player_id, p_team_id,
            (p_batting_stats->>'plate_appearances')::INTEGER,
            (p_batting_stats->>'at_bats')::INTEGER,
            (p_batting_stats->>'runs')::INTEGER,
            (p_batting_stats->>'hits')::INTEGER,
            (p_batting_stats->>'doubles')::INTEGER,
            (p_batting_stats->>'triples')::INTEGER,
            (p_batting_stats->>'home_runs')::INTEGER,
            (p_batting_stats->>'runs_batted_in')::INTEGER,
            (p_batting_stats->>'walks')::INTEGER,
            (p_batting_stats->>'strikeouts')::INTEGER,
            (p_batting_stats->>'stolen_bases')::INTEGER,
            (p_batting_stats->>'caught_stealing')::INTEGER,
            (p_batting_stats->>'hit_by_pitch')::INTEGER,
            (p_batting_stats->>'sacrifice_hits')::INTEGER,
            (p_batting_stats->>'sacrifice_flies')::INTEGER
        );
    END IF;
    
    -- Record pitching stats if provided
    IF p_pitching_stats IS NOT NULL THEN
        INSERT INTO pitching_stats (
            game_id, player_id, team_id,
            innings_pitched, hits_allowed, runs_allowed,
            earned_runs, walks_allowed, strikeouts,
            home_runs_allowed, pitches_thrown, strikes_thrown,
            win, loss, save
        ) VALUES (
            p_game_id, p_player_id, p_team_id,
            (p_pitching_stats->>'innings_pitched')::DECIMAL,
            (p_pitching_stats->>'hits_allowed')::INTEGER,
            (p_pitching_stats->>'runs_allowed')::INTEGER,
            (p_pitching_stats->>'earned_runs')::INTEGER,
            (p_pitching_stats->>'walks_allowed')::INTEGER,
            (p_pitching_stats->>'strikeouts')::INTEGER,
            (p_pitching_stats->>'home_runs_allowed')::INTEGER,
            (p_pitching_stats->>'pitches_thrown')::INTEGER,
            (p_pitching_stats->>'strikes_thrown')::INTEGER,
            (p_pitching_stats->>'win')::BOOLEAN,
            (p_pitching_stats->>'loss')::BOOLEAN,
            (p_pitching_stats->>'save')::BOOLEAN
        );
    END IF;
    
    -- Record fielding stats if provided
    IF p_fielding_stats IS NOT NULL THEN
        INSERT INTO fielding_stats (
            game_id, player_id, team_id,
            position, innings_played, putouts,
            assists, errors, double_plays,
            passed_balls, stolen_bases_allowed, caught_stealing
        ) VALUES (
            p_game_id, p_player_id, p_team_id,
            p_fielding_stats->>'position',
            (p_fielding_stats->>'innings_played')::DECIMAL,
            (p_fielding_stats->>'putouts')::INTEGER,
            (p_fielding_stats->>'assists')::INTEGER,
            (p_fielding_stats->>'errors')::INTEGER,
            (p_fielding_stats->>'double_plays')::INTEGER,
            (p_fielding_stats->>'passed_balls')::INTEGER,
            (p_fielding_stats->>'stolen_bases_allowed')::INTEGER,
            (p_fielding_stats->>'caught_stealing')::INTEGER
        );
    END IF;
END;
$$ LANGUAGE plpgsql; 