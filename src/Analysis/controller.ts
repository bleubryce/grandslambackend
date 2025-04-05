import { Request, Response } from 'express';
import { AnalysisEngine } from './Engine/AnalysisEngine';
import { logger } from '../utils/logger';

export class AnalysisController {
  private analysisEngine: AnalysisEngine;

  constructor(analysisEngine: AnalysisEngine) {
    this.analysisEngine = analysisEngine;
  }

  async analyzeTeam(req: Request, res: Response): Promise<void> {
    try {
      const teamId = parseInt(req.params.teamId, 10);
      if (isNaN(teamId)) {
        res.status(400).json({ error: 'Invalid team ID' });
        return;
      }
      const results = await this.analysisEngine.analyzeTeam(teamId);
      res.json(results);
      logger.info(`Team analysis completed for team ${teamId}`);
    } catch (error) {
      logger.error('Team analysis error:', error);
      res.status(500).json({ error: 'Internal server error' });
    }
  }

  async analyzePlayer(req: Request, res: Response): Promise<void> {
    try {
      const playerId = parseInt(req.params.playerId, 10);
      if (isNaN(playerId)) {
        res.status(400).json({ error: 'Invalid player ID' });
        return;
      }
      const results = await this.analysisEngine.analyzePlayer(playerId);
      res.json(results);
      logger.info(`Player analysis completed for player ${playerId}`);
    } catch (error) {
      logger.error('Player analysis error:', error);
      res.status(500).json({ error: 'Internal server error' });
    }
  }

  async analyzeGame(req: Request, res: Response): Promise<void> {
    try {
      const gameId = parseInt(req.params.gameId, 10);
      if (isNaN(gameId)) {
        res.status(400).json({ error: 'Invalid game ID' });
        return;
      }
      const results = await this.analysisEngine.analyzeGame(gameId);
      res.json(results);
      logger.info(`Game analysis completed for game ${gameId}`);
    } catch (error) {
      logger.error('Game analysis error:', error);
      res.status(500).json({ error: 'Internal server error' });
    }
  }

  async runModel(req: Request, res: Response): Promise<void> {
    try {
      const modelResults = await this.analysisEngine.analyze(req.body);
      res.json({
        version: '1.0.0',
        timestamp: new Date().toISOString(),
        results: modelResults
      });
      logger.info('Model analysis completed successfully');
    } catch (error) {
      logger.error('Model analysis error:', error);
      res.status(500).json({ error: 'Internal server error' });
    }
  }
} 