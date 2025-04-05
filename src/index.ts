import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { config } from './config';
import { logger } from './utils/logger';
import { DatabaseManager } from './Database/DatabaseManager';
import authRoutes from './Security/routes';
import analysisRoutes from './Analysis/routes';

const app = express();

// Initialize database
const dbManager = new DatabaseManager(config.database);

// Middleware
app.use(helmet());
app.use(cors(config.security.cors));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/analysis', analysisRoutes);

// Health check endpoint
app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Error handling middleware
app.use((err: Error, req: express.Request, res: express.Response, next: express.NextFunction) => {
    logger.error('Unhandled error:', err);
    res.status(500).json({ error: 'Internal server error' });
});

// Initialize database tables
dbManager.createTables()
    .then(() => {
        const port = config.port;
        app.listen(port, () => {
            logger.info(`Server running on port ${port}`);
        });
    })
    .catch((error) => {
        logger.error('Failed to initialize database:', error);
        process.exit(1);
    }); 