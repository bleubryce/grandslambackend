
import { Team } from '../api';
import { mlbApi } from './apiClient';
import { convertMlbTeam } from './converters';

export const teamsService = {
  // Get all teams
  getTeams: async (): Promise<Team[]> => {
    try {
      const response = await mlbApi.get('/teams', {
        params: { sportId: 1, season: new Date().getFullYear() }
      });
      return response.data.teams.map(convertMlbTeam);
    } catch (error) {
      console.error('Error fetching MLB teams:', error);
      return [];
    }
  },
  
  // Get team by ID
  getTeam: async (id: number): Promise<Team | null> => {
    try {
      const response = await mlbApi.get(`/teams/${id}`);
      return convertMlbTeam(response.data.teams[0]);
    } catch (error) {
      console.error(`Error fetching MLB team ${id}:`, error);
      return null;
    }
  }
};
