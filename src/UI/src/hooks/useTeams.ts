import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import teamService, { Team } from '../services/team.service';
import { mlbService } from '../services/mlbService';
import { useToast } from '@/hooks/use-toast';
import BaseballApi from '../services/baseballApi';

export const useTeams = () => {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  const { data: teams, isLoading, error } = useQuery({
    queryKey: ['teams'],
    queryFn: teamService.getTeams,
  });

  const createTeam = useMutation({
    mutationFn: teamService.createTeam,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teams'] });
      toast({
        title: 'Success',
        description: 'Team created successfully',
      });
    },
    onError: (error) => {
      toast({
        title: 'Error',
        description: 'Failed to create team',
        variant: 'destructive',
      });
    },
  });

  const updateTeam = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Team> }) =>
      teamService.updateTeam(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teams'] });
      toast({
        title: 'Success',
        description: 'Team updated successfully',
      });
    },
    onError: (error) => {
      toast({
        title: 'Error',
        description: 'Failed to update team',
        variant: 'destructive',
      });
    },
  });

  const deleteTeam = useMutation({
    mutationFn: teamService.deleteTeam,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teams'] });
      toast({
        title: 'Success',
        description: 'Team deleted successfully',
      });
    },
    onError: (error) => {
      toast({
        title: 'Error',
        description: 'Failed to delete team',
        variant: 'destructive',
      });
    },
  });

  // Get team by ID with roster (now using backend API first, falling back to mlbService)
  const getTeamWithRoster = (id: number) => {
    return useQuery({
      queryKey: ['teams', id, 'roster'],
      queryFn: async () => {
        try {
          // Try to get team stats from our backend API
          const backendTeamStats = await BaseballApi.getTeamStats(id);
          
          // If backend API succeeds, continue with MLB API for roster
          const team = await mlbService.getTeam(id);
          if (!team) throw new Error("Team not found");
          
          // Get roster from MLB API
          const players = await mlbService.getPlayers(id);
          
          // Return combined data
          return {
            ...team,
            roster: players,
            stats: backendTeamStats
          };
        } catch (err) {
          console.error("Error fetching team with roster:", err);
          // Fallback to MLB API
          const team = await mlbService.getTeam(id);
          if (!team) throw new Error("Team not found");
          
          // Get roster from MLB API
          const players = await mlbService.getPlayers(id);
          
          // Fallback to our old API if needed
          try {
            const [teamResponse, rosterResponse] = await Promise.all([
              teamService.getTeam(id),
              teamService.getTeamRoster(id),
            ]);
            
            return {
              ...team,
              roster: players,
              apiData: teamResponse.data.data
            };
          } catch (innerErr) {
            return {
              ...team,
              roster: players
            };
          }
        }
      },
    });
  };
  
  // Get advanced team analytics from our backend
  const getTeamAnalytics = (id: number) => {
    return useQuery({
      queryKey: ['teams', id, 'analytics'],
      queryFn: async () => {
        try {
          const analytics = await BaseballApi.analyze({ type: 'team', id });
          return analytics;
        } catch (err) {
          console.error("Error fetching team analytics:", err);
          throw err;
        }
      },
      enabled: !!id, // Only run if id is provided
    });
  };

  return {
    teams,
    isLoading,
    error,
    createTeam,
    updateTeam,
    deleteTeam,
    getTeamWithRoster,
    getTeamAnalytics,
  };
};
