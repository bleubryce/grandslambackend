import express from 'express';
import cors from 'cors';
import { AnalysisEngine } from './Analysis/Engine/AnalysisEngine';
import { DatabaseManager } from './Database/DatabaseManager';
import { config } from './config';

const app = express();
const port = process.env.PORT || 3000;

// CORS configuration
const corsOptions = {
  origin: process.env.CORS_ORIGIN || 'http://localhost:3000',
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  credentials: true,
  maxAge: 86400 // 24 hours
};

// Middleware
app.use(cors(corsOptions));
app.use(express.json());

// Basic route for testing
app.get('/', (req, res) => {
  res.json({ message: 'Welcome to Baseball Analytics System' });
});

// Health check route
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'healthy', 
    timestamp: new Date().toISOString(),
    modelEnabled: process.env.MODEL_ENABLED === 'true',
    modelVersion: process.env.MODEL_VERSION
  });
});

// Error handling middleware
app.use((err: any, req: express.Request, res: express.Response, next: express.NextFunction) => {
  console.error(err.stack);
  res.status(500).json({ error: 'Something went wrong!' });
});

// Initialize components
const dbManager = new DatabaseManager(config.database);

async function startServer() {
  try {
    // Start server first
    app.listen(Number(port), '0.0.0.0', () => {
      console.log(`Server is running on port ${port}`);
      console.log(`CORS enabled for: ${corsOptions.origin}`);
      console.log(`Model enabled: ${process.env.MODEL_ENABLED}, version: ${process.env.MODEL_VERSION}`);
    });

    // Then try to connect to the database
    const dbClient = await dbManager.getConnection();
    const analysisEngine = new AnalysisEngine(dbClient, config.analysis);

    // Analysis routes
    app.get('/api/analysis/team/:teamId', async (req, res) => {
      try {
        const teamId = parseInt(req.params.teamId);
        const results = await analysisEngine.analyzeTeam(teamId);
        res.json(results);
      } catch (error) {
        res.status(500).json({ error: 'Failed to analyze team data' });
      }
    });

    app.get('/api/analysis/player/:playerId', async (req, res) => {
      try {
        const playerId = parseInt(req.params.playerId);
        const results = await analysisEngine.analyzePlayer(playerId);
        res.json(results);
      } catch (error) {
        res.status(500).json({ error: 'Failed to analyze player data' });
      }
    });

    app.get('/api/analysis/game/:gameId', async (req, res) => {
      try {
        const gameId = parseInt(req.params.gameId);
        const results = await analysisEngine.analyzeGame(gameId);
        res.json(results);
      } catch (error) {
        res.status(500).json({ error: 'Failed to analyze game data' });
      }
    });

    // Model endpoint
    app.post('/api/analysis/model', async (req, res) => {
      try {
        if (process.env.MODEL_ENABLED !== 'true') {
          return res.status(403).json({ error: 'Model analysis is currently disabled' });
        }
        const result = await analysisEngine.analyze(req.body);
        res.json({
          modelVersion: process.env.MODEL_VERSION,
          timestamp: new Date().toISOString(),
          results: result
        });
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'An unknown error occurred';
        res.status(500).json({ error: errorMessage });
      }
    });

  } catch (error) {
    console.error('Failed to connect to database:', error);
    // Don't exit the process, just log the error
  }
}

startServer(); 