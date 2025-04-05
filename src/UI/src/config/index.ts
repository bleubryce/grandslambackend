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
      teams: import.meta.env.VITE_API_TEAMS_ENDPOINT || '/api/analysis/team',
      players: import.meta.env.VITE_API_PLAYERS_ENDPOINT || '/api/analysis/player',
      games: import.meta.env.VITE_API_GAMES_ENDPOINT || '/api/analysis/game',
      analysis: import.meta.env.VITE_API_ANALYSIS_ENDPOINT || '/analyze',
      model: import.meta.env.VITE_MODEL_ENDPOINT || '/api/analysis/model',
    },
  },
  model: {
    enabled: import.meta.env.VITE_MODEL_ENABLED === 'true',
    version: import.meta.env.VITE_MODEL_VERSION || '1.0',
    healthCheckInterval: 30000, // Check model health every 30 seconds
  },
  features: {
    analytics: import.meta.env.VITE_ENABLE_ANALYTICS === 'true',
    auth: import.meta.env.VITE_ENABLE_AUTH === 'true',
  },
};

export default config;
