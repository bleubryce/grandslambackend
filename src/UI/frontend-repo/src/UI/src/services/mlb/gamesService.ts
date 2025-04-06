
import { Game } from '../api';
import { mlbApi } from './apiClient';
import { convertMlbGame } from './converters';

export const gamesService = {
  // Get games
  getGames: async (date?: string): Promise<Game[]> => {
    try {
      // Format date as YYYY-MM-DD if provided, or use today
      const gameDate = date || new Date().toISOString().split('T')[0];
      
      const response = await mlbApi.get('/schedule', {
        params: { 
          sportId: 1,
          date: gameDate,
          hydrate: 'team,venue'
        }
      });
      
      let games: Game[] = [];
      const dates = response.data.dates || [];
      
      // Collect games from all dates in the response
      dates.forEach((dateData: any) => {
        const dateGames = dateData.games || [];
        games = [...games, ...dateGames.map(convertMlbGame)];
      });
      
      return games;
    } catch (error) {
      console.error('Error fetching MLB games:', error);
      return [];
    }
  },
  
  // Get game IDs
  getGameIds: async (date?: string): Promise<number[]> => {
    try {
      // Use the same date format as getGames
      const gameDate = date || new Date().toISOString().split('T')[0];
      
      const response = await mlbApi.get('/schedule', {
        params: { 
          sportId: 1,
          date: gameDate,
        }
      });
      
      let gameIds: number[] = [];
      const dates = response.data.dates || [];
      
      // Extract just the game IDs from all dates in the response
      dates.forEach((dateData: any) => {
        const dateGames = dateData.games || [];
        const ids = dateGames.map((game: any) => game.gamePk).filter(Boolean);
        gameIds = [...gameIds, ...ids];
      });
      
      return gameIds;
    } catch (error) {
      console.error('Error fetching MLB game IDs:', error);
      return [];
    }
  },
  
  // Get a single game
  getGame: async (id: number): Promise<Game | null> => {
    try {
      const response = await mlbApi.get(`/game/${id}/feed/live`);
      return convertMlbGame(response.data.gameData);
    } catch (error) {
      console.error(`Error fetching MLB game ${id}:`, error);
      return null;
    }
  }
};
