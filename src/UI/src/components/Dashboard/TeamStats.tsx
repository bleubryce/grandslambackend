
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  PieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  Legend,
  Tooltip,
} from "recharts";
import { useGames } from "@/hooks/useGames";
import { Skeleton } from "@/components/ui/skeleton";
import { useState, useEffect } from "react";

const COLORS = ["#0A2351", "#E31937"];

const TeamStats = () => {
  const { games, isLoading } = useGames();
  const [wins, setWins] = useState(0);
  const [losses, setLosses] = useState(0);
  const [winPercentage, setWinPercentage] = useState("0.000");
  
  // Set a "home" team ID for the app - usually this would come from user settings
  const HOME_TEAM_ID = 119; // LA Dodgers as example

  useEffect(() => {
    if (games && games.length > 0) {
      // Filter completed games
      const completedGames = games.filter(
        game => game.status === 'completed' && 
                game.homeScore !== undefined && 
                game.awayScore !== undefined
      );
      
      // Count wins and losses for the home team
      let winCount = 0;
      let lossCount = 0;
      
      completedGames.forEach(game => {
        const isHome = game.homeTeamId === HOME_TEAM_ID;
        const ourScore = isHome ? game.homeScore : game.awayScore;
        const theirScore = isHome ? game.awayScore : game.homeScore;
        
        if (ourScore !== undefined && theirScore !== undefined) {
          if (ourScore > theirScore) {
            winCount++;
          } else {
            lossCount++;
          }
        }
      });
      
      setWins(winCount);
      setLosses(lossCount);
      
      // Calculate win percentage
      const gamesPlayed = winCount + lossCount;
      const percentage = gamesPlayed > 0 ? winCount / gamesPlayed : 0;
      setWinPercentage(percentage.toFixed(3));
    }
  }, [games, HOME_TEAM_ID]);

  const data = [
    { name: "Wins", value: wins },
    { name: "Losses", value: losses },
  ];

  return (
    <Card className="col-span-2">
      <CardHeader>
        <CardTitle>Season Record</CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="space-y-4">
            <Skeleton className="h-[250px] w-full" />
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-full" />
          </div>
        ) : (
          <>
            <div className="h-[250px]">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={data}
                    cx="50%"
                    cy="50%"
                    innerRadius={60}
                    outerRadius={80}
                    fill="#8884d8"
                    paddingAngle={5}
                    dataKey="value"
                    label={({ name, percent }) =>
                      `${name}: ${(percent * 100).toFixed(0)}%`
                    }
                  >
                    {data.map((entry, index) => (
                      <Cell
                        key={`cell-${index}`}
                        fill={COLORS[index % COLORS.length]}
                      />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            </div>
            <div className="mt-4 space-y-2">
              <div className="flex justify-between items-center">
                <div className="flex items-center">
                  <div className="w-3 h-3 rounded-full bg-baseball-navy mr-2"></div>
                  <span className="text-sm">Wins</span>
                </div>
                <span className="font-bold">{wins}</span>
              </div>
              <div className="flex justify-between items-center">
                <div className="flex items-center">
                  <div className="w-3 h-3 rounded-full bg-baseball-red mr-2"></div>
                  <span className="text-sm">Losses</span>
                </div>
                <span className="font-bold">{losses}</span>
              </div>
              <div className="flex justify-between items-center border-t pt-2">
                <span className="text-sm font-medium">Win Percentage</span>
                <span className="font-bold">{winPercentage}</span>
              </div>
            </div>
          </>
        )}
      </CardContent>
    </Card>
  );
};

export default TeamStats;
