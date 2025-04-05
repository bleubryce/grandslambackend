import { baseballApi } from '../../services/api';

describe('Baseball API Integration Tests', () => {
  // Test credentials (use test account)
  const testCredentials = {
    username: 'testuser',
    password: 'testpass123'
  };

  let authToken: string;

  // Test authentication
  test('should authenticate successfully', async () => {
    try {
      const response = await baseballApi.login(testCredentials.username, testCredentials.password);
      expect(response.token).toBeDefined();
      expect(response.user).toBeDefined();
      authToken = response.token;
    } catch (error) {
      console.error('Authentication failed:', error);
      throw error;
    }
  });

  // Test team analysis endpoint
  test('should fetch team analysis data', async () => {
    try {
      const teamId = 1; // Use a known test team ID
      const response = await baseballApi.getTeamAnalysis(teamId);
      expect(response.teamId).toBe(teamId);
      expect(response.results).toBeDefined();
      expect(response.results.performanceMetrics).toBeDefined();
    } catch (error) {
      console.error('Team analysis failed:', error);
      throw error;
    }
  });

  // Test player analysis endpoint
  test('should fetch player analysis data', async () => {
    try {
      const playerId = 1; // Use a known test player ID
      const response = await baseballApi.getPlayerAnalysis(playerId);
      expect(response.playerId).toBe(playerId);
      expect(response.results).toBeDefined();
      expect(response.results.batting).toBeDefined();
      expect(response.results.pitching).toBeDefined();
    } catch (error) {
      console.error('Player analysis failed:', error);
      throw error;
    }
  });

  // Test game analysis endpoint
  test('should fetch game analysis data', async () => {
    try {
      const gameId = 1; // Use a known test game ID
      const response = await baseballApi.getGameAnalysis(gameId);
      expect(response.gameId).toBe(gameId);
      expect(response.results).toBeDefined();
      expect(response.results.gameMetrics).toBeDefined();
    } catch (error) {
      console.error('Game analysis failed:', error);
      throw error;
    }
  });

  // Test model analysis endpoint
  test('should perform model analysis', async () => {
    try {
      const request = {
        type: 'team' as const,
        id: 1
      };
      const response = await baseballApi.analyze(request);
      expect(response.modelVersion).toBeDefined();
      expect(response.timestamp).toBeDefined();
      expect(response.results).toBeDefined();
    } catch (error) {
      console.error('Model analysis failed:', error);
      throw error;
    }
  });

  // Test error handling
  test('should handle invalid authentication', async () => {
    try {
      await baseballApi.login('invalid', 'credentials');
      fail('Should have thrown an error');
    } catch (error: any) {
      expect(error.status).toBe(401);
    }
  });
}); 