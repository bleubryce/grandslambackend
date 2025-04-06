import { authService } from '../../services/authService';
import { playerService } from '../../services/playerService';

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
      const response = await authService.login(testCredentials.username, testCredentials.password);
      expect(response.data.data.token).toBeDefined();
      expect(response.data.data.user).toBeDefined();
      authToken = response.data.data.token;
    } catch (error) {
      console.error('Authentication failed:', error);
      throw error;
    }
  });

  // Test team analysis endpoint
  test('should fetch players data', async () => {
    try {
      const response = await playerService.getPlayers();
      expect(response.data.data).toBeDefined();
    } catch (error) {
      console.error('Player analysis failed:', error);
      throw error;
    }
  });

  // Test player analysis endpoint
  test('should fetch player analysis data', async () => {
    try {
      const playerId = 1; // Use a known test player ID
      const response = await playerService.getPlayer(playerId);
      expect(response.data.data.id).toBe(playerId);
    } catch (error) {
      console.error('Player analysis failed:', error);
      throw error;
    }
  });
});
