import axios, { AxiosInstance, AxiosResponse } from 'axios';
import {
  ApiConfig,
  AuthResponse,
  HealthCheckResponse,
  TeamAnalysisResponse,
  PlayerAnalysisResponse,
  GameAnalysisResponse,
  ModelAnalysisRequest,
  ModelAnalysisResponse,
  ErrorResponse
} from '../types/api';
import { playerService, type Player } from './player.service';
import { gameService, type Game } from './game.service';

class BaseballApi {
  private api: AxiosInstance;
  private static instance: BaseballApi;

  private constructor(config: ApiConfig) {
    this.api = axios.create({
      baseURL: config.baseUrl,
      timeout: config.timeout || 10000,
      headers: {
        'Content-Type': 'application/json',
        ...config.headers
      }
    });

    // Add response interceptor for error handling
    this.api.interceptors.response.use(
      (response) => response,
      (error) => {
        const errorResponse: ErrorResponse = {
          error: error.message,
          status: error.response?.status,
          details: error.response?.data
        };
        return Promise.reject(errorResponse);
      }
    );
  }

  public static getInstance(config?: ApiConfig): BaseballApi {
    if (!BaseballApi.instance) {
      if (!config) {
        throw new Error('Configuration required for initial setup');
      }
      BaseballApi.instance = new BaseballApi(config);
    }
    return BaseballApi.instance;
  }

  // Auth methods
  public async login(username: string, password: string): Promise<AuthResponse> {
    const response = await this.api.post<AuthResponse>('/api/auth/login', {
      username,
      password
    });
    this.setAuthToken(response.data.token);
    return response.data;
  }

  public setAuthToken(token: string): void {
    this.api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
  }

  public clearAuthToken(): void {
    delete this.api.defaults.headers.common['Authorization'];
  }

  // Health check
  public async checkHealth(): Promise<HealthCheckResponse> {
    const response = await this.api.get<HealthCheckResponse>('/api/health');
    return response.data;
  }

  // Team analysis
  public async getTeamAnalysis(teamId: number): Promise<TeamAnalysisResponse> {
    const response = await this.api.get<TeamAnalysisResponse>(`/api/analysis/team/${teamId}`);
    return response.data;
  }

  // Player analysis
  public async getPlayerAnalysis(playerId: number): Promise<PlayerAnalysisResponse> {
    const response = await this.api.get<PlayerAnalysisResponse>(`/api/analysis/player/${playerId}`);
    return response.data;
  }

  // Game analysis
  public async getGameAnalysis(gameId: number): Promise<GameAnalysisResponse> {
    const response = await this.api.get<GameAnalysisResponse>(`/api/analysis/game/${gameId}`);
    return response.data;
  }

  // Model analysis
  public async analyze(request: ModelAnalysisRequest): Promise<ModelAnalysisResponse> {
    const response = await this.api.post<ModelAnalysisResponse>('/api/analysis/model', request);
    return response.data;
  }

  // Error handling helper
  private handleError(error: any): never {
    const errorResponse: ErrorResponse = {
      error: error.message || 'An unknown error occurred',
      status: error.response?.status,
      details: error.response?.data
    };
    throw errorResponse;
  }
}

// Create and export the API instance
const apiConfig: ApiConfig = {
  baseUrl: import.meta.env.VITE_BACKEND_API_URL || 'http://localhost:3000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
};

export { playerService, gameService };
export type { Player, Game };
export const baseballApi = BaseballApi.getInstance(apiConfig);
export default baseballApi;
