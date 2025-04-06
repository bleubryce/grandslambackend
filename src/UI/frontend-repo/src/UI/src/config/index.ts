export const config = {
  api: {
    baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:3000/api',
    backendUrl: import.meta.env.VITE_BACKEND_API_URL || 'http://localhost:3000',
    wsEndpoint: import.meta.env.VITE_WS_URL || 'ws://localhost:3000',
    timeout: 5000,
    endpoints: {
      auth: {
        login: '/api/auth/login',
        register: '/api/auth/register',
        validateToken: '/api/auth/validate-token',
        me: '/api/auth/validate-token'
      },
      analysis: {
        base: '/api/analysis',
        train: '/api/analysis/train',
        predict: '/api/analysis/predict',
        metrics: '/api/analysis/metrics',
        parameters: '/api/analysis/parameters',
        versions: '/api/analysis/versions',
        export: '/api/analysis/export',
        visualization: '/api/analysis/visualization'
      },
      teams: '/api/analysis/team',
      players: '/api/analysis/player',
      games: '/api/analysis/game'
    }
  },
  app: {
    title: import.meta.env.VITE_APP_TITLE || 'Baseball Analytics',
    version: '1.0.0',
    environment: import.meta.env.MODE || 'development',
  },
  model: {
    enabled: import.meta.env.VITE_MODEL_ENABLED === 'true',
    version: import.meta.env.VITE_MODEL_VERSION || '1.0',
    defaultParameters: {
      learningRate: 0.001,
      epochs: 100,
      batchSize: 32,
      layers: [64, 32, 16],
      activationFunction: 'relu'
    },
    supportedFormats: ['csv', 'json', 'xlsx'],
    maxBatchSize: 1000,
    updateInterval: 1000, // ms
    autoSave: true,
    visualization: {
      enabled: true,
      refreshRate: 5000, // ms
      maxDataPoints: 1000
    }
  },
  database: {
    host: import.meta.env.VITE_DB_HOST || 'localhost',
    port: import.meta.env.VITE_DB_PORT || 5432,
    name: import.meta.env.VITE_DB_NAME || 'baseball_analytics',
    user: import.meta.env.VITE_DB_USER || 'postgres',
    password: import.meta.env.VITE_DB_PASSWORD || 'postgres',
  },
  redis: {
    url: import.meta.env.VITE_REDIS_URL || 'redis://localhost:6379',
    password: import.meta.env.VITE_REDIS_PASSWORD || '',
  },
  auth: {
    tokenKey: 'jwt_token',
    expirationTime: '24h',
  },
  features: {
    enableRealTimeUpdates: true,
    enableExportFeature: true,
    enableAdvancedCharts: true,
    enableModelTraining: true,
    enableBatchPredictions: true,
    enableVersionControl: true
  }
};
