
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { teamService, Team } from '../services/api';
import { mlbService } from '../services/mlbService';
import { useToast } from '@/hooks/use-toast';
import BaseballApi from '../services/baseballApi';

export const useTeams = () => {
  const { toast } = useToast();
  const queryClient = useQueryClient();

  // Get all teams (now using mlbService)
  const { data: teams, isLoading, error } = useQuery({
    queryKey: ['teams'],
    queryFn: async () => {
      try {
        // Fetch from MLB API
        const mlbTeams = await mlbService.getTeams();
        if (mlbTeams.length > 0) {
          return mlbTeams;
        }
        
        // Fallback to our API if MLB API fails
        const response = await teamService.getTeams();
        return response.data.data;
      } catch (err) {
        console.error("Error fetching teams:", err);
        // Fallback to our API if MLB API fails
        const response = await teamService.getTeams();
        return response.data.data;
      }
    },
  });

  // Create a new team
  const createTeamMutation = useMutation({
    mutationFn: (newTeam: Omit<Team, 'id'>) => teamService.createTeam(newTeam),
    onSuccess: () => {
      toast({
        title: 'Team created',
        description: 'The team has been created successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['teams'] });
    },
  });

  // Update a team
  const updateTeamMutation = useMutation({
    mutationFn: ({ id, team }: { id: number; team: Partial<Team> }) => 
      teamService.updateTeam(id, team),
    onSuccess: () => {
      toast({
        title: 'Team updated',
        description: 'The team has been updated successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['teams'] });
    },
  });

  // Delete a team
  const deleteTeamMutation = useMutation({
    mutationFn: (id: number) => teamService.deleteTeam(id),
    onSuccess: () => {
      toast({
        title: 'Team deleted',
        description: 'The team has been deleted successfully',
      });
      queryClient.invalidateQueries({ queryKey: ['teams'] });
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
    createTeam: createTeamMutation.mutate,
    updateTeam: updateTeamMutation.mutate,
    deleteTeam: deleteTeamMutation.mutate,
    getTeamWithRoster,
    getTeamAnalytics,
  };
};
