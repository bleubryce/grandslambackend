
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { useGames } from "@/hooks/useGames";
import { useTeams } from "@/hooks/useTeams";
import { Skeleton } from "@/components/ui/skeleton";
import { useState, useEffect } from "react";
import { format } from "date-fns";

interface ProcessedGame {
  id: number;
  date: string;
  opponent: string;
  location: string;
  result: "W" | "L";
  score: {
    team: number;
    opponent: number;
  };
}

const GameLog = () => {
  const { games, isLoading: gamesLoading } = useGames();
  const { teams, isLoading: teamsLoading } = useTeams();
  const [recentGames, setRecentGames] = useState<ProcessedGame[]>([]);
  const isLoading = gamesLoading || teamsLoading;

  // Set a "home" team ID for the app - usually this would come from user settings
  const HOME_TEAM_ID = 119; // LA Dodgers as example

  useEffect(() => {
    if (games && games.length > 0 && teams && teams.length > 0) {
      // Get team name map
      const teamMap = new Map(teams.map(team => [team.id, team.name]));
      
      // Process games to match our format
      const processedGames = games
        .filter(game => game.homeTeamId === HOME_TEAM_ID || game.awayTeamId === HOME_TEAM_ID)
        .filter(game => game.homeScore !== undefined && game.awayScore !== undefined)
        .map(game => {
          const isHome = game.homeTeamId === HOME_TEAM_ID;
          const opponentId = isHome ? game.awayTeamId : game.homeTeamId;
          const opponentName = teamMap.get(opponentId) || 'Unknown Team';
          const ourScore = isHome ? game.homeScore || 0 : game.awayScore || 0;
          const theirScore = isHome ? game.awayScore || 0 : game.homeScore || 0;
          const result = ourScore > theirScore ? 'W' as const : 'L' as const;
          
          return {
            id: game.id,
            date: format(new Date(game.date), 'MMM d, yyyy'),
            opponent: opponentName,
            location: isHome ? 'Home' : 'Away',
            result,
            score: {
              team: ourScore,
              opponent: theirScore
            }
          };
        })
        .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
        .slice(0, 5);
      
      setRecentGames(processedGames);
    }
  }, [games, teams, HOME_TEAM_ID]);

  return (
    <Card className="col-span-2">
      <CardHeader>
        <CardTitle>Recent Games</CardTitle>
        <CardDescription>Last 5 games results</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {isLoading ? (
            // Loading state
            Array(5).fill(0).map((_, i) => (
              <div key={i} className="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0">
                <div>
                  <Skeleton className="h-4 w-28" />
                  <Skeleton className="h-3 w-20 mt-2" />
                </div>
                <div className="flex items-center space-x-3">
                  <Skeleton className="h-4 w-12" />
                  <Skeleton className="h-6 w-8" />
                </div>
              </div>
            ))
          ) : recentGames.length > 0 ? (
            recentGames.map((game) => (
              <div
                key={game.id}
                className="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0"
              >
                <div>
                  <p className="text-sm font-medium">vs {game.opponent}</p>
                  <div className="flex items-center text-xs text-muted-foreground mt-1">
                    <span>{game.date}</span>
                    <span className="mx-1">•</span>
                    <span>{game.location}</span>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <div className="text-center">
                    <p className="text-sm font-bold">
                      {game.score.team} - {game.score.opponent}
                    </p>
                  </div>
                  <Badge
                    variant="outline"
                    className={`${
                      game.result === "W"
                        ? "bg-green-50 text-green-700 border-green-200"
                        : "bg-red-50 text-red-700 border-red-200"
                    }`}
                  >
                    {game.result}
                  </Badge>
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-6 text-muted-foreground">
              No recent games available
            </div>
          )}
          <div className="mt-4 text-center">
            <a
              href="#"
              className="text-sm text-baseball-lightBlue hover:underline"
            >
              View full game log →
            </a>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default GameLog;
