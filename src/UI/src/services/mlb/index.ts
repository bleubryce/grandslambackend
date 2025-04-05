
import { getPlayerWithStats } from './playersService';
import { teamsService } from './teamsService';
import { playersService } from './playersService';
import { gamesService } from './gamesService';
import { statsService } from './statsService';

// Export all services as a single mlbService object for backward compatibility
export const mlbService = {
  // Teams
  getTeams: teamsService.getTeams,
  getTeam: teamsService.getTeam,
  
  // Players
  getPlayers: playersService.getPlayers,
  getPlayer: playersService.getPlayer,
  
  // Games
  getGames: gamesService.getGames,
  getGameIds: gamesService.getGameIds,
  getGame: gamesService.getGame,
  
  // Stats
  getPlayerStats: statsService.getPlayerStats,
  getPitcherStats: statsService.getPitcherStats,
  getGameStats: statsService.getGameStats
};
