
import { PlayerStats, PitcherStats } from '../api';
import { mlbApi } from './apiClient';
import { convertPlayerStats, convertPitcherStats } from './converters';

export const statsService = {
  // Get player batting stats
  getPlayerStats: async (id: number): Promise<PlayerStats | null> => {
    try {
      const response = await mlbApi.get(`/people/${id}/stats`, {
        params: {
          stats: 'season',
          group: 'hitting',
          season: new Date().getFullYear()
        }
      });
      
      if (response.data.stats.length === 0 || !response.data.stats[0].splits.length) {
        return null;
      }
      
      return convertPlayerStats({
        id,
        stats: response.data.stats
      });
    } catch (error) {
      console.error(`Error fetching MLB player stats for ${id}:`, error);
      return null;
    }
  },
  
  // Get pitcher stats
  getPitcherStats: async (id: number): Promise<PitcherStats | null> => {
    try {
      const response = await mlbApi.get(`/people/${id}/stats`, {
        params: {
          stats: 'season',
          group: 'pitching',
          season: new Date().getFullYear()
        }
      });
      
      if (response.data.stats.length === 0 || !response.data.stats[0].splits.length) {
        return null;
      }
      
      return convertPitcherStats({
        id,
        stats: response.data.stats
      });
    } catch (error) {
      console.error(`Error fetching MLB pitcher stats for ${id}:`, error);
      return null;
    }
  },
  
  // Get game stats
  getGameStats: async (id: number): Promise<any> => {
    try {
      const response = await mlbApi.get(`/game/${id}/boxscore`);
      return response.data;
    } catch (error) {
      console.error(`Error fetching MLB game stats for ${id}:`, error);
      return null;
    }
  }
};
