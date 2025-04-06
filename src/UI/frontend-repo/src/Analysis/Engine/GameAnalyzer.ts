import { PoolClient } from 'pg';
import { BaseAnalyzer } from './BaseAnalyzer';

export class GameAnalyzer extends BaseAnalyzer {
  constructor(dbConnection: PoolClient) {
    super(dbConnection);
  }

  public async analyze(gameId: number): Promise<any> {
    const results = {
      summary: await this.getGameSummary(gameId),
      scoring: await this.getScoringBreakdown(gameId),
      performance: await this.getTeamPerformance(gameId),
      keyPlays: await this.getKeyPlays(gameId)
    };

    return results;
  }

  private async getGameSummary(gameId: number): Promise<any> {
    const query = `
      SELECT 
        g.game_date,
        g.home_team_id,
        g.away_team_id,
        g.home_score,
        g.away_score,
        g.venue,
        g.attendance,
        g.duration
      FROM games g
      WHERE g.id = $1
    `;
    
    const results = await this.executeQuery(query, [gameId]);
    return results[0];
  }

  private async getScoringBreakdown(gameId: number): Promise<any> {
    const query = `
      SELECT 
        inning,
        half_inning,
        runs_scored,
        hits,
        errors
      FROM inning_scores
      WHERE game_id = $1
      ORDER BY inning, CASE WHEN half_inning = 'top' THEN 1 ELSE 2 END
    `;
    
    return await this.executeQuery(query, [gameId]);
  }

  private async getTeamPerformance(gameId: number): Promise<any> {
    const query = `
      SELECT 
        team_id,
        SUM(hits) as total_hits,
        SUM(errors) as total_errors,
        SUM(left_on_base) as left_on_base,
        SUM(CASE WHEN hit_type = 'HR' THEN 1 ELSE 0 END) as home_runs
      FROM team_game_stats
      WHERE game_id = $1
      GROUP BY team_id
    `;
    
    return await this.executeQuery(query, [gameId]);
  }

  private async getKeyPlays(gameId: number): Promise<any> {
    const query = `
      SELECT 
        inning,
        half_inning,
        description,
        run_value,
        win_probability_added
      FROM game_events
      WHERE game_id = $1 AND is_key_play = true
      ORDER BY inning, CASE WHEN half_inning = 'top' THEN 1 ELSE 2 END
    `;
    
    return await this.executeQuery(query, [gameId]);
  }
} 