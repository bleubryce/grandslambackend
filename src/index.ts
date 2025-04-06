import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { config } from './config';
import { logger } from './Logging/Logger';
import { setupRoutes } from './routes';
import { setupDatabase } from './Database/setup';
import { setupSecurity } from './Security/setup';

const app = express();

// Security middleware
app.use(helmet());
app.use(cors(config.security.cors));
app.use(express.json());

// Setup security features
setupSecurity(app);

// Setup routes
setupRoutes(app);

// Start server
const PORT = config.port || 3001;

async function startServer() {
  try {
    // Initialize database
    await setupDatabase();
    logger.info('Database connection established');

    app.listen(PORT, () => {
      logger.info(`Server running on port ${PORT}`);
    });
  } catch (error) {
    logger.error('Failed to start server:', error);
    process.exit(1);
  }
}

startServer(); 