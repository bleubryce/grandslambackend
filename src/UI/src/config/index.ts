interface Config {
  api: {
    baseUrl: string;
    endpoints: {
      teams: string;
      players: string;
      games: string;
      analysis: string;
      model: string;
    };
  };
  model: {
    enabled: boolean;
    version: string;
    healthCheckInterval: number;
    supportedTypes: string[];
  };
  features: {
    analytics: boolean;
    auth: boolean;
  };
}

const config: Config = {
  api: {
    baseUrl: import.meta.env.VITE_BACKEND_API_URL || 'http://localhost:3001',
    endpoints: {
      teams: '/api/analysis/team',
      players: '/api/analysis/player',
      games: '/api/analysis/game',
      analysis: '/api/analysis',
      model: '/api/model',
    },
  },
  model: {
    enabled: import.meta.env.VITE_MODEL_ENABLED === 'true',
    version: import.meta.env.VITE_MODEL_VERSION || '1.0',
    healthCheckInterval: 30000, // Check model health every 30 seconds
    supportedTypes: ['team', 'player', 'game'],
  },
  features: {
    analytics: import.meta.env.VITE_ENABLE_ANALYTICS === 'true',
    auth: import.meta.env.VITE_ENABLE_AUTH === 'true',
  },
};

export default config;
