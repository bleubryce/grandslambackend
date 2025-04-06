import { PoolClient } from 'pg';
import { BaseAnalyzer } from './BaseAnalyzer';

export class TeamPerformanceAnalyzer extends BaseAnalyzer {
  constructor(dbConnection: PoolClient) {
    super(dbConnection);
  }

  public async analyze(teamId: number): Promise<any> {
    const results = {
      overall: await this.getOverallPerformance(teamId),
      home: await this.getHomePerformance(teamId),
      away: await this.getAwayPerformance(teamId),
      trends: await this.getPerformanceTrends(teamId)
    };

    return results;
  }

  private async getOverallPerformance(teamId: number): Promise<any> {
    const query = `
      SELECT 
        COUNT(*) as total_games,
        SUM(CASE WHEN home_score > away_score THEN 1 ELSE 0 END) as wins,
        AVG(home_score) as avg_runs_scored,
        AVG(away_score) as avg_runs_allowed
      FROM games
      WHERE home_team_id = $1 OR away_team_id = $1
    `;
    
    const results = await this.executeQuery(query, [teamId]);
    return results[0];
  }

  private async getHomePerformance(teamId: number): Promise<any> {
    const query = `
      SELECT 
        COUNT(*) as home_games,
        SUM(CASE WHEN home_score > away_score THEN 1 ELSE 0 END) as home_wins,
        AVG(home_score) as home_avg_runs_scored,
        AVG(away_score) as home_avg_runs_allowed
      FROM games
      WHERE home_team_id = $1
    `;
    
    const results = await this.executeQuery(query, [teamId]);
    return results[0];
  }

  private async getAwayPerformance(teamId: number): Promise<any> {
    const query = `
      SELECT 
        COUNT(*) as away_games,
        SUM(CASE WHEN away_score > home_score THEN 1 ELSE 0 END) as away_wins,
        AVG(away_score) as away_avg_runs_scored,
        AVG(home_score) as away_avg_runs_allowed
      FROM games
      WHERE away_team_id = $1
    `;
    
    const results = await this.executeQuery(query, [teamId]);
    return results[0];
  }

  private async getPerformanceTrends(teamId: number): Promise<any> {
    const query = `
      SELECT 
        date_trunc('month', game_date) as month,
        COUNT(*) as games,
        SUM(CASE 
          WHEN (home_team_id = $1 AND home_score > away_score) OR 
               (away_team_id = $1 AND away_score > home_score) 
          THEN 1 ELSE 0 END) as wins
      FROM games
      WHERE home_team_id = $1 OR away_team_id = $1
      GROUP BY date_trunc('month', game_date)
      ORDER BY month
    `;
    
    return await this.executeQuery(query, [teamId]);
  }
} 