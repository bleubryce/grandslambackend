
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { playerService, Player } from '../services/api';
import { mlbService } from '../services/mlbService';
import { useToast } from '@/hooks/use-toast';
import BaseballApi, { PlayerStats as BackendPlayerStats } from '../services/baseballApi';

export const usePlayers = () => {
  const { toast } = useToast();
  const queryClient = useQueryClient();

  // Get all players (now using mlbService with includeStats=true)
  const { data: players, isLoading, error } = useQuery({
    queryKey: ['players'],
    queryFn: async () => {
      try {
        // Fetch from MLB API with stats
        const mlbPlayers = await mlbService.getPlayers(undefined, true);
        if (mlbPlayers.length > 0) {
          return mlbPlayers;
        }
        
        // Fallback to our API if MLB API fails
        const response = await playerService.getPlayers();
        return response.data.data;
      } catch (err) {
        console.error("Error fetching players:", err);
        // Fallback to our API
        const response = await playerService.getPlayers();
        return response.data.data;
      }
    },
  });

  // Create a new player
  const createPlayerMutation = useMutation({
    mutationFn: (newPlayer: Omit<Player, 'id'>) => playerService.createPlayer(newPlayer),
    onSuccess: () => {
      toast({
        title: 'Player created',
        description: 'The player has been created successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['players'] });
    },
  });

  // Update a player
  const updatePlayerMutation = useMutation({
    mutationFn: ({ id, player }: { id: number; player: Partial<Player> }) => 
      playerService.updatePlayer(id, player),
    onSuccess: () => {
      toast({
        title: 'Player updated',
        description: 'The player has been updated successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['players'] });
    },
  });

  // Delete a player
  const deletePlayerMutation = useMutation({
    mutationFn: (id: number) => playerService.deletePlayer(id),
    onSuccess: () => {
      toast({
        title: 'Player deleted',
        description: 'The player has been deleted successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['players'] });
    },
  });

  // Get player by ID with stats (now using backend API first, falling back to mlbService)
  const getPlayerWithStats = (id: number) => {
    return useQuery({
      queryKey: ['players', id, 'stats'],
      queryFn: async () => {
        try {
          // First try to get data from our backend API
          const backendPlayer = await BaseballApi.getPlayerStats(id);
          if (backendPlayer) {
            // Convert backend format to our app format
            return {
              id: backendPlayer.playerId,
              firstName: backendPlayer.playerName.split(' ')[0],
              lastName: backendPlayer.playerName.split(' ').slice(1).join(' '),
              position: backendPlayer.position,
              teamId: backendPlayer.teamId,
              stats: backendPlayer.results,
              isPitcher: backendPlayer.results.pitching !== undefined,
              // Add other fields that might be needed
              battingAverage: backendPlayer.results.batting.battingAverage,
              homeRuns: backendPlayer.results.batting.homeRuns,
              rbi: backendPlayer.results.batting.rbis,
              era: backendPlayer.results.pitching?.era,
              wins: backendPlayer.results.pitching?.wins,
              losses: backendPlayer.results.pitching?.losses,
            };
          }
          
          // If backend API fails, try MLB API
          const player = await mlbService.getPlayer(id);
          if (!player) throw new Error("Player not found");
          
          // Determine if player is a pitcher
          const isPitcher = ['P', 'SP', 'RP', 'CL'].includes(player.position);
          
          let stats;
          if (isPitcher) {
            stats = await mlbService.getPitcherStats(id);
          } else {
            stats = await mlbService.getPlayerStats(id);
          }
          
          return {
            ...player,
            stats: stats || {},
            isPitcher
          };
        } catch (err) {
          console.error("Error fetching player with stats:", err);
          // Fallback to our API
          const [playerResponse, statsResponse] = await Promise.all([
            playerService.getPlayer(id),
            playerService.getPlayerStats(id),
          ]);
          
          return {
            ...playerResponse.data.data,
            stats: statsResponse.data.data,
          };
        }
      },
    });
  };
  
  // Get advanced player analytics from our backend
  const getPlayerAnalytics = (id: number) => {
    return useQuery({
      queryKey: ['players', id, 'analytics'],
      queryFn: async () => {
        try {
          const analytics = await BaseballApi.analyze({ type: 'player', id });
          return analytics;
        } catch (err) {
          console.error("Error fetching player analytics:", err);
          throw err;
        }
      },
      enabled: !!id, // Only run if id is provided
    });
  };

  return {
    players,
    isLoading,
    error,
    createPlayer: createPlayerMutation.mutate,
    updatePlayer: updatePlayerMutation.mutate,
    deletePlayer: deletePlayerMutation.mutate,
    getPlayerWithStats,
    getPlayerAnalytics,
  };
};
