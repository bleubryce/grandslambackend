
// Re-export all services from a single file for backward compatibility
import { ApiResponse, ApiErrorResponse, User, LoginResponse, ApiConfig, PlayerStats, PitcherStats, Player } from './types';
import { apiClient } from './apiClient';
import { authService } from './authService';
import { playerService } from './playerService';

// Export all services
export { apiClient, authService, playerService };

// Export all types
export type {
  ApiResponse,
  ApiErrorResponse,
  User,
  LoginResponse,
  ApiConfig,
  PlayerStats,
  PitcherStats,
  Player
};
