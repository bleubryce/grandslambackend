import { Router } from 'express';
import { AnalysisController } from './controller';
import { AnalysisEngine } from './Engine/AnalysisEngine';
import { DatabaseManager } from '../Database/DatabaseManager';
import { config } from '../config';

const router: Router = Router();

// Initialize database and analysis engine
const dbManager = new DatabaseManager(config.database);
let analysisController: AnalysisController;

(async () => {
  try {
    const dbClient = await dbManager.getConnection();
    const analysisEngine = new AnalysisEngine(dbClient, config.analysis);
    analysisController = new AnalysisController(analysisEngine);

    // Analysis routes
    router.get('/team/:teamId', analysisController.analyzeTeam.bind(analysisController));
    router.get('/player/:playerId', analysisController.analyzePlayer.bind(analysisController));
    router.get('/game/:gameId', analysisController.analyzeGame.bind(analysisController));
    router.post('/model', analysisController.runModel.bind(analysisController));
  } catch (error) {
    console.error('Failed to initialize analysis routes:', error);
    process.exit(1);
  }
})();

export default router; 