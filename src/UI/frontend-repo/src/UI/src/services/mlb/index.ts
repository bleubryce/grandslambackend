
import { teamsService } from './teamsService';
import { playersService } from './playersService';
import { gamesService } from './gamesService';
import { statsService } from './statsService';

export const mlbService = {
  teams: teamsService,
  players: playersService,
  games: gamesService,
  stats: statsService,
};
