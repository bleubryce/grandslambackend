
import { useState } from "react";
import Navbar from "@/components/Navbar";
import Sidebar from "@/components/Sidebar";
import StatCard from "@/components/Dashboard/StatCard";
import PerformanceChart from "@/components/Dashboard/PerformanceChart";
import TeamStats from "@/components/Dashboard/TeamStats";
import PlayerList from "@/components/Dashboard/PlayerList";
import GameLog from "@/components/Dashboard/GameLog";
import { BarChart3, Award, CalendarDays, TrendingUp, Users, Target } from "lucide-react";
import { useTeams } from "@/hooks/useTeams";
import { usePlayers } from "@/hooks/usePlayers";
import { useGames } from "@/hooks/useGames";
import { useEffect, useMemo } from "react";

const Index = () => {
  const [collapsed, setCollapsed] = useState(false);
  const { teams, isLoading: teamsLoading } = useTeams();
  const { players, isLoading: playersLoading } = usePlayers();
  const { games, isLoading: gamesLoading } = useGames();
  
  const isLoading = teamsLoading || playersLoading || gamesLoading;
  
  // Stats calculation
  const stats = useMemo(() => {
    if (!players || !games) {
      return {
        battingAvg: ".000",
        era: "0.00",
        playersCount: "0",
        nextGame: "None"
      };
    }
    
    // Calculate team batting average
    const nonPitchers = players.filter(p => !['P', 'SP', 'RP', 'CL'].includes(p.position));
    const battingAvg = nonPitchers.length > 0 
      ? (Math.random() * 0.05 + 0.265).toFixed(3) // Random BA between .265 and .315
      : ".000";
    
    // Calculate team ERA
    const pitchers = players.filter(p => ['P', 'SP', 'RP', 'CL'].includes(p.position));
    const era = pitchers.length > 0
      ? (Math.random() * 1.5 + 3.0).toFixed(2) // Random ERA between 3.00 and 4.50
      : "0.00";
    
    // Get players count
    const playersCount = players.length.toString();
    
    // Find next game
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const upcomingGames = games
      .filter(g => g.status === 'scheduled' && new Date(g.date) >= today)
      .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
    
    let nextGame = "None";
    if (upcomingGames.length > 0) {
      const game = upcomingGames[0];
      const gameDate = new Date(game.date);
      const month = gameDate.toLocaleString('default', { month: 'short' });
      const day = gameDate.getDate();
      nextGame = `${month} ${day}`;
    }
    
    return {
      battingAvg,
      era,
      playersCount,
      nextGame
    };
  }, [players, games]);
  
  // Calculate trends (in a real app, this would compare to previous time periods)
  const battingTrend = useMemo(() => ({ 
    value: parseFloat((Math.random() * 8 - 3).toFixed(1)), 
    isPositive: Math.random() > 0.3 // 70% chance of positive trend
  }), []);
  
  const eraTrend = useMemo(() => ({ 
    value: parseFloat((Math.random() * 8 - 3).toFixed(1)), 
    isPositive: Math.random() > 0.6  // ERA: lower is better, so flipped probability
  }), []);

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <Navbar />
      <div className="flex flex-1 pt-16">
        <Sidebar />
        <div className="flex-1 ml-64 p-8">
          <div className="mb-8">
            <h1 className="text-3xl font-bold animate-fade-in">Baseball Analytics Dashboard</h1>
            <p className="text-muted-foreground animate-fade-in animate-delay-100">
              Track team performance and player statistics
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <StatCard
              title="Team Batting Average"
              value={stats.battingAvg}
              icon={Target}
              trend={battingTrend}
              className="animate-fade-in"
              isLoading={isLoading}
            />
            <StatCard
              title="Team ERA"
              value={stats.era}
              icon={TrendingUp}
              trend={eraTrend}
              className="animate-fade-in animate-delay-100"
              isLoading={isLoading}
            />
            <StatCard
              title="Total Players"
              value={stats.playersCount}
              icon={Users}
              description="Active roster count"
              className="animate-fade-in animate-delay-200"
              isLoading={isLoading}
            />
            <StatCard
              title="Next Game"
              value={stats.nextGame}
              icon={CalendarDays}
              description={stats.nextGame !== "None" ? "vs Opponent @ Home" : "No games scheduled"}
              className="animate-fade-in animate-delay-300"
              isLoading={isLoading}
            />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-6 gap-6">
            <PerformanceChart />
            <div className="col-span-1 lg:col-span-2">
              <div className="space-y-6">
                <TeamStats />
                <PlayerList />
              </div>
            </div>
            <GameLog />
          </div>
        </div>
      </div>
    </div>
  );
};

export default Index;
