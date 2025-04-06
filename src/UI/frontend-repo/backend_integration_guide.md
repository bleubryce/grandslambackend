# Baseball Analytics System - Backend Integration Guide

## 1. Backend Services Overview

### API Services
The frontend needs to interact with these core services:
- Authentication Service (JWT-based)
- Team Service
- Player Service
- Game Service
- Report Service

### API Endpoints

```typescript
// Authentication Endpoints
POST /api/v1/auth/login
POST /api/v1/auth/logout

// Team Endpoints
GET /api/v1/teams
POST /api/v1/teams
GET /api/v1/teams/:id
PUT /api/v1/teams/:id
DELETE /api/v1/teams/:id
GET /api/v1/teams/:id/roster

// Player Endpoints
GET /api/v1/players
POST /api/v1/players
GET /api/v1/players/:id
PUT /api/v1/players/:id
DELETE /api/v1/players/:id
GET /api/v1/players/:id/stats

// Game Endpoints
GET /api/v1/games
POST /api/v1/games
GET /api/v1/games/:id
PUT /api/v1/games/:id
DELETE /api/v1/games/:id
GET /api/v1/games/:id/stats

// Report Endpoints
GET /api/v1/reports/templates
POST /api/v1/reports/templates
GET /api/v1/reports/generate
GET /api/v1/reports/history
```

## 2. Service Configuration

### Docker Services Configuration
```yaml
# docker-compose.dev.yml
services:
  postgres:
    image: postgres:14-alpine
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
      POSTGRES_DB: baseball_analytics
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:6-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  postgres_data:
  redis_data:
```

## 3. API Service Implementation

### Base API Configuration
```typescript
// src/services/api.ts
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.API_URL || 'http://localhost:3000/api/v1',
  timeout: 5000,
});

// Add JWT token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Authentication Service
export const authService = {
  login: (credentials) => api.post('/auth/login', credentials),
  logout: () => api.post('/auth/logout'),
};

// Team Service
export const teamService = {
  getTeams: () => api.get('/teams'),
  createTeam: (team) => api.post('/teams', team),
  getTeam: (id) => api.get(`/teams/${id}`),
  updateTeam: (id, team) => api.put(`/teams/${id}`, team),
  deleteTeam: (id) => api.delete(`/teams/${id}`),
  getTeamRoster: (id) => api.get(`/teams/${id}/roster`),
};

// Player Service
export const playerService = {
  getPlayers: () => api.get('/players'),
  createPlayer: (player) => api.post('/players', player),
  getPlayer: (id) => api.get(`/players/${id}`),
  updatePlayer: (id, player) => api.put(`/players/${id}`, player),
  deletePlayer: (id) => api.delete(`/players/${id}`),
  getPlayerStats: (id) => api.get(`/players/${id}/stats`),
};

// Game Service
export const gameService = {
  getGames: () => api.get('/games'),
  createGame: (game) => api.post('/games', game),
  getGame: (id) => api.get(`/games/${id}`),
  updateGame: (id, game) => api.put(`/games/${id}`, game),
  deleteGame: (id) => api.delete(`/games/${id}`),
  getGameStats: (id) => api.get(`/games/${id}/stats`),
};

// Report Service
export const reportService = {
  getTemplates: () => api.get('/reports/templates'),
  createTemplate: (template) => api.post('/reports/templates', template),
  generateReport: (params) => api.get('/reports/generate', { params }),
  getHistory: () => api.get('/reports/history'),
};
```

## 4. Authentication Implementation

### Authentication Context
```typescript
// src/contexts/AuthContext.tsx
import React, { createContext, useState, useContext } from 'react';
import { authService } from '../services/api';

interface AuthContextType {
  user: any | null;
  login: (credentials: { username: string; password: string }) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export const AuthProvider: React.FC = ({ children }) => {
  const [user, setUser] = useState<any | null>(null);

  const login = async (credentials: { username: string; password: string }) => {
    try {
      const response = await authService.login(credentials);
      const { token, user } = response.data;
      localStorage.setItem('jwt_token', token);
      setUser(user);
    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    }
  };

  const logout = async () => {
    try {
      await authService.logout();
      localStorage.removeItem('jwt_token');
      setUser(null);
    } catch (error) {
      console.error('Logout failed:', error);
      throw error;
    }
  };

  return (
    <AuthContext.Provider value={{ user, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
```

## 5. Environment Configuration

### Configuration File
```typescript
// src/config/index.ts
export const config = {
  api: {
    baseUrl: process.env.API_URL || 'http://localhost:3000/api/v1',
    timeout: 5000,
  },
  database: {
    host: process.env.DB_HOST || 'localhost',
    port: process.env.DB_PORT || 5432,
    name: process.env.DB_NAME || 'baseball_analytics',
    user: process.env.DB_USER || 'postgres',
    password: process.env.DB_PASSWORD || 'postgres',
  },
  redis: {
    url: process.env.REDIS_URL || 'redis://localhost:6379',
    password: process.env.REDIS_PASSWORD || '',
  },
  auth: {
    tokenKey: 'jwt_token',
    expirationTime: '24h',
  },
};
```

## 6. Database Schema Overview

### Main Tables
- Users (Authentication and user management)
- Teams (Team information and management)
- Players (Player profiles and statistics)
- Games (Game schedules and results)
- Reports (Report templates and generated reports)
- Statistics (Player and team statistics)

## 7. Implementation Steps

1. Set up the development environment:
   ```bash
   # Start Docker services
   docker-compose -f docker-compose.dev.yml up -d
   ```

2. Configure environment variables:
   ```bash
   # Create .env file
   cp .env.example .env
   ```

3. Install dependencies:
   ```bash
   npm install axios @types/axios
   ```

4. Implement API services and authentication

5. Test the integration:
   ```bash
   # Run integration tests
   npm run test:integration
   ```

## 8. Security Considerations

1. JWT Token Management
   - Store tokens securely
   - Implement token refresh mechanism
   - Handle token expiration

2. API Security
   - Use HTTPS
   - Implement rate limiting
   - Validate all inputs
   - Handle CORS properly

3. Data Protection
   - Encrypt sensitive data
   - Implement proper error handling
   - Use prepared statements for database queries

## 9. Error Handling

```typescript
// src/utils/errorHandler.ts
export const handleApiError = (error: any) => {
  if (error.response) {
    // Server responded with error
    switch (error.response.status) {
      case 401:
        // Handle unauthorized
        break;
      case 403:
        // Handle forbidden
        break;
      case 404:
        // Handle not found
        break;
      default:
        // Handle other errors
    }
  } else if (error.request) {
    // Request made but no response
    console.error('No response received:', error.request);
  } else {
    // Error in request setup
    console.error('Request setup error:', error.message);
  }
  throw error;
};
```

## 10. Testing

```typescript
// src/services/__tests__/api.test.ts
import { api, authService } from '../api';

describe('API Services', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('should add auth token to requests', () => {
    const token = 'test-token';
    localStorage.setItem('jwt_token', token);
    
    const config = api.interceptors.request.handlers[0].fulfilled({
      headers: {},
    });
    
    expect(config.headers.Authorization).toBe(`Bearer ${token}`);
  });

  it('should handle login successfully', async () => {
    const mockResponse = { data: { token: 'test-token', user: { id: 1 } } };
    jest.spyOn(api, 'post').mockResolvedValue(mockResponse);
    
    const response = await authService.login({
      username: 'test',
      password: 'password',
    });
    
    expect(response).toEqual(mockResponse);
  });
});
``` 