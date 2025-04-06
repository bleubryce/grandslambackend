
import { io, Socket } from 'socket.io-client';
import { config } from '../config';
import { toast } from '@/components/ui/use-toast';

export interface GameUpdate {
  gameId: number;
  timestamp: string;
  type: 'score' | 'status' | 'play';
  data: any;
}

export interface StatsUpdate {
  playerId: number;
  gameId: number;
  timestamp: string;
  stats: {
    atBats?: number;
    hits?: number;
    runs?: number;
    rbis?: number;
    homeRuns?: number;
    strikeouts?: number;
    walks?: number;
    inningsPitched?: number;
    earnedRuns?: number;
  };
}

class WebSocketService {
  private socket: Socket | null = null;
  private static instance: WebSocketService;
  private listeners: Map<string, Set<Function>> = new Map();
  private gameSubscriptions: Set<number> = new Set();
  private playerSubscriptions: Set<number> = new Set();
  private connectionStatus: 'connected' | 'disconnected' | 'connecting' = 'disconnected';

  private constructor() {}

  public static getInstance(): WebSocketService {
    if (!WebSocketService.instance) {
      WebSocketService.instance = new WebSocketService();
    }
    return WebSocketService.instance;
  }

  public getConnectionStatus(): 'connected' | 'disconnected' | 'connecting' {
    return this.connectionStatus;
  }

  public connect(token: string): void {
    if (this.socket) {
      return;
    }

    this.connectionStatus = 'connecting';
    
    const url = config.api.backendUrl;
    
    try {
      this.socket = io(url, {
        auth: { token },
        transports: ['websocket'],
        reconnection: true,
        reconnectionAttempts: 5,
        reconnectionDelay: 1000
      });

      this.registerSocketEvents();
    } catch (error) {
      console.error('WebSocket connection error:', error);
      this.connectionStatus = 'disconnected';
      toast({
        variant: "destructive",
        title: "Connection Error",
        description: "Failed to connect to real-time updates server.",
      });
    }
  }

  private registerSocketEvents(): void {
    if (!this.socket) return;

    this.socket.on('connect', () => {
      console.log('WebSocket connected');
      this.connectionStatus = 'connected';
      toast({
        title: "Real-time Connected",
        description: "You are now receiving live updates.",
      });
      
      // Resubscribe to previously subscribed resources
      this.gameSubscriptions.forEach(gameId => {
        this.subscribeToGame(gameId);
      });
      
      this.playerSubscriptions.forEach(playerId => {
        this.subscribeToPlayer(playerId);
      });
    });

    this.socket.on('connect_error', (error) => {
      console.error('WebSocket connection error:', error);
      this.connectionStatus = 'disconnected';
    });

    this.socket.on('disconnect', (reason) => {
      console.log('WebSocket disconnected:', reason);
      this.connectionStatus = 'disconnected';
      if (reason === 'io server disconnect') {
        // the disconnection was initiated by the server, reconnect manually
        this.socket?.connect();
      }
    });
    
    this.socket.on('gameUpdate', (update: GameUpdate) => {
      this.notifyListeners('gameUpdate', update);
    });
    
    this.socket.on('statsUpdate', (update: StatsUpdate) => {
      this.notifyListeners('statsUpdate', update);
    });
  }

  public disconnect(): void {
    if (this.socket) {
      this.socket.disconnect();
      this.socket = null;
      this.connectionStatus = 'disconnected';
    }
  }
  
  private notifyListeners(event: string, data: any): void {
    const listeners = this.listeners.get(event);
    if (listeners) {
      listeners.forEach(callback => {
        try {
          callback(data);
        } catch (error) {
          console.error(`Error in ${event} listener:`, error);
        }
      });
    }
  }

  public onGameUpdate(callback: (update: GameUpdate) => void): () => void {
    return this.addListener('gameUpdate', callback);
  }

  public onStatsUpdate(callback: (update: StatsUpdate) => void): () => void {
    return this.addListener('statsUpdate', callback);
  }
  
  private addListener(event: string, callback: Function): () => void {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set());
    }
    
    this.listeners.get(event)?.add(callback);
    
    // Return unsubscribe function
    return () => {
      const listeners = this.listeners.get(event);
      if (listeners) {
        listeners.delete(callback);
      }
    };
  }

  public subscribeToGame(gameId: number): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    
    this.socket.emit('subscribeToGame', { gameId });
    this.gameSubscriptions.add(gameId);
  }

  public unsubscribeFromGame(gameId: number): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    
    this.socket.emit('unsubscribeFromGame', { gameId });
    this.gameSubscriptions.delete(gameId);
  }

  public subscribeToPlayer(playerId: number): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    
    this.socket.emit('subscribeToPlayer', { playerId });
    this.playerSubscriptions.add(playerId);
  }

  public unsubscribeFromPlayer(playerId: number): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    
    this.socket.emit('unsubscribeFromPlayer', { playerId });
    this.playerSubscriptions.delete(playerId);
  }
}

export const websocketService = WebSocketService.getInstance();
export default websocketService;
