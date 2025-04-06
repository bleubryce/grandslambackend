
import React, { useState } from "react";
import { usePlayers } from "@/hooks/usePlayers";
import Navbar from "@/components/Navbar";
import Sidebar from "@/components/Sidebar";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useNavigate } from "react-router-dom";
import TabContent from "@/components/Players/TabContent";
import ModelAnalysisPanel from "@/components/Analysis/ModelAnalysisPanel";
import { config } from "@/config";

const Players = () => {
  const { players, isLoading, error } = usePlayers();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState("all");
  const isModelEnabled = config.app.isModelEnabled;

  const handlePlayerClick = (playerId: number) => {
    navigate(`/player/${playerId}`);
  };

  // Filter players based on active tab
  const filteredPlayers = React.useMemo(() => {
    if (!players) return [];
    
    switch (activeTab) {
      case "pitchers":
        return players.filter(player => ['P', 'SP', 'RP', 'CL'].includes(player.position));
      case "batters":
        return players.filter(player => !['P', 'SP', 'RP', 'CL'].includes(player.position));
      default:
        return players;
    }
  }, [players, activeTab]);

  return (
    <div className="min-h-screen bg-gray-100">
      <Navbar />
      <Sidebar />
      
      <div className="pt-16 pl-64">
        <div className="p-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-6">Player Analysis</h1>
          
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div className="lg:col-span-2">
              <Tabs defaultValue="all" className="w-full" onValueChange={setActiveTab}>
                <TabsList>
                  <TabsTrigger value="all">All Players</TabsTrigger>
                  <TabsTrigger value="pitchers">Pitchers</TabsTrigger>
                  <TabsTrigger value="batters">Batters</TabsTrigger>
                </TabsList>
                
                <TabsContent value="all" className="mt-4">
                  <TabContent 
                    players={filteredPlayers}
                    isLoading={isLoading}
                    error={error}
                    onPlayerClick={handlePlayerClick}
                    skeletonCount={9}
                  />
                </TabsContent>
                
                <TabsContent value="pitchers" className="mt-4">
                  <TabContent 
                    players={filteredPlayers}
                    isLoading={isLoading}
                    error={error}
                    onPlayerClick={handlePlayerClick}
                    skeletonCount={3}
                  />
                </TabsContent>
                
                <TabsContent value="batters" className="mt-4">
                  <TabContent 
                    players={filteredPlayers}
                    isLoading={isLoading}
                    error={error}
                    onPlayerClick={handlePlayerClick}
                    skeletonCount={6}
                  />
                </TabsContent>
              </Tabs>
            </div>
            
            {isModelEnabled && (
              <div>
                <ModelAnalysisPanel />
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Players;
