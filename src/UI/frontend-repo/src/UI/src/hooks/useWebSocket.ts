
import { useState, useEffect, useCallback } from 'react';
import { websocketService, GameUpdate, StatsUpdate } from '../services/websocketService';
import { useAuth } from '@/contexts/AuthContext';
import { toast } from '@/components/ui/use-toast';

export const useWebSocket = () => {
  const { user } = useAuth();
  const [isConnected, setIsConnected] = useState(false);
  const [isConnecting, setIsConnecting] = useState(false);
  const [reconnectAttempts, setReconnectAttempts] = useState(0);
  const maxReconnectAttempts = 5;
  
  // Connect to WebSocket when user is authenticated
  useEffect(() => {
    if (user) {
      const token = localStorage.getItem('jwt_token');
      if (token) {
        setIsConnecting(true);
        websocketService.connect(token)
          .then(() => {
            setIsConnected(true);
            setIsConnecting(false);
            setReconnectAttempts(0);
          })
          .catch(error => {
            console.error('WebSocket connection failed:', error);
            setIsConnecting(false);
            
            // Only attempt to reconnect if we haven't exceeded the maximum attempts
            if (reconnectAttempts < maxReconnectAttempts) {
              setTimeout(() => {
                setReconnectAttempts(prev => prev + 1);
              }, 2000 * Math.pow(2, reconnectAttempts)); // Exponential backoff
            } else {
              toast({
                title: "Connection Error",
                description: "Failed to connect to real-time updates. Please refresh the page.",
                variant: "destructive"
              });
            }
          });
        
        const checkConnectionStatus = () => {
          setIsConnected(websocketService.getConnectionStatus() === 'connected');
        };
        
        // Check initial connection status
        checkConnectionStatus();
        
        // Set up interval to check connection status
        const interval = setInterval(checkConnectionStatus, 5000);
        
        return () => {
          clearInterval(interval);
          websocketService.disconnect();
        };
      }
    }
  }, [user, reconnectAttempts]);
  
  // Subscribe to game updates
  const subscribeToGame = useCallback((gameId: number) => {
    try {
      websocketService.subscribeToGame(gameId);
      toast({
        title: "Subscribed",
        description: `Now receiving real-time updates for game #${gameId}`,
      });
    } catch (error) {
      console.error('Failed to subscribe to game:', error);
      toast({
        title: "Subscription Error",
        description: "Failed to subscribe to game updates",
        variant: "destructive"
      });
    }
  }, []);
  
  // Unsubscribe from game updates
  const unsubscribeFromGame = useCallback((gameId: number) => {
    try {
      websocketService.unsubscribeFromGame(gameId);
    } catch (error) {
      console.error('Failed to unsubscribe from game:', error);
    }
  }, []);
  
  // Subscribe to player updates
  const subscribeToPlayer = useCallback((playerId: number) => {
    try {
      websocketService.subscribeToPlayer(playerId);
      toast({
        title: "Subscribed",
        description: `Now receiving real-time updates for player #${playerId}`,
      });
    } catch (error) {
      console.error('Failed to subscribe to player:', error);
      toast({
        title: "Subscription Error",
        description: "Failed to subscribe to player updates",
        variant: "destructive"
      });
    }
  }, []);
  
  // Unsubscribe from player updates
  const unsubscribeFromPlayer = useCallback((playerId: number) => {
    try {
      websocketService.unsubscribeFromPlayer(playerId);
    } catch (error) {
      console.error('Failed to unsubscribe from player:', error);
    }
  }, []);
  
  // Register game update listener
  const onGameUpdate = useCallback((callback: (update: GameUpdate) => void) => {
    return websocketService.onGameUpdate(callback);
  }, []);
  
  // Register stats update listener
  const onStatsUpdate = useCallback((callback: (update: StatsUpdate) => void) => {
    return websocketService.onStatsUpdate(callback);
  }, []);
  
  return {
    isConnected,
    isConnecting,
    reconnectAttempts,
    maxReconnectAttempts,
    subscribeToGame,
    unsubscribeFromGame,
    subscribeToPlayer,
    unsubscribeFromPlayer,
    onGameUpdate,
    onStatsUpdate
  };
};

export default useWebSocket;
