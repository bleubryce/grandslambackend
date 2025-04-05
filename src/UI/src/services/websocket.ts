import { io, Socket } from 'socket.io-client';
import { GameUpdate, StatsUpdate } from '../types/api';

class WebSocketService {
  private socket: Socket | null = null;
  private static instance: WebSocketService;

  private constructor() {}

  public static getInstance(): WebSocketService {
    if (!WebSocketService.instance) {
      WebSocketService.instance = new WebSocketService();
    }
    return WebSocketService.instance;
  }

  public connect(url: string, token: string): void {
    this.socket = io(url, {
      auth: {
        token
      },
      transports: ['websocket'],
      reconnection: true,
      reconnectionAttempts: 5,
      reconnectionDelay: 1000
    });

    this.socket.on('connect', () => {
      console.log('WebSocket connected');
    });

    this.socket.on('connect_error', (error) => {
      console.error('WebSocket connection error:', error);
    });

    this.socket.on('disconnect', (reason) => {
      console.log('WebSocket disconnected:', reason);
    });
  }

  public disconnect(): void {
    if (this.socket) {
      this.socket.disconnect();
      this.socket = null;
    }
  }

  public onGameUpdate(callback: (update: GameUpdate) => void): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    this.socket.on('gameUpdate', callback);
  }

  public onStatsUpdate(callback: (update: StatsUpdate) => void): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    this.socket.on('statsUpdate', callback);
  }

  public subscribeToGame(gameId: number): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    this.socket.emit('subscribeToGame', { gameId });
  }

  public unsubscribeFromGame(gameId: number): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    this.socket.emit('unsubscribeFromGame', { gameId });
  }

  public subscribeToPlayer(playerId: number): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    this.socket.emit('subscribeToPlayer', { playerId });
  }

  public unsubscribeFromPlayer(playerId: number): void {
    if (!this.socket) {
      throw new Error('WebSocket not connected');
    }
    this.socket.emit('unsubscribeFromPlayer', { playerId });
  }
}

export const webSocketService = WebSocketService.getInstance();
export default webSocketService; 