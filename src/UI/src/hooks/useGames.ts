
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { gameService, Game } from '../services/api';
import { mlbService } from '../services/mlbService';
import { useToast } from '@/hooks/use-toast';
import BaseballApi from '../services/baseballApi';

export const useGames = () => {
  const { toast } = useToast();
  const queryClient = useQueryClient();

  // Get all games (now prioritizing backend API, falling back to mlbService)
  const { data: games, isLoading, error } = useQuery({
    queryKey: ['games'],
    queryFn: async () => {
      try {
        // First try to get data from our backend API directly
        try {
          // First check if backend is healthy
          const health = await BaseballApi.checkHealth();
          console.log("Backend API health check:", health);
          
          // Try to get game data from our backend API
          // Get game IDs from MLB API to fetch from our backend
          const mlbGameIds = await mlbService.getGameIds();
          
          // Fetch each game's stats from our backend
          if (mlbGameIds && mlbGameIds.length > 0) {
            const gamePromises = mlbGameIds.map(async (id) => {
              try {
                return await BaseballApi.getGameStats(id);
              } catch (err) {
                console.log(`Failed to get game ${id} from backend, will use MLB data`);
                return null;
              }
            });
            
            const backendGames = await Promise.all(gamePromises);
            const validGames = backendGames.filter(Boolean);
            
            if (validGames.length > 0) {
              console.log(`Found ${validGames.length} games from backend API`);
              return validGames.map(game => ({
                id: game.gameId,
                homeTeamId: game.homeTeam.teamId,
                awayTeamId: game.awayTeam.teamId,
                homeScore: game.homeTeam.score,
                awayScore: game.awayTeam.score,
                status: 'completed',
                location: 'From Backend',
                date: new Date().toISOString(),
                startTime: '19:00',
              }));
            }
          }
        } catch (backendError) {
          console.error("Error accessing backend API:", backendError);
          // Continue to MLB fallback
        }
        
        // Fallback to MLB API
        const mlbGames = await mlbService.getGames();
        if (mlbGames && mlbGames.length > 0) {
          return mlbGames;
        }
        
        // Last resort: fallback to our legacy API
        const response = await gameService.getGames();
        return response.data.data;
      } catch (err) {
        console.error("Error fetching games:", err);
        // Last fallback to our API
        try {
          const response = await gameService.getGames();
          return response.data.data;
        } catch (finalError) {
          console.error("Final fallback failed:", finalError);
          return [];
        }
      }
    },
  });

  // Create a new game
  const createGameMutation = useMutation({
    mutationFn: (newGame: Omit<Game, 'id'>) => gameService.createGame(newGame),
    onSuccess: () => {
      toast({
        title: 'Game created',
        description: 'The game has been created successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['games'] });
    },
  });

  // Update a game
  const updateGameMutation = useMutation({
    mutationFn: ({ id, game }: { id: number; game: Partial<Game> }) => 
      gameService.updateGame(id, game),
    onSuccess: () => {
      toast({
        title: 'Game updated',
        description: 'The game has been updated successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['games'] });
    },
  });

  // Delete a game
  const deleteGameMutation = useMutation({
    mutationFn: (id: number) => gameService.deleteGame(id),
    onSuccess: () => {
      toast({
        title: 'Game deleted',
        description: 'The game has been deleted successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['games'] });
    },
  });

  // Get game by ID with stats (now using backend API first, falling back to mlbService)
  const getGameWithStats = (id: number) => {
    return useQuery({
      queryKey: ['games', id, 'stats'],
      queryFn: async () => {
        try {
          // First try to get data from our backend API
          const backendGame = await BaseballApi.getGameStats(id);
          if (backendGame) {
            return {
              id: backendGame.gameId,
              homeTeamId: backendGame.homeTeam.teamId,
              awayTeamId: backendGame.awayTeam.teamId,
              homeScore: backendGame.homeTeam.score,
              awayScore: backendGame.awayTeam.score,
              stats: backendGame.results,
              // Add other fields that might be needed
            };
          }
          
          // Get game from MLB API
          const game = await mlbService.getGame(id);
          if (!game) throw new Error("Game not found");
          
          // Get game stats from MLB API
          const stats = await mlbService.getGameStats(id);
          
          return {
            ...game,
            stats: stats || {},
          };
        } catch (err) {
          console.error("Error fetching game with stats:", err);
          // Fallback to our API
          const [gameResponse, statsResponse] = await Promise.all([
            gameService.getGame(id),
            gameService.getGameStats(id),
          ]);
          
          return {
            ...gameResponse.data.data,
            stats: statsResponse.data.data,
          };
        }
      },
    });
  };
  
  // Get advanced game analytics from our backend
  const getGameAnalytics = (id: number) => {
    return useQuery({
      queryKey: ['games', id, 'analytics'],
      queryFn: async () => {
        try {
          const analytics = await BaseballApi.analyze({ type: 'game', id });
          return analytics;
        } catch (err) {
          console.error("Error fetching game analytics:", err);
          throw err;
        }
      },
      enabled: !!id, // Only run if id is provided
    });
  };

  return {
    games,
    isLoading,
    error,
    createGame: createGameMutation.mutate,
    updateGame: updateGameMutation.mutate,
    deleteGame: deleteGameMutation.mutate,
    getGameWithStats,
    getGameAnalytics,
  };
};
