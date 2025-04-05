
import { useState } from "react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import StatCard from "@/components/Dashboard/StatCard";
import GameLog from "@/components/Dashboard/GameLog";
import TeamStats from "@/components/Dashboard/TeamStats";
import PerformanceChart from "@/components/Dashboard/PerformanceChart";
import { 
  Users, Trophy, Circle, Calendar, TrendingUp,
  BatteryCharging, Target, Timer, Ruler, ArrowUpRight, Shield
} from "lucide-react";
import { useGames } from "@/hooks/useGames";
import { usePlayers } from "@/hooks/usePlayers";
import { useTeams } from "@/hooks/useTeams";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Skeleton } from "@/components/ui/skeleton";

const Dashboard = () => {
  const [activeTab, setActiveTab] = useState("overview");
  const { games, isLoading: gamesLoading } = useGames();
  const { players, isLoading: playersLoading } = usePlayers();
  const { teams, isLoading: teamsLoading } = useTeams();
  
  const isLoading = gamesLoading || playersLoading || teamsLoading;
  
  // Top players for stats
  const topBatters = players 
    ? [...players]
        .filter(p => !['P', 'SP', 'RP', 'CL'].includes(p.position || ''))
        .sort((a, b) => {
          const aAvg = a.battingAverage || 0;
          const bAvg = b.battingAverage || 0;
          return bAvg - aAvg;
        })
        .slice(0, 5)
    : [];
  
  const topPitchers = players
    ? [...players]
        .filter(p => ['P', 'SP', 'RP', 'CL'].includes(p.position || ''))
        .sort((a, b) => {
          const aEra = a.era || 9.99;
          const bEra = b.era || 9.99;
          return aEra - bEra;
        })
        .slice(0, 5)
    : [];
  
  return (
    <div className="container mx-auto py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold tracking-tight">Baseball Analytics Dashboard</h1>
        <p className="text-muted-foreground">
          Track team performance, player statistics, and game analytics
        </p>
      </div>
      
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="bg-card">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="players">Player Stats</TabsTrigger>
          <TabsTrigger value="teams">Team Comparison</TabsTrigger>
          <TabsTrigger value="predictions">Predictive Models</TabsTrigger>
        </TabsList>
        
        <TabsContent value="overview" className="space-y-6">
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatCard
              title="Total Teams"
              value={teams?.length || 0}
              icon={Trophy}
              description="Active MLB Teams"
              isLoading={isLoading}
            />
            <StatCard
              title="Total Players"
              value={players?.length || 0}
              icon={Users}
              description="Active MLB Players"
              isLoading={isLoading}
            />
            <StatCard
              title="Scheduled Games"
              value={games?.filter(g => g.status === 'scheduled')?.length || 0}
              icon={Calendar}
              description="Upcoming games"
              isLoading={isLoading}
            />
            <StatCard
              title="League Batting Avg"
              value={0.248}
              icon={Circle}
              description="League average"
              trend={{ value: 2.1, isPositive: true }}
              isLoading={isLoading}
            />
          </div>
          
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-8">
            <PerformanceChart />
            <TeamStats />
            <GameLog />
          </div>
        </TabsContent>
        
        <TabsContent value="players" className="space-y-6">
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatCard
              title="Top Batting Average"
              value={topBatters[0]?.battingAverage || 0}
              icon={Target}
              description={topBatters[0] ? `${topBatters[0].firstName} ${topBatters[0].lastName}` : "Loading..."}
              isLoading={isLoading}
            />
            <StatCard
              title="Top ERA"
              value={topPitchers[0]?.era || 0}
              icon={TrendingUp}
              description={topPitchers[0] ? `${topPitchers[0].firstName} ${topPitchers[0].lastName}` : "Loading..."}
              isLoading={isLoading}
            />
            <StatCard
              title="Most HR"
              value={topBatters[0]?.homeRuns || 0}
              icon={ArrowUpRight}
              description="Season leader"
              isLoading={isLoading}
            />
            <StatCard
              title="Most Strikeouts"
              value={topPitchers[0]?.strikeouts || 0}
              icon={BatteryCharging}
              description="Pitcher leader"
              isLoading={isLoading}
            />
          </div>

          <div className="grid gap-6 grid-cols-1 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Top Hitters</CardTitle>
              </CardHeader>
              <CardContent>
                {isLoading ? (
                  Array(5).fill(0).map((_, i) => (
                    <div key={i} className="flex justify-between items-center py-2 border-b">
                      <Skeleton className="h-4 w-36" />
                      <Skeleton className="h-4 w-16" />
                    </div>
                  ))
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Player</TableHead>
                        <TableHead>Pos</TableHead>
                        <TableHead className="text-right">AVG</TableHead>
                        <TableHead className="text-right">HR</TableHead>
                        <TableHead className="text-right">RBI</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {topBatters.map((player) => (
                        <TableRow key={player.id}>
                          <TableCell className="font-medium">{player.lastName}, {player.firstName.charAt(0)}</TableCell>
                          <TableCell>{player.position}</TableCell>
                          <TableCell className="text-right">{player.battingAverage?.toFixed(3) || ".000"}</TableCell>
                          <TableCell className="text-right">{player.homeRuns || 0}</TableCell>
                          <TableCell className="text-right">{player.rbi || 0}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader>
                <CardTitle>Top Pitchers</CardTitle>
              </CardHeader>
              <CardContent>
                {isLoading ? (
                  Array(5).fill(0).map((_, i) => (
                    <div key={i} className="flex justify-between items-center py-2 border-b">
                      <Skeleton className="h-4 w-36" />
                      <Skeleton className="h-4 w-16" />
                    </div>
                  ))
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Player</TableHead>
                        <TableHead className="text-right">W-L</TableHead>
                        <TableHead className="text-right">ERA</TableHead>
                        <TableHead className="text-right">IP</TableHead>
                        <TableHead className="text-right">SO</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {topPitchers.map((player) => (
                        <TableRow key={player.id}>
                          <TableCell className="font-medium">{player.lastName}, {player.firstName.charAt(0)}</TableCell>
                          <TableCell className="text-right">{player.wins || 0}-{player.losses || 0}</TableCell>
                          <TableCell className="text-right">{player.era?.toFixed(2) || "0.00"}</TableCell>
                          <TableCell className="text-right">{player.inningsPitched?.toFixed(1) || "0.0"}</TableCell>
                          <TableCell className="text-right">{player.strikeouts || 0}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>
        
        <TabsContent value="teams" className="space-y-6">
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatCard
              title="Total Games"
              value={games?.length || 0}
              icon={Calendar}
              description="Current season"
              isLoading={isLoading}
            />
            <StatCard
              title="League ERA"
              value={3.98}
              icon={Target}
              description="League average"
              trend={{ value: 0.5, isPositive: false, label: "vs last month" }}
              isLoading={isLoading}
            />
            <StatCard
              title="Average Game Time"
              value="2:45"
              icon={Timer}
              description="Hours:Minutes"
              trend={{ value: 8, isPositive: false, label: "vs last season" }}
              isLoading={isLoading}
            />
            <StatCard
              title="Field Dimensions"
              value="330-400-330"
              icon={Ruler}
              description="LF-CF-RF (feet)"
              isLoading={isLoading}
            />
          </div>
          
          <Card>
            <CardHeader>
              <CardTitle>Team Standings</CardTitle>
            </CardHeader>
            <CardContent>
              {isLoading ? (
                Array(6).fill(0).map((_, i) => (
                  <div key={i} className="flex justify-between items-center py-2 border-b">
                    <Skeleton className="h-4 w-36" />
                    <div className="flex space-x-6">
                      <Skeleton className="h-4 w-8" />
                      <Skeleton className="h-4 w-8" />
                      <Skeleton className="h-4 w-16" />
                    </div>
                  </div>
                ))
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Team</TableHead>
                      <TableHead className="text-right">W</TableHead>
                      <TableHead className="text-right">L</TableHead>
                      <TableHead className="text-right">PCT</TableHead>
                      <TableHead className="text-right">GB</TableHead>
                      <TableHead className="text-right">L10</TableHead>
                      <TableHead className="text-right">STRK</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {teams?.slice(0, 6).map((team, index) => (
                      <TableRow key={team.id}>
                        <TableCell className="font-medium">{team.name}</TableCell>
                        <TableCell className="text-right">{90 - index * 5}</TableCell>
                        <TableCell className="text-right">{72 + index * 5}</TableCell>
                        <TableCell className="text-right">{(0.556 - index * 0.031).toFixed(3)}</TableCell>
                        <TableCell className="text-right">{index === 0 ? "-" : `${index * 5.0}`}</TableCell>
                        <TableCell className="text-right">{index % 2 === 0 ? "7-3" : "5-5"}</TableCell>
                        <TableCell className="text-right">{index % 3 === 0 ? "W3" : index % 3 === 1 ? "L2" : "W1"}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
              <div className="mt-4 flex justify-center">
                <Button variant="outline" size="sm">View All Teams</Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
        
        <TabsContent value="predictions" className="space-y-6">
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatCard
              title="Win Probability"
              value="64.2%"
              icon={Trophy}
              description="Next game forecast"
              trend={{ value: 3.5, isPositive: true, label: "vs last prediction" }}
              isLoading={false}
            />
            <StatCard
              title="Run Differential"
              value="+1.5"
              icon={TrendingUp}
              description="Predicted margin"
              isLoading={false}
            />
            <StatCard
              title="Pitcher Matchup"
              value="73%"
              icon={Shield}
              description="Advantage rating"
              isLoading={false}
            />
            <StatCard
              title="Model Confidence"
              value="High"
              icon={Target}
              description="Based on 1000+ simulations"
              isLoading={false}
            />
          </div>
          
          <Card>
            <CardHeader>
              <CardTitle>Prediction Model</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex flex-col space-y-2 md:flex-row md:space-y-0 md:space-x-4">
                <Button className="bg-baseball-navy">Run Prediction</Button>
                <Button variant="outline">Export Report</Button>
              </div>
              <div className="rounded-lg border bg-muted/40 p-8 text-center">
                <h3 className="mb-2 text-xl font-semibold">Predictive Analytics Coming Soon</h3>
                <p className="text-muted-foreground">
                  Our machine learning models are being trained with the latest MLB data to provide accurate predictions and insights for upcoming games.
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default Dashboard;
