import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import baseballApi from '../services/api';
import {
  TeamAnalysisResponse,
  PlayerAnalysisResponse,
  GameAnalysisResponse,
  ModelAnalysisRequest,
  ModelAnalysisResponse,
  AuthResponse
} from '../types/api';

// Query keys
export const queryKeys = {
  health: ['health'],
  team: (teamId: number) => ['team', teamId],
  player: (playerId: number) => ['player', playerId],
  game: (gameId: number) => ['game', gameId],
  model: (type: string, id: number) => ['model', type, id]
};

// Authentication hooks
export const useLogin = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ username, password }: { username: string; password: string }): Promise<AuthResponse> => {
      return baseballApi.login(username, password);
    },
    onSuccess: () => {
      // Invalidate and refetch relevant queries after successful login
      queryClient.invalidateQueries();
    }
  });
};

// Health check hook
export const useHealthCheck = () => {
  return useQuery({
    queryKey: queryKeys.health,
    queryFn: () => baseballApi.checkHealth()
  });
};

// Team analysis hooks
export const useTeamAnalysis = (teamId: number) => {
  return useQuery({
    queryKey: queryKeys.team(teamId),
    queryFn: () => baseballApi.getTeamAnalysis(teamId),
    enabled: !!teamId
  });
};

// Player analysis hooks
export const usePlayerAnalysis = (playerId: number) => {
  return useQuery({
    queryKey: queryKeys.player(playerId),
    queryFn: () => baseballApi.getPlayerAnalysis(playerId),
    enabled: !!playerId
  });
};

// Game analysis hooks
export const useGameAnalysis = (gameId: number) => {
  return useQuery({
    queryKey: queryKeys.game(gameId),
    queryFn: () => baseballApi.getGameAnalysis(gameId),
    enabled: !!gameId
  });
};

// Model analysis hooks
export const useModelAnalysis = () => {
  return useMutation({
    mutationFn: async (request: ModelAnalysisRequest): Promise<ModelAnalysisResponse> => {
      return baseballApi.analyze(request);
    }
  });
};

// WebSocket integration hooks
export const useWebSocketConnection = () => {
  const queryClient = useQueryClient();

  const handleGameUpdate = (gameId: number) => {
    // Invalidate and refetch game data when we receive an update
    queryClient.invalidateQueries({
      queryKey: queryKeys.game(gameId)
    });
  };

  const handlePlayerUpdate = (playerId: number) => {
    // Invalidate and refetch player data when we receive an update
    queryClient.invalidateQueries({
      queryKey: queryKeys.player(playerId)
    });
  };

  return {
    handleGameUpdate,
    handlePlayerUpdate
  };
};

export default {
  useLogin,
  useHealthCheck,
  useTeamAnalysis,
  usePlayerAnalysis,
  useGameAnalysis,
  useModelAnalysis,
  useWebSocketConnection
}; 