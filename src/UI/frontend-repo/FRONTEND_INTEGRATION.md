# Frontend Integration Guide

## API Configuration

### Base URL
```
Development: http://localhost:3000
```

### Environment Variables
Add these to your `.env` file:
```
VITE_BACKEND_API_URL=http://localhost:3000
VITE_APP_TITLE=Grand Slam Analytics
VITE_MODEL_ENABLED=true
VITE_MODEL_VERSION=1.0
VITE_ENABLE_ANALYTICS=true
VITE_ENABLE_AUTH=true
VITE_API_TEAMS_ENDPOINT=/api/analysis/team
VITE_API_PLAYERS_ENDPOINT=/api/analysis/player
VITE_API_GAMES_ENDPOINT=/api/analysis/game
VITE_API_ANALYSIS_ENDPOINT=/analyze
```

## API Endpoints

### Authentication
```typescript
POST /api/auth/login
Headers: { "Content-Type": "application/json" }
Body: { username: string, password: string }
Response: {
  token: string,
  user: {
    id: number,
    username: string,
    role: string
  }
}
```

### Team Analysis
```typescript
GET /api/analysis/team/:teamId
Headers: { "Authorization": "Bearer <token>" }
Response: {
  teamId: number,
  teamName: string,
  wins: number,
  losses: number,
  winningPercentage: number,
  runsScored: number,
  runsAllowed: number,
  results: {
    performanceMetrics: {
      battingAverage: number,
      onBasePercentage: number,
      sluggingPercentage: number,
      era: number,
      whip: number
    },
    recentTrends: {
      lastTenGames: { wins: number, losses: number },
      homeVsAway: {
        home: { wins: number, losses: number },
        away: { wins: number, losses: number }
      }
    }
  }
}
```

### Player Analysis
```typescript
GET /api/analysis/player/:playerId
Headers: { "Authorization": "Bearer <token>" }
Response: {
  playerId: number,
  playerName: string,
  teamId: number,
  position: string,
  results: {
    batting: {
      battingAverage: number,
      homeRuns: number,
      rbis: number,
      onBasePercentage: number,
      sluggingPercentage: number,
      hits: number,
      atBats: number,
      strikeouts: number,
      walks: number
    },
    pitching: {
      era: number,
      wins: number,
      losses: number,
      strikeouts: number,
      walks: number,
      inningsPitched: number,
      whip: number
    },
    trends: {
      last7Days: {
        battingAverage: number,
        homeRuns: number,
        rbis: number
      },
      last30Days: {
        battingAverage: number,
        homeRuns: number,
        rbis: number
      }
    }
  }
}
```

### Game Analysis
```typescript
GET /api/analysis/game/:gameId
Headers: { "Authorization": "Bearer <token>" }
Response: {
  gameId: number,
  homeTeam: {
    teamId: number,
    teamName: string,
    score: number
  },
  awayTeam: {
    teamId: number,
    teamName: string,
    score: number
  },
  results: {
    gameMetrics: {
      totalHits: number,
      totalErrors: number,
      totalRuns: number,
      innings: number
    },
    keyPlays: [
      {
        inning: number,
        description: string,
        impact: number
      }
    ],
    playerHighlights: [
      {
        playerId: number,
        playerName: string,
        achievement: string,
        stats: Record<string, number>
      }
    ]
  }
}
```

### Model Analysis
```typescript
POST /api/analysis/model
Headers: {
  "Authorization": "Bearer <token>",
  "Content-Type": "application/json"
}
Body: {
  type: "team" | "player" | "game" | "ml",
  id: number
}
Response: {
  modelVersion: string,
  timestamp: string,
  results: any // Depends on analysis type
}
```

## Error Handling

### Error Response Format
```typescript
{
  error: string,
  status: number,
  details?: any
}
```

### Common Error Codes
- 400: Bad Request - Invalid input
- 401: Unauthorized - Missing/invalid token
- 403: Forbidden - Valid token but insufficient permissions
- 404: Not Found - Resource doesn't exist
- 500: Internal Server Error

## Real-time Updates (WebSocket)

### Connection
```typescript
// Connect to WebSocket
const socket = io("http://localhost:3000", {
  auth: { token: "your-jwt-token" }
});

// Listen for game updates
socket.on("gameUpdate", (data: {
  gameId: number,
  timestamp: string,
  type: "score" | "status" | "play",
  data: any
}) => {
  // Handle game update
});

// Listen for player stats updates
socket.on("statsUpdate", (data: {
  playerId: number,
  gameId: number,
  timestamp: string,
  stats: {
    atBats?: number,
    hits?: number,
    runs?: number,
    rbis?: number,
    homeRuns?: number,
    strikeouts?: number,
    walks?: number,
    inningsPitched?: number,
    earnedRuns?: number
  }
}) => {
  // Handle stats update
});
```

## Authentication Flow

1. User logs in with username/password
2. Backend returns JWT token
3. Store token securely (e.g., in memory or secure cookie)
4. Include token in all subsequent API requests
5. Handle token expiration/refresh

## Required Dependencies
```json
{
  "dependencies": {
    "@tanstack/react-query": "^5.28.4",
    "axios": "^1.6.7",
    "file-saver": "^2.0.5",
    "socket.io-client": "^4.7.5",
    "xlsx": "^0.18.5"
  },
  "devDependencies": {
    "@testing-library/jest-dom": "^5.16.5",
    "@testing-library/react": "^13.4.0",
    "@testing-library/user-event": "^14.4.3",
    "@types/file-saver": "^2.0.5",
    "@types/jest": "^29.2.4",
    "@types/xlsx": "^0.0.36",
    "axios-mock-adapter": "^1.21.2"
  }
}
```

## Example Usage

### Making API Calls
```typescript
// Setup axios instance
const api = axios.create({
  baseURL: "http://localhost:3000",
  headers: {
    "Content-Type": "application/json"
  }
});

// Add auth token interceptor
api.interceptors.request.use(config => {
  const token = getStoredToken(); // Your token storage method
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Example API call
try {
  const { data } = await api.get(`/api/analysis/team/${teamId}`);
  // Handle success
} catch (error) {
  if (error.response?.status === 401) {
    // Handle unauthorized
  }
  // Handle other errors
}
```

### WebSocket Subscription
```typescript
// Subscribe to game updates
socket.emit("subscribeToGame", { gameId: 123 });

// Subscribe to player updates
socket.emit("subscribeToPlayer", { playerId: 456 });

// Unsubscribe when done
socket.emit("unsubscribeFromGame", { gameId: 123 });
socket.emit("unsubscribeFromPlayer", { playerId: 456 });
``` 