import axios, { AxiosInstance } from 'axios';
import config from '@/config';

export interface Player {
  id: number;
  name: string;
  position: string;
  teamId: number;
  jerseyNumber?: number;
  battingAverage?: number;
  homeRuns?: number;
  rbi?: number;
  era?: number;
  wins?: number;
  losses?: number;
  inningsPitched?: number;
  strikeouts?: number;
}

class PlayerService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: config.api.baseUrl,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  }

  async getPlayers(): Promise<Player[]> {
    const response = await this.api.get<Player[]>(config.api.endpoints.players);
    return response.data;
  }

  async getPlayer(id: number): Promise<Player> {
    const response = await this.api.get<Player>(`${config.api.endpoints.players}/${id}`);
    return response.data;
  }

  async createPlayer(player: Omit<Player, 'id'>): Promise<Player> {
    const response = await this.api.post<Player>(config.api.endpoints.players, player);
    return response.data;
  }

  async updatePlayer(id: number, player: Partial<Player>): Promise<Player> {
    const response = await this.api.put<Player>(`${config.api.endpoints.players}/${id}`, player);
    return response.data;
  }

  async deletePlayer(id: number): Promise<void> {
    await this.api.delete(`${config.api.endpoints.players}/${id}`);
  }

  async getPlayerStats(id: number): Promise<any> {
    const response = await this.api.get(`${config.api.endpoints.players}/${id}/stats`);
    return response.data;
  }
}

export const playerService = new PlayerService();
export default playerService; 