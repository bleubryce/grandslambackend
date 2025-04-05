import axios, { AxiosInstance } from 'axios';
import config from '@/config';

export interface Game {
  id: number;
  homeTeamId: number;
  awayTeamId: number;
  homeScore: number;
  awayScore: number;
  status: 'scheduled' | 'in_progress' | 'completed' | 'postponed' | 'cancelled';
  location: string;
  date: string;
  startTime: string;
}

class GameService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: config.api.baseUrl,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  }

  async getGames(): Promise<Game[]> {
    const response = await this.api.get<Game[]>(config.api.endpoints.games);
    return response.data;
  }

  async getGame(id: number): Promise<Game> {
    const response = await this.api.get<Game>(`${config.api.endpoints.games}/${id}`);
    return response.data;
  }

  async createGame(game: Omit<Game, 'id'>): Promise<Game> {
    const response = await this.api.post<Game>(config.api.endpoints.games, game);
    return response.data;
  }

  async updateGame(id: number, game: Partial<Game>): Promise<Game> {
    const response = await this.api.put<Game>(`${config.api.endpoints.games}/${id}`, game);
    return response.data;
  }

  async deleteGame(id: number): Promise<void> {
    await this.api.delete(`${config.api.endpoints.games}/${id}`);
  }

  async getGameStats(id: number): Promise<any> {
    const response = await this.api.get(`${config.api.endpoints.games}/${id}/stats`);
    return response.data;
  }
}

export const gameService = new GameService();
export default gameService; 