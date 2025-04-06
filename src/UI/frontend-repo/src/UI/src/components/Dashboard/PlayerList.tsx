
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar } from "@/components/ui/avatar";
import { usePlayers } from "@/hooks/usePlayers";
import { Skeleton } from "@/components/ui/skeleton";
import { useEffect, useState } from "react";

interface PlayerDisplay {
  id: number;
  name: string;
  position: string;
  stat: number;
  statLabel: string;
  photoUrl?: string;
}

const PlayerList = () => {
  const { players, isLoading, error } = usePlayers();
  const [battingLeaders, setBattingLeaders] = useState<PlayerDisplay[]>([]);
  const [pitchingLeaders, setPitchingLeaders] = useState<PlayerDisplay[]>([]);

  useEffect(() => {
    if (players && players.length > 0) {
      // Process players into batting and pitching leaders
      const hitters = players.filter(p => !['P', 'SP', 'RP', 'CL'].includes(p.position))
                             .sort(() => Math.random() - 0.5) // Random sort for demo
                             .slice(0, 5);
                             
      const pitchers = players.filter(p => ['P', 'SP', 'RP', 'CL'].includes(p.position))
                              .sort(() => Math.random() - 0.5) // Random sort for demo
                              .slice(0, 5);
      
      // Create batting leaders
      const battingList = hitters.map(player => ({
        id: player.id,
        name: `${player.firstName} ${player.lastName}`,
        position: player.position,
        stat: parseFloat((Math.random() * 0.15 + 0.25).toFixed(3)), // Random BA between .250 and .400
        statLabel: "AVG",
        photoUrl: player.photoUrl
      }));
      
      // Sort by stat value descending
      battingList.sort((a, b) => b.stat - a.stat);
      setBattingLeaders(battingList);
      
      // Create pitching leaders
      const pitchingList = pitchers.map(player => ({
        id: player.id,
        name: `${player.firstName} ${player.lastName}`,
        position: player.position,
        stat: parseFloat((Math.random() * 2 + 1.5).toFixed(2)), // Random ERA between 1.50 and 3.50
        statLabel: "ERA",
        photoUrl: player.photoUrl
      }));
      
      // Sort by stat value ascending (lower ERA is better)
      pitchingList.sort((a, b) => a.stat - b.stat);
      setPitchingLeaders(pitchingList);
    }
  }, [players]);

  return (
    <Card className="col-span-2">
      <CardHeader>
        <CardTitle>Team Leaders</CardTitle>
        <CardDescription>Top performing players this season</CardDescription>
      </CardHeader>
      <CardContent>
        <Tabs defaultValue="batting">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="batting">Batting</TabsTrigger>
            <TabsTrigger value="pitching">Pitching</TabsTrigger>
          </TabsList>
          
          <TabsContent value="batting" className="space-y-4 mt-4">
            {isLoading ? (
              // Loading state
              Array(5).fill(0).map((_, i) => (
                <div key={i} className="flex items-center justify-between p-2">
                  <div className="flex items-center">
                    <Skeleton className="h-8 w-8 rounded-full" />
                    <div className="ml-3">
                      <Skeleton className="h-4 w-24" />
                      <Skeleton className="h-3 w-12 mt-1" />
                    </div>
                  </div>
                  <Skeleton className="h-4 w-10" />
                </div>
              ))
            ) : (
              battingLeaders.map((player) => (
                <div
                  key={player.id}
                  className="flex items-center justify-between p-2 hover:bg-muted rounded-md transition-colors"
                >
                  <div className="flex items-center">
                    <Avatar className="h-8 w-8 bg-baseball-navy text-white">
                      {player.photoUrl ? (
                        <img src={player.photoUrl} alt={player.name} className="h-full w-full object-cover" />
                      ) : (
                        <span className="text-xs font-bold">
                          {player.name
                            .split(" ")
                            .map((n) => n[0])
                            .join("")}
                        </span>
                      )}
                    </Avatar>
                    <div className="ml-3">
                      <p className="text-sm font-medium">{player.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {player.position}
                      </p>
                    </div>
                  </div>
                  <div className="flex flex-col items-end">
                    <span className="font-bold text-base">{player.stat}</span>
                    <span className="text-xs text-muted-foreground">
                      {player.statLabel}
                    </span>
                  </div>
                </div>
              ))
            )}
          </TabsContent>
          
          <TabsContent value="pitching" className="space-y-4 mt-4">
            {isLoading ? (
              // Loading state
              Array(5).fill(0).map((_, i) => (
                <div key={i} className="flex items-center justify-between p-2">
                  <div className="flex items-center">
                    <Skeleton className="h-8 w-8 rounded-full" />
                    <div className="ml-3">
                      <Skeleton className="h-4 w-24" />
                      <Skeleton className="h-3 w-12 mt-1" />
                    </div>
                  </div>
                  <Skeleton className="h-4 w-10" />
                </div>
              ))
            ) : (
              pitchingLeaders.map((player) => (
                <div
                  key={player.id}
                  className="flex items-center justify-between p-2 hover:bg-muted rounded-md transition-colors"
                >
                  <div className="flex items-center">
                    <Avatar className="h-8 w-8 bg-baseball-green text-white">
                      {player.photoUrl ? (
                        <img src={player.photoUrl} alt={player.name} className="h-full w-full object-cover" />
                      ) : (
                        <span className="text-xs font-bold">
                          {player.name
                            .split(" ")
                            .map((n) => n[0])
                            .join("")}
                        </span>
                      )}
                    </Avatar>
                    <div className="ml-3">
                      <p className="text-sm font-medium">{player.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {player.position}
                      </p>
                    </div>
                  </div>
                  <div className="flex flex-col items-end">
                    <span className="font-bold text-base">{player.stat}</span>
                    <span className="text-xs text-muted-foreground">
                      {player.statLabel}
                    </span>
                  </div>
                </div>
              ))
            )}
          </TabsContent>
        </Tabs>
      </CardContent>
    </Card>
  );
};

export default PlayerList;
