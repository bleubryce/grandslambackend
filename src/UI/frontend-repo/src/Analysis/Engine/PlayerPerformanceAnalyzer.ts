import { PoolClient } from 'pg';
import { BaseAnalyzer } from './BaseAnalyzer';

export class PlayerPerformanceAnalyzer extends BaseAnalyzer {
  constructor(dbConnection: PoolClient) {
    super(dbConnection);
  }

  public async analyze(playerId: number): Promise<any> {
    const results = {
      batting: await this.getBattingStats(playerId),
      pitching: await this.getPitchingStats(playerId),
      trends: await this.getPerformanceTrends(playerId),
      splits: await this.getPerformanceSplits(playerId)
    };

    return results;
  }

  private async getBattingStats(playerId: number): Promise<any> {
    const query = `
      SELECT 
        COUNT(*) as games_played,
        SUM(at_bats) as at_bats,
        SUM(hits) as hits,
        SUM(home_runs) as home_runs,
        SUM(runs_batted_in) as rbi,
        SUM(walks) as walks,
        SUM(strikeouts) as strikeouts,
        ROUND(CAST(SUM(hits) AS DECIMAL) / NULLIF(SUM(at_bats), 0), 3) as batting_avg
      FROM batting_stats
      WHERE player_id = $1
    `;
    
    const results = await this.executeQuery(query, [playerId]);
    return results[0];
  }

  private async getPitchingStats(playerId: number): Promise<any> {
    const query = `
      SELECT 
        COUNT(*) as games_pitched,
        SUM(innings_pitched) as innings_pitched,
        SUM(earned_runs) as earned_runs,
        SUM(strikeouts) as strikeouts,
        SUM(walks) as walks,
        ROUND(CAST(SUM(earned_runs) * 9 AS DECIMAL) / NULLIF(SUM(innings_pitched), 0), 2) as era
      FROM pitching_stats
      WHERE player_id = $1
    `;
    
    const results = await this.executeQuery(query, [playerId]);
    return results[0];
  }

  private async getPerformanceTrends(playerId: number): Promise<any> {
    const query = `
      SELECT 
        date_trunc('month', game_date) as month,
        COUNT(*) as games,
        ROUND(AVG(CAST(hits AS DECIMAL) / NULLIF(at_bats, 0)), 3) as monthly_avg
      FROM batting_stats
      WHERE player_id = $1
      GROUP BY date_trunc('month', game_date)
      ORDER BY month
    `;
    
    return await this.executeQuery(query, [playerId]);
  }

  private async getPerformanceSplits(playerId: number): Promise<any> {
    const query = `
      SELECT 
        opponent_handedness,
        COUNT(*) as at_bats,
        SUM(hits) as hits,
        ROUND(CAST(SUM(hits) AS DECIMAL) / NULLIF(COUNT(*), 0), 3) as split_avg
      FROM batting_stats
      WHERE player_id = $1
      GROUP BY opponent_handedness
    `;
    
    return await this.executeQuery(query, [playerId]);
  }
} 