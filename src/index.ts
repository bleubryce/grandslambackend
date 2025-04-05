import express from 'express';
import cors from 'cors';
import { AnalysisEngine } from './Analysis/Engine/AnalysisEngine';
import { DatabaseManager } from './Database/DatabaseManager';
import { SecurityService } from './Security/service';
import { securityConfig } from './Security/config';
import { config } from './config';

const app = express();
const port = process.env.PORT || 3000;

// Initialize security service
const securityService = new SecurityService();

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
app.use(express.urlencoded({ extended: true }));

// Apply rate limiting to all routes
app.use(securityService.getRateLimiterMiddleware());

// Database connection
const dbManager = new DatabaseManager(config.database);

// Initialize analysis engine after getting a database connection
let analysisEngine: AnalysisEngine;
(async () => {
  const dbClient = await dbManager.getConnection();
  analysisEngine = new AnalysisEngine(dbClient, config.analysis);
})().catch(err => {
  console.error('Failed to initialize analysis engine:', err);
  process.exit(1);
});

// Routes
app.use('/api/auth', require('./Security/routes'));
app.use('/api/analysis', require('./Analysis/routes'));

// Error handling middleware
app.use((err: Error, req: express.Request, res: express.Response, next: express.NextFunction) => {
  console.error(err.stack);
  res.status(500).json({ error: 'Something broke!' });
});

// Start server
app.listen(port, () => {
  console.log(`Server is running on port ${port}`);
});

// Graceful shutdown
process.on('SIGTERM', async () => {
  console.log('SIGTERM signal received: closing HTTP server');
  await dbManager.end();
  await securityService.cleanup();
  process.exit(0);
}); 