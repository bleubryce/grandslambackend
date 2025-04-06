
import { AxiosResponse } from 'axios';
import { apiClient } from './apiClient';
import { config } from '@/config';
import { ApiResponse, Player } from './types';

// Mock player service for development
class PlayerService {
  private useMockResponse: boolean;

  constructor() {
    // Use mock responses in development
    this.useMockResponse = config.app.environment === 'development';
  }

  async getPlayers(): Promise<AxiosResponse<ApiResponse<Player[]>>> {
    if (this.useMockResponse) {
      console.log('Using mock players response in development');
      const mockPlayers = [
        { id: 1, firstName: 'Mike', lastName: 'Trout', position: 'CF', teamId: 1, number: 27, battingAverage: '.312', homeRuns: 42, rbi: 99 },
        { id: 2, firstName: 'Aaron', lastName: 'Judge', position: 'RF', teamId: 2, number: 99, battingAverage: '.287', homeRuns: 51, rbi: 108 },
        { id: 3, firstName: 'Shohei', lastName: 'Ohtani', position: 'DH', teamId: 3, number: 17, battingAverage: '.304', homeRuns: 44, rbi: 95 },
        { id: 4, firstName: 'Jacob', lastName: 'deGrom', position: 'P', teamId: 4, number: 48, era: '2.38', wins: 14, losses: 5, strikeouts: 255 },
        { id: 5, firstName: 'Freddie', lastName: 'Freeman', position: '1B', teamId: 5, number: 5, battingAverage: '.325', homeRuns: 28, rbi: 102 },
        { id: 6, firstName: 'Mookie', lastName: 'Betts', position: 'RF', teamId: 5, number: 50, battingAverage: '.300', homeRuns: 35, rbi: 85 },
        { id: 7, firstName: 'Gerrit', lastName: 'Cole', position: 'SP', teamId: 2, number: 45, era: '3.22', wins: 15, losses: 7, strikeouts: 238 },
        { id: 8, firstName: 'Juan', lastName: 'Soto', position: 'LF', teamId: 6, number: 22, battingAverage: '.275', homeRuns: 32, rbi: 89 },
        { id: 9, firstName: 'Max', lastName: 'Scherzer', position: 'SP', teamId: 7, number: 31, era: '2.96', wins: 12, losses: 6, strikeouts: 210 }
      ];
      
      const mockResponse: ApiResponse<Player[]> = {
        status: 'success',
        message: 'Players retrieved successfully',
        data: mockPlayers,
        timestamp: new Date().toISOString()
      };
      
      return Promise.resolve({
        data: mockResponse,
        status: 200,
        statusText: 'OK',
        headers: {},
        config: {} as any
      });
    }
    
    return apiClient.get<ApiResponse<Player[]>>('/api/players');
  }

  async getPlayer(id: number): Promise<AxiosResponse<ApiResponse<Player>>> {
    return apiClient.get<ApiResponse<Player>>(`/api/players/${id}`);
  }

  async createPlayer(player: Omit<Player, 'id'>): Promise<AxiosResponse<ApiResponse<Player>>> {
    return apiClient.post<ApiResponse<Player>>('/api/players', player);
  }

  async updatePlayer(id: number, player: Partial<Player>): Promise<AxiosResponse<ApiResponse<Player>>> {
    return apiClient.put<ApiResponse<Player>>(`/api/players/${id}`, player);
  }

  async deletePlayer(id: number): Promise<AxiosResponse<ApiResponse<null>>> {
    return apiClient.delete<ApiResponse<null>>(`/api/players/${id}`);
  }

  async getPlayerStats(id: number): Promise<AxiosResponse<ApiResponse<any>>> {
    return apiClient.get<ApiResponse<any>>(`/api/players/${id}/stats`);
  }
}

export const playerService = new PlayerService();
