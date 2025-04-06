import { PoolClient } from 'pg';
import { AnalysisConfig } from '../../config';
import { TeamPerformanceAnalyzer } from './TeamPerformanceAnalyzer';
import { PlayerPerformanceAnalyzer } from './PlayerPerformanceAnalyzer';
import { GameAnalyzer } from './GameAnalyzer';
import { MachineLearningAnalyzer } from './MachineLearningAnalyzer';

export class AnalysisEngine {
  private dbClient: PoolClient;
  private config: AnalysisConfig;
  private teamAnalyzer: TeamPerformanceAnalyzer;
  private playerAnalyzer: PlayerPerformanceAnalyzer;
  private gameAnalyzer: GameAnalyzer;
  private mlAnalyzer: MachineLearningAnalyzer;

  constructor(dbClient: PoolClient, config: AnalysisConfig) {
    this.dbClient = dbClient;
    this.config = config;
    this.teamAnalyzer = new TeamPerformanceAnalyzer(dbClient);
    this.playerAnalyzer = new PlayerPerformanceAnalyzer(dbClient);
    this.gameAnalyzer = new GameAnalyzer(dbClient);
    this.mlAnalyzer = new MachineLearningAnalyzer(dbClient, config);
  }

  public async analyzeTeam(teamId: number): Promise<any> {
    try {
      const results = await this.teamAnalyzer.analyze(teamId);
      return {
        teamId,
        timestamp: new Date().toISOString(),
        results
      };
    } catch (error) {
      console.error('Error analyzing team:', error);
      throw error;
    }
  }

  public async analyzePlayer(playerId: number): Promise<any> {
    try {
      const results = await this.playerAnalyzer.analyze(playerId);
      return {
        playerId,
        timestamp: new Date().toISOString(),
        results
      };
    } catch (error) {
      console.error('Error analyzing player:', error);
      throw error;
    }
  }

  public async analyzeGame(gameId: number): Promise<any> {
    try {
      const results = await this.gameAnalyzer.analyze(gameId);
      return {
        gameId,
        timestamp: new Date().toISOString(),
        results
      };
    } catch (error) {
      console.error('Error analyzing game:', error);
      throw error;
    }
  }

  public async generatePredictions(data: any): Promise<any> {
    try {
      return await this.mlAnalyzer.generatePredictions(data);
    } catch (error) {
      console.error('Error generating predictions:', error);
      throw error;
    }
  }

  async analyze(data: any) {
    const { type, id } = data;
    
    switch (type) {
      case 'team':
        return await this.teamAnalyzer.analyze(id);
      case 'player':
        return await this.playerAnalyzer.analyze(id);
      case 'game':
        return await this.gameAnalyzer.analyze(id);
      case 'ml':
        return await this.mlAnalyzer.analyze(id);
      default:
        throw new Error(`Unsupported analysis type: ${type}`);
    }
  }
} 