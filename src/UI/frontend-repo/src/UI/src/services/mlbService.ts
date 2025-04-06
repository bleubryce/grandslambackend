
import axios from 'axios';
import { config } from '../config';
import { toast } from '@/components/ui/use-toast';
import { saveAs } from 'file-saver';
import * as XLSX from 'xlsx';

// API client setup
const apiClient = axios.create({
  baseURL: config.api.backendUrl,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: config.api.timeout,
});

// Add JWT token to requests
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Error handling interceptor
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const errorMsg = error.response?.data?.error || 'An unknown error occurred';
    toast({
      variant: "destructive",
      title: "API Error",
      description: errorMsg,
    });
    return Promise.reject(error);
  }
);

// Types
export interface TeamAnalysisResponse {
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
      lastTenGames: { wins: number; losses: number };
      homeVsAway: {
        home: { wins: number; losses: number };
        away: { wins: number; losses: number };
      };
    };
  };
}

export interface PlayerAnalysisResponse {
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

export interface GameAnalysisResponse {
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

export interface ModelAnalysisRequest {
  type: 'team' | 'player' | 'game' | 'ml';
  id: number;
}

export interface ModelAnalysisResponse {
  modelVersion: string;
  timestamp: string;
  results: any; // Depends on analysis type
}

// MLB Service implementation
export const mlbService = {
  // Team Analysis
  getTeamAnalysis: async (teamId: number): Promise<TeamAnalysisResponse> => {
    const response = await apiClient.get(`/api/analysis/team/${teamId}`);
    return response.data;
  },
  
  // Player Analysis
  getPlayerAnalysis: async (playerId: number): Promise<PlayerAnalysisResponse> => {
    const response = await apiClient.get(`/api/analysis/player/${playerId}`);
    return response.data;
  },
  
  // Game Analysis
  getGameAnalysis: async (gameId: number): Promise<GameAnalysisResponse> => {
    const response = await apiClient.get(`/api/analysis/game/${gameId}`);
    return response.data;
  },
  
  // Model Analysis
  runModelAnalysis: async (request: ModelAnalysisRequest): Promise<ModelAnalysisResponse> => {
    const response = await apiClient.post('/api/analysis/model', request);
    return response.data;
  },
  
  // Export functions
  exportToExcel: (data: any, fileName: string = 'analysis-export'): void => {
    const worksheet = XLSX.utils.json_to_sheet(data);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Analysis');
    const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
    const fileData = new Blob([excelBuffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8' });
    saveAs(fileData, `${fileName}-${new Date().toISOString().split('T')[0]}.xlsx`);
    
    toast({
      title: "Export Successful",
      description: `Data exported to ${fileName}.xlsx`,
    });
  },
};

export default mlbService;
