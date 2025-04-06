
import { Player } from '../api';
import { mlbApi } from './apiClient';
import { convertMlbPlayer } from './converters';
import { statsService } from './statsService';

// Helper function to get a player with their stats
export const getPlayerWithStats = async (playerId: number): Promise<Player | null> => {
  try {
    const player = await playersService.getPlayer(playerId);
    if (!player) return null;
    
    // Determine if player is a pitcher
    const isPitcher = ['P', 'SP', 'RP', 'CL'].includes(player.position);
    
    if (isPitcher) {
      const pitcherStats = await statsService.getPitcherStats(playerId);
      if (pitcherStats) {
        player.era = pitcherStats.era;
        player.wins = pitcherStats.wins;
        player.losses = pitcherStats.losses;
        player.inningsPitched = pitcherStats.inningsPitched;
        player.strikeouts = pitcherStats.strikeouts;
      }
    } else {
      const playerStats = await statsService.getPlayerStats(playerId);
      if (playerStats) {
        player.battingAverage = playerStats.battingAverage;
        player.homeRuns = playerStats.homeRuns;
        player.rbi = playerStats.rbi;
        player.strikeouts = playerStats.strikeouts;
      }
    }
    
    return player;
  } catch (error) {
    console.error(`Error fetching player with stats for ${playerId}:`, error);
    return null;
  }
};

export const playersService = {
  // Get players 
  getPlayers: async (teamId?: number, includeStats: boolean = false): Promise<Player[]> => {
    try {
      // If teamId is provided, get players from that team
      if (teamId) {
        const response = await mlbApi.get(`/teams/${teamId}/roster`, {
          params: { rosterType: 'active' }
        });
        
        // Fetch detailed player info for each roster member
        const playerPromises = response.data.roster.map(async (item: any) => {
          const playerResponse = await mlbApi.get(`/people/${item.person.id}`);
          return convertMlbPlayer(playerResponse.data.people[0]);
        });
        
        return await Promise.all(playerPromises);
      } else {
        // Without teamId, get a sampling of players
        // In a real app, you might want a different approach for getting all players
        const { teamsService } = await import('./teamsService');
        const teams = await teamsService.getTeams();
        const sampleTeamIds = teams.slice(0, 3).map(team => team.id);
        
        let allPlayers: Player[] = [];
        for (const tId of sampleTeamIds) {
          const teamPlayers = await playersService.getPlayers(tId);
          allPlayers = [...allPlayers, ...teamPlayers];
        }
        
        // If includeStats is true, fetch stats for each player
        if (includeStats) {
          // This would be more efficient in a real app with batched requests
          // For now, we'll do individual requests as a simple implementation
          const playersWithStats = await Promise.all(
            allPlayers.map(async (player) => {
              return await getPlayerWithStats(player.id) || player;
            })
          );
          return playersWithStats;
        }
        
        return allPlayers;
      }
    } catch (error) {
      console.error('Error fetching MLB players:', error);
      return [];
    }
  },
  
  // Get player by ID
  getPlayer: async (id: number): Promise<Player | null> => {
    try {
      const response = await mlbApi.get(`/people/${id}`);
      return convertMlbPlayer(response.data.people[0]);
    } catch (error) {
      console.error(`Error fetching MLB player ${id}:`, error);
      return null;
    }
  }
};
