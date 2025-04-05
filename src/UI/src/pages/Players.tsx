
import React from "react";
import { usePlayers } from "@/hooks/usePlayers";
import Navbar from "@/components/Navbar";
import Sidebar from "@/components/Sidebar";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Skeleton } from "@/components/ui/skeleton";
import { Avatar } from "@/components/ui/avatar";

const Players = () => {
  const { players, isLoading, error } = usePlayers();

  return (
    <div className="min-h-screen bg-gray-100">
      <Navbar />
      <Sidebar />
      
      <div className="pt-16 pl-64">
        <div className="p-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-6">Player Analysis</h1>
          
          <Tabs defaultValue="all" className="w-full">
            <TabsList>
              <TabsTrigger value="all">All Players</TabsTrigger>
              <TabsTrigger value="pitchers">Pitchers</TabsTrigger>
              <TabsTrigger value="batters">Batters</TabsTrigger>
            </TabsList>
            
            <TabsContent value="all" className="mt-4">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {isLoading ? (
                  // Loading skeletons
                  Array(9).fill(0).map((_, i) => (
                    <Card key={i}>
                      <CardHeader className="pb-2">
                        <div className="flex items-center space-x-4">
                          <Skeleton className="h-12 w-12 rounded-full" />
                          <div className="space-y-2">
                            <Skeleton className="h-4 w-[200px]" />
                            <Skeleton className="h-4 w-[150px]" />
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <div className="grid grid-cols-2 gap-2">
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                        </div>
                      </CardContent>
                    </Card>
                  ))
                ) : error ? (
                  <div className="col-span-full text-center py-8">
                    <p className="text-red-500">Failed to load player data</p>
                  </div>
                ) : (
                  players?.map((player) => (
                    <Card key={player.id} className="hover:shadow-lg transition-shadow">
                      <CardHeader className="pb-2">
                        <div className="flex items-center">
                          <Avatar className="h-12 w-12 mr-4 bg-baseball-navy">
                            <span className="font-bold text-white">
                              {player.firstName?.[0]}{player.lastName?.[0]}
                            </span>
                          </Avatar>
                          <div>
                            <CardTitle className="text-lg">
                              {player.firstName} {player.lastName}
                            </CardTitle>
                            <CardDescription>
                              {player.position} · #{player.number || "N/A"}
                            </CardDescription>
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent>
                        {player.isPitcher ? (
                          <div className="grid grid-cols-2 gap-x-4 gap-y-2">
                            <div className="text-sm">ERA: <span className="font-medium">{player.era || "N/A"}</span></div>
                            <div className="text-sm">Wins: <span className="font-medium">{player.wins || "0"}</span></div>
                            <div className="text-sm">Losses: <span className="font-medium">{player.losses || "0"}</span></div>
                            <div className="text-sm">K: <span className="font-medium">{player.strikeouts || "0"}</span></div>
                          </div>
                        ) : (
                          <div className="grid grid-cols-2 gap-x-4 gap-y-2">
                            <div className="text-sm">AVG: <span className="font-medium">{player.battingAverage || ".000"}</span></div>
                            <div className="text-sm">HR: <span className="font-medium">{player.homeRuns || "0"}</span></div>
                            <div className="text-sm">RBI: <span className="font-medium">{player.rbi || "0"}</span></div>
                            <div className="text-sm">K: <span className="font-medium">{player.strikeouts || "0"}</span></div>
                          </div>
                        )}
                      </CardContent>
                    </Card>
                  ))
                )}
              </div>
            </TabsContent>
            
            <TabsContent value="pitchers" className="mt-4">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {isLoading ? (
                  Array(3).fill(0).map((_, i) => (
                    <Card key={i}>
                      <CardHeader className="pb-2">
                        <div className="flex items-center space-x-4">
                          <Skeleton className="h-12 w-12 rounded-full" />
                          <div className="space-y-2">
                            <Skeleton className="h-4 w-[200px]" />
                            <Skeleton className="h-4 w-[150px]" />
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <div className="grid grid-cols-2 gap-2">
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                        </div>
                      </CardContent>
                    </Card>
                  ))
                ) : error ? (
                  <div className="col-span-full text-center py-8">
                    <p className="text-red-500">Failed to load pitcher data</p>
                  </div>
                ) : (
                  players?.filter(player => 
                    ['P', 'SP', 'RP', 'CL'].includes(player.position)
                  ).map((player) => (
                    <Card key={player.id} className="hover:shadow-lg transition-shadow">
                      <CardHeader className="pb-2">
                        <div className="flex items-center">
                          <Avatar className="h-12 w-12 mr-4 bg-baseball-navy">
                            <span className="font-bold text-white">
                              {player.firstName?.[0]}{player.lastName?.[0]}
                            </span>
                          </Avatar>
                          <div>
                            <CardTitle className="text-lg">
                              {player.firstName} {player.lastName}
                            </CardTitle>
                            <CardDescription>
                              {player.position} · #{player.number || "N/A"}
                            </CardDescription>
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <div className="grid grid-cols-2 gap-x-4 gap-y-2">
                          <div className="text-sm">ERA: <span className="font-medium">{player.era || "N/A"}</span></div>
                          <div className="text-sm">Wins: <span className="font-medium">{player.wins || "0"}</span></div>
                          <div className="text-sm">Losses: <span className="font-medium">{player.losses || "0"}</span></div>
                          <div className="text-sm">K: <span className="font-medium">{player.strikeouts || "0"}</span></div>
                        </div>
                      </CardContent>
                    </Card>
                  ))
                )}
              </div>
            </TabsContent>
            
            <TabsContent value="batters" className="mt-4">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {isLoading ? (
                  Array(6).fill(0).map((_, i) => (
                    <Card key={i}>
                      <CardHeader className="pb-2">
                        <div className="flex items-center space-x-4">
                          <Skeleton className="h-12 w-12 rounded-full" />
                          <div className="space-y-2">
                            <Skeleton className="h-4 w-[200px]" />
                            <Skeleton className="h-4 w-[150px]" />
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <div className="grid grid-cols-2 gap-2">
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                          <Skeleton className="h-4 w-full" />
                        </div>
                      </CardContent>
                    </Card>
                  ))
                ) : error ? (
                  <div className="col-span-full text-center py-8">
                    <p className="text-red-500">Failed to load batter data</p>
                  </div>
                ) : (
                  players?.filter(player => 
                    !['P', 'SP', 'RP', 'CL'].includes(player.position)
                  ).map((player) => (
                    <Card key={player.id} className="hover:shadow-lg transition-shadow">
                      <CardHeader className="pb-2">
                        <div className="flex items-center">
                          <Avatar className="h-12 w-12 mr-4 bg-baseball-navy">
                            <span className="font-bold text-white">
                              {player.firstName?.[0]}{player.lastName?.[0]}
                            </span>
                          </Avatar>
                          <div>
                            <CardTitle className="text-lg">
                              {player.firstName} {player.lastName}
                            </CardTitle>
                            <CardDescription>
                              {player.position} · #{player.number || "N/A"}
                            </CardDescription>
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <div className="grid grid-cols-2 gap-x-4 gap-y-2">
                          <div className="text-sm">AVG: <span className="font-medium">{player.battingAverage || ".000"}</span></div>
                          <div className="text-sm">HR: <span className="font-medium">{player.homeRuns || "0"}</span></div>
                          <div className="text-sm">RBI: <span className="font-medium">{player.rbi || "0"}</span></div>
                          <div className="text-sm">K: <span className="font-medium">{player.strikeouts || "0"}</span></div>
                        </div>
                      </CardContent>
                    </Card>
                  ))
                )}
              </div>
            </TabsContent>
          </Tabs>
        </div>
      </div>
    </div>
  );
};

export default Players;
