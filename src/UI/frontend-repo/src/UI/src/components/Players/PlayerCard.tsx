
import React from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Player } from "@/services/types";

interface PlayerCardProps {
  player: Player;
  onClick: (playerId: number) => void;
}

const PlayerCard: React.FC<PlayerCardProps> = ({ player, onClick }) => {
  const isPitcher = ['P', 'SP', 'RP', 'CL'].includes(player.position);
  
  return (
    <Card 
      className="hover:shadow-lg transition-shadow cursor-pointer"
      onClick={() => onClick(player.id)}
    >
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
        {isPitcher ? (
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
        <Button className="w-full mt-4 bg-baseball-navy hover:bg-baseball-navy/90 text-white" size="sm">
          View Details
        </Button>
      </CardContent>
    </Card>
  );
};

export default PlayerCard;
