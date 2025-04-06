
import React from "react";
import { Player } from "@/services/types";
import PlayerCard from "./PlayerCard";
import PlayersPageSkeleton from "./PlayersPageSkeleton";

interface PlayerGridProps {
  players: Player[] | undefined;
  isLoading: boolean;
  error: Error | null;
  onPlayerClick: (playerId: number) => void;
  skeletonCount?: number;
}

const PlayerGrid: React.FC<PlayerGridProps> = ({
  players,
  isLoading,
  error,
  onPlayerClick,
  skeletonCount = 9
}) => {
  if (isLoading) {
    return <PlayersPageSkeleton count={skeletonCount} />;
  }

  if (error) {
    return (
      <div className="col-span-full text-center py-8">
        <p className="text-red-500">Failed to load player data: {error.message}</p>
      </div>
    );
  }

  if (!players || players.length === 0) {
    return (
      <div className="col-span-full text-center py-8">
        <p className="text-gray-500">No players found</p>
      </div>
    );
  }

  return (
    <>
      {players.map((player) => (
        <PlayerCard 
          key={player.id}
          player={player}
          onClick={onPlayerClick}
        />
      ))}
    </>
  );
};

export default PlayerGrid;
