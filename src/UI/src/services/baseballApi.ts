
import axios, { AxiosInstance, AxiosResponse } from 'axios';
import { config } from '../config';

// API Configuration
const API_URL = import.meta.env.VITE_BACKEND_API_URL || 'http://localhost:3001';

// Base API Client
const apiClient: AxiosInstance = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Error Types
export interface ApiError {
  error: string;
  status?: number;
}

// Base Types
interface BaseAnalysisResponse {
  timestamp: string;
}

// Team Types
export interface TeamStats extends BaseAnalysisResponse {
  teamId: number;
  teamName: string;
  wins: number;
  losses: number;
  winningPercentage: number;
  runsScored: number;
  runsAllowed: number;
  results: {
    performanceMetrics: {
      battingAverage: number;
      onBasePercentage: number;
      sluggingPercentage: number;
      era: number;
      whip: number;
    };
    recentTrends: {
      lastTenGames: {
        wins: number;
        losses: number;
      };
      homeVsAway: {
        home: { wins: number; losses: number };
        away: { wins: number; losses: number };
      };
    };
  };
}

// Player Types
export interface PlayerStats extends BaseAnalysisResponse {
  playerId: number;
  playerName: string;
  teamId: number;
  position: string;
  results: {
    batting: {
      battingAverage: number;
      homeRuns: number;
      rbis: number;
      onBasePercentage: number;
      sluggingPercentage: number;
      hits: number;
      atBats: number;
      strikeouts: number;
      walks: number;
    };
    pitching?: {
      era: number;
      wins: number;
      losses: number;
      strikeouts: number;
      walks: number;
      inningsPitched: number;
      whip: number;
    };
    trends: {
      last7Days: {
        battingAverage: number;
        homeRuns: number;
        rbis: number;
      };
      last30Days: {
        battingAverage: number;
        homeRuns: number;
        rbis: number;
      };
    };
  };
}

// Game Types
export interface GameStats extends BaseAnalysisResponse {
  gameId: number;
  homeTeam: {
    teamId: number;
    teamName: string;
    score: number;
  };
  awayTeam: {
    teamId: number;
    teamName: string;
    score: number;
  };
  results: {
    gameMetrics: {
      totalHits: number;
      totalErrors: number;
      totalRuns: number;
      innings: number;
    };
    keyPlays: Array<{
      inning: number;
      description: string;
      impact: number;
    }>;
    playerHighlights: Array<{
      playerId: number;
      playerName: string;
      achievement: string;
      stats: Record<string, number>;
    }>;
  };
}

// Analysis Request Type
export interface AnalysisRequest {
  type: 'team' | 'player' | 'game' | 'ml';
  id: number;
}

// Add JWT token to requests
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// API Class
class BaseballApi {
  private static handleError(error: any): never {
    console.error("Baseball API Error:", error);
    const apiError: ApiError = {
      error: error.response?.data?.error || 'An unknown error occurred',
      status: error.response?.status,
    };
    throw apiError;
  }

  // Team Endpoints
  static async getTeamStats(teamId: number): Promise<TeamStats> {
    try {
      const response: AxiosResponse<TeamStats> = await apiClient.get(`/api/analysis/team/${teamId}`);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  // Player Endpoints
  static async getPlayerStats(playerId: number): Promise<PlayerStats> {
    try {
      const response: AxiosResponse<PlayerStats> = await apiClient.get(`/api/analysis/player/${playerId}`);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  // Game Endpoints
  static async getGameStats(gameId: number): Promise<GameStats> {
    try {
      const response: AxiosResponse<GameStats> = await apiClient.get(`/api/analysis/game/${gameId}`);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  // Custom Analysis Endpoint
  static async analyze(data: AnalysisRequest): Promise<TeamStats | PlayerStats | GameStats> {
    try {
      const response: AxiosResponse = await apiClient.post('/api/analyze', data);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  // Health Check
  static async checkHealth(): Promise<{ status: string; timestamp: string }> {
    try {
      const response: AxiosResponse = await apiClient.get('/api/health');
      return response.data;
    } catch (error) {
      console.error("Health check error:", error);
      throw error; // We want to actually throw this error to handle it in the calling code
    }
  }
}

export default BaseballApi;
