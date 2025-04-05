export const config = {
  api: {
    baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:3000/api/v1',
    backendUrl: import.meta.env.VITE_BACKEND_API_URL || 'http://localhost:3000',
    timeout: 5000,
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
};
