import { webSocketService } from '../../services/websocket';

describe('WebSocket Integration Tests', () => {
  const testToken = 'test-token';
  
  beforeAll(() => {
    webSocketService.connect('http://localhost:3000', testToken);
  });

  afterAll(() => {
    webSocketService.disconnect();
  });

  test('should receive game updates', (done) => {
    const gameId = 1;
    
    webSocketService.onGameUpdate((update) => {
      expect(update.gameId).toBe(gameId);
      expect(update.timestamp).toBeDefined();
      expect(update.type).toBeDefined();
      done();
    });

    webSocketService.subscribeToGame(gameId);
  });

  test('should receive player stats updates', (done) => {
    const playerId = 1;
    
    webSocketService.onStatsUpdate((update) => {
      expect(update.playerId).toBe(playerId);
      expect(update.timestamp).toBeDefined();
      expect(update.stats).toBeDefined();
      done();
    });

    webSocketService.subscribeToPlayer(playerId);
  });

  test('should handle connection errors', (done) => {
    const invalidUrl = 'http://invalid-url:3000';
    
    try {
      webSocketService.connect(invalidUrl, testToken);
    } catch (error: any) {
      expect(error).toBeDefined();
      done();
    }
  });
}); 