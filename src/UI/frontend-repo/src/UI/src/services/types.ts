
import { AxiosResponse } from 'axios';

// API response types
export interface ApiResponse<T = any> {
  status: string;
  message: string;
  data: T;
  errors?: Record<string, string[]>;
  timestamp: string;
}

export interface ApiErrorResponse {
  status: string;
  message: string;
  errors?: Record<string, string[]>;
  timestamp: string;
}

export interface User {
  id: string;
  username: string;
  email: string;
  role: string;
}

export interface LoginResponse {
  token: string;
  user: User;
}

export interface ApiConfig {
  baseUrl: string;
  timeout?: number;
  headers?: Record<string, string[]>;
}

export interface PlayerStats {
  battingAverage?: string;
  homeRuns?: number;
  rbi?: number;
  onBasePercentage?: string;
  sluggingPercentage?: string;
  hits?: number;
  atBats?: number;
  strikeouts?: number;
  walks?: number;
}

export interface PitcherStats {
  era?: string;
  wins?: number;
  losses?: number;
  inningsPitched?: number;
  strikeouts?: number;
  walks?: number;
  whip?: string;
}

export interface Player {
  id: number;
  firstName: string;
  lastName: string;
  position: string;
  teamId: number;
  number?: number;
  isPitcher?: boolean;
  battingAverage?: string;
  homeRuns?: number;
  rbi?: number;
  era?: string;
  wins?: number;
  losses?: number;
  strikeouts?: number;
  photoUrl?: string;
  stats?: any;
}
