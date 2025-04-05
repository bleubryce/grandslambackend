# Baseball Analytics System API Documentation

## Base URL
```
Development: http://localhost:3001
Production: [Your production URL]
```

## Authentication
The API uses JWT (JSON Web Token) for authentication.

### Authentication Endpoints

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "username": string,
  "password": string
}

Response:
{
  "token": string,
  "user": {
    "id": number,
    "username": string,
    "role": string
  }
}
```

### Authentication Headers
```http
Authorization: Bearer <jwt_token>
```

## API Endpoints

### Health Check
```http
GET /api/health

Response:
{
  "status": "healthy",
  "timestamp": string,
  "modelEnabled": boolean,
  "modelVersion": string
}
```

### Team Analysis
```http
GET /api/analysis/team/:teamId

Response:
{
  "teamId": number,
  "teamName": string,
  "wins": number,
  "losses": number,
  "winningPercentage": number,
  "runsScored": number,
  "runsAllowed": number,
  "results": {
    "performanceMetrics": {
      "battingAverage": number,
      "onBasePercentage": number,
      "sluggingPercentage": number,
      "era": number,
      "whip": number
    },
    "recentTrends": {
      "lastTenGames": {
        "wins": number,
        "losses": number
      },
      "homeVsAway": {
        "home": { "wins": number, "losses": number },
        "away": { "wins": number, "losses": number }
      }
    }
  }
}
```

### Player Analysis
```http
GET /api/analysis/player/:playerId

Response:
{
  "playerId": number,
  "playerName": string,
  "teamId": number,
  "position": string,
  "results": {
    "batting": {
      "battingAverage": number,
      "homeRuns": number,
      "rbis": number,
      "onBasePercentage": number,
      "sluggingPercentage": number,
      "hits": number,
      "atBats": number,
      "strikeouts": number,
      "walks": number
    },
    "pitching": {
      "era": number,
      "wins": number,
      "losses": number,
      "strikeouts": number,
      "walks": number,
      "inningsPitched": number,
      "whip": number
    },
    "trends": {
      "last7Days": {
        "battingAverage": number,
        "homeRuns": number,
        "rbis": number
      },
      "last30Days": {
        "battingAverage": number,
        "homeRuns": number,
        "rbis": number
      }
    }
  }
}
```

### Game Analysis
```http
GET /api/analysis/game/:gameId

Response:
{
  "gameId": number,
  "homeTeam": {
    "teamId": number,
    "teamName": string,
    "score": number
  },
  "awayTeam": {
    "teamId": number,
    "teamName": string,
    "score": number
  },
  "results": {
    "gameMetrics": {
      "totalHits": number,
      "totalErrors": number,
      "totalRuns": number,
      "innings": number
    },
    "keyPlays": [
      {
        "inning": number,
        "description": string,
        "impact": number
      }
    ],
    "playerHighlights": [
      {
        "playerId": number,
        "playerName": string,
        "achievement": string,
        "stats": Record<string, number>
      }
    ]
  }
}
```

### Model Analysis
```http
POST /api/analysis/model
Content-Type: application/json

Request:
{
  "type": "team" | "player" | "game" | "ml",
  "id": number
}

Response:
{
  "modelVersion": string,
  "timestamp": string,
  "results": {
    // Analysis results based on type
  }
}
```

## Database Schema

### Teams Table
```sql
CREATE TABLE teams (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  city VARCHAR(100) NOT NULL,
  division VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Players Table
```sql
CREATE TABLE players (
  id SERIAL PRIMARY KEY,
  team_id INTEGER REFERENCES teams(id),
  name VARCHAR(100) NOT NULL,
  position VARCHAR(50) NOT NULL,
  jersey_number INTEGER,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Games Table
```sql
CREATE TABLE games (
  id SERIAL PRIMARY KEY,
  home_team_id INTEGER REFERENCES teams(id),
  away_team_id INTEGER REFERENCES teams(id),
  start_time TIMESTAMP NOT NULL,
  status VARCHAR(50) NOT NULL,
  home_score INTEGER,
  away_score INTEGER,
  venue VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Stats Table
```sql
CREATE TABLE stats (
  id SERIAL PRIMARY KEY,
  player_id INTEGER REFERENCES players(id),
  game_id INTEGER REFERENCES games(id),
  at_bats INTEGER DEFAULT 0,
  hits INTEGER DEFAULT 0,
  runs INTEGER DEFAULT 0,
  rbis INTEGER DEFAULT 0,
  home_runs INTEGER DEFAULT 0,
  strikeouts INTEGER DEFAULT 0,
  walks INTEGER DEFAULT 0,
  innings_pitched DECIMAL(4,1),
  earned_runs INTEGER,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Error Handling

### Error Response Format
```typescript
{
  error: string;
  status?: number;
  details?: any;
}
```

### Common Error Codes
- 400: Bad Request - Invalid input parameters
- 401: Unauthorized - Missing or invalid authentication
- 403: Forbidden - Valid authentication but insufficient permissions
- 404: Not Found - Resource doesn't exist
- 500: Internal Server Error - Server-side error

## Environment Variables

### Frontend (.env)
```
VITE_BACKEND_API_URL=http://localhost:3001
VITE_APP_TITLE=Grand Slam Analytics
VITE_ENABLE_ANALYTICS=true
VITE_ENABLE_AUTH=true
VITE_MODEL_ENABLED=true
VITE_MODEL_VERSION=1.0
VITE_API_TEAMS_ENDPOINT=/api/analysis/team
VITE_API_PLAYERS_ENDPOINT=/api/analysis/player
VITE_API_GAMES_ENDPOINT=/api/analysis/game
VITE_API_ANALYSIS_ENDPOINT=/analyze
```

### Backend (.env)
```
NODE_ENV=development
PORT=3001
DB_HOST=postgres
DB_PORT=5432
DB_NAME=baseball_analytics
DB_USER=postgres
DB_PASSWORD=postgres
CORS_ORIGIN=http://localhost:3000
MODEL_ENABLED=true
MODEL_VERSION=1.0
JWT_SECRET=your_jwt_secret
```

## Integration Testing

### Test Endpoints
```typescript
// Health check
await BaseballApi.checkHealth();

// Team stats
const teamStats = await BaseballApi.getTeamStats(1);

// Player stats
const playerStats = await BaseballApi.getPlayerStats(1);

// Game stats
const gameStats = await BaseballApi.getGameStats(1);

// Model analysis
const analysis = await BaseballApi.analyze({ 
  type: 'team', 
  id: 1 
});
```

### Error Handling Example
```typescript
try {
  const result = await BaseballApi.getTeamStats(1);
  // Handle success
} catch (error) {
  if (error.status === 401) {
    // Handle unauthorized
  } else if (error.status === 404) {
    // Handle not found
  } else {
    // Handle other errors
  }
}
```

## WebSocket Events (if implemented)
```typescript
// Live game updates
socket.on('gameUpdate', (data: GameUpdate) => {
  // Handle live game updates
});

// Live stats updates
socket.on('statsUpdate', (data: StatsUpdate) => {
  // Handle live stats updates
});
``` 