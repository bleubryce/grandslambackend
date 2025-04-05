import axios, { AxiosInstance } from 'axios';
import config from '@/config';

export interface Team {
  id: string;
  name: string;
  location: string;
  league: string;
  division: string;
}

class TeamService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: config.api.baseUrl,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  }

  async getTeams(): Promise<Team[]> {
    const response = await this.api.get<Team[]>(config.api.endpoints.teams);
    return response.data;
  }

  async getTeam(id: string): Promise<Team> {
    const response = await this.api.get<Team>(`${config.api.endpoints.teams}/${id}`);
    return response.data;
  }

  async createTeam(team: Omit<Team, 'id'>): Promise<Team> {
    const response = await this.api.post<Team>(config.api.endpoints.teams, team);
    return response.data;
  }

  async updateTeam(id: string, team: Partial<Team>): Promise<Team> {
    const response = await this.api.put<Team>(`${config.api.endpoints.teams}/${id}`, team);
    return response.data;
  }

  async deleteTeam(id: string): Promise<void> {
    await this.api.delete(`${config.api.endpoints.teams}/${id}`);
  }
}

export const teamService = new TeamService();
export default teamService; 