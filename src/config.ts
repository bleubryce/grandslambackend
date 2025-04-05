export interface DatabaseConfig {
  host: string;
  port: number;
  database: string;
  user: string;
  password: string;
}

export interface AnalysisConfig {
  batchSize: number;
  cacheEnabled: boolean;
  cacheDuration: number;
  mlModelPath: string;
  logLevel: string;
}

export interface Config {
  database: DatabaseConfig;
  analysis: AnalysisConfig;
}

export const config: Config = {
  database: {
    host: process.env.DB_HOST || 'postgres',
    port: parseInt(process.env.DB_PORT || '5432'),
    database: process.env.DB_NAME || 'baseball_analytics',
    user: process.env.DB_USER || 'postgres',
    password: process.env.DB_PASSWORD || 'postgres'
  },
  analysis: {
    batchSize: parseInt(process.env.ANALYSIS_BATCH_SIZE || '100'),
    cacheEnabled: process.env.ANALYSIS_CACHE_ENABLED === 'true',
    cacheDuration: parseInt(process.env.ANALYSIS_CACHE_DURATION || '3600'),
    mlModelPath: process.env.ML_MODEL_PATH || './models',
    logLevel: process.env.LOG_LEVEL || 'info'
  }
}; 