export interface AuthResponse {
  token: string;
  user: {
    id: number;
    username: string;
    role: string;
  };
}

export interface HealthCheckResponse {
  status: string;
  timestamp: string;
  modelEnabled: boolean;
  modelVersion: string;
}

export interface TeamPerformanceMetrics {
  battingAverage: number;
  onBasePercentage: number;
  sluggingPercentage: number;
  era: number;
  whip: number;
}

export interface TeamGameRecord {
  wins: number;
  losses: number;
}

export interface TeamAnalysisResponse {
  teamId: number;
  teamName: string;
  wins: number;
  losses: number;
  winningPercentage: number;
  runsScored: number;
  runsAllowed: number;
  results: {
    performanceMetrics: TeamPerformanceMetrics;
    recentTrends: {
      lastTenGames: TeamGameRecord;
      homeVsAway: {
        home: TeamGameRecord;
        away: TeamGameRecord;
      };
    };
  };
}

export interface BattingStats {
  battingAverage: number;
  homeRuns: number;
  rbis: number;
  onBasePercentage: number;
  sluggingPercentage: number;
  hits: number;
  atBats: number;
  strikeouts: number;
  walks: number;
}

export interface PitchingStats {
  era: number;
  wins: number;
  losses: number;
  strikeouts: number;
  walks: number;
  inningsPitched: number;
  whip: number;
}

export interface PlayerTrends {
  battingAverage: number;
  homeRuns: number;
  rbis: number;
}

export interface PlayerAnalysisResponse {
  playerId: number;
  playerName: string;
  teamId: number;
  position: string;
  results: {
    batting: BattingStats;
    pitching: PitchingStats;
    trends: {
      last7Days: PlayerTrends;
      last30Days: PlayerTrends;
    };
  };
}

export interface TeamGameInfo {
  teamId: number;
  teamName: string;
  score: number;
}

export interface KeyPlay {
  inning: number;
  description: string;
  impact: number;
}

export interface PlayerHighlight {
  playerId: number;
  playerName: string;
  achievement: string;
  stats: Record<string, number>;
}

export interface GameAnalysisResponse {
  gameId: number;
  homeTeam: TeamGameInfo;
  awayTeam: TeamGameInfo;
  results: {
    gameMetrics: {
      totalHits: number;
      totalErrors: number;
      totalRuns: number;
      innings: number;
    };
    keyPlays: KeyPlay[];
    playerHighlights: PlayerHighlight[];
  };
}

export type AnalysisType = 'team' | 'player' | 'game' | 'ml';

export interface ModelAnalysisRequest {
  type: AnalysisType;
  id: number;
}

export interface ModelAnalysisResponse {
  modelVersion: string;
  timestamp: string;
  results: any; // Type depends on analysis type
}

export interface ErrorResponse {
  error: string;
  status?: number;
  details?: any;
}

// WebSocket Event Types
export interface GameUpdate {
  gameId: number;
  timestamp: string;
  type: 'score' | 'status' | 'play';
  data: any;
}

export interface StatsUpdate {
  playerId: number;
  gameId: number;
  timestamp: string;
  stats: Partial<BattingStats & PitchingStats>;
}

// API Client Configuration
export interface ApiConfig {
  baseUrl: string;
  timeout?: number;
  headers?: Record<string, string>;
}

// Database Types
export interface Team {
  id: number;
  name: string;
  city: string;
  division: string;
  created_at: string;
}

export interface Player {
  id: number;
  team_id: number;
  name: string;
  position: string;
  jersey_number: number | null;
  created_at: string;
}

export interface Game {
  id: number;
  home_team_id: number;
  away_team_id: number;
  start_time: string;
  status: string;
  home_score: number | null;
  away_score: number | null;
  venue: string;
  created_at: string;
}

export interface Stats {
  id: number;
  player_id: number;
  game_id: number;
  at_bats: number;
  hits: number;
  runs: number;
  rbis: number;
  home_runs: number;
  strikeouts: number;
  walks: number;
  innings_pitched: number | null;
  earned_runs: number | null;
  created_at: string;
} 