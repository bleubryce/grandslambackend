
import React from "react";
import { Player } from "@/services/types";
import PlayerGrid from "./PlayerGrid";

interface TabContentProps {
  players: Player[] | undefined;
  isLoading: boolean;
  error: Error | null;
  onPlayerClick: (playerId: number) => void;
  skeletonCount?: number;
}

const TabContent: React.FC<TabContentProps> = ({
  players,
  isLoading,
  error,
  onPlayerClick,
  skeletonCount
}) => {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <PlayerGrid
        players={players}
        isLoading={isLoading}
        error={error}
        onPlayerClick={onPlayerClick}
        skeletonCount={skeletonCount}
      />
    </div>
  );
};

export default TabContent;
