
import { useQuery } from '@tanstack/react-query';
import { playerService } from '../services/playerService';

export const usePlayers = () => {
  const { data, isLoading, error } = useQuery({
    queryKey: ['players'],
    queryFn: async () => {
      const response = await playerService.getPlayers();
      return response.data.data;
    }
  });

  return {
    players: data,
    isLoading,
    error
  };
};
