import { AxiosResponse } from 'axios';
import { apiClient } from './apiClient';
import { config } from '@/config';
import { ApiResponse, LoginResponse, User } from './types';

class AuthService {
  private useMockResponse: boolean;

  constructor() {
    // Use mock responses in development if the backend is not available
    // Set to false by default to use the real API endpoints
    this.useMockResponse = config.app.environment === 'development' && import.meta.env.VITE_USE_MOCK_API === 'true';
  }

  async login(credentials: { username: string; password: string }): Promise<AxiosResponse<ApiResponse<LoginResponse>>> {
    try {
      if (this.useMockResponse) {
        return Promise.resolve(this.createMockLoginResponse(credentials));
      }
      
      const response = await apiClient.post<ApiResponse<LoginResponse>>(config.api.endpoints.auth.login, credentials);
      
      if (response.data.data?.token) {
        localStorage.setItem(config.auth.tokenKey, response.data.data.token);
      }
      
      return response;
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    }
  }

  async logout(): Promise<void> {
    localStorage.removeItem(config.auth.tokenKey);
    localStorage.removeItem('user_data');
  }

  async getCurrentUser(): Promise<AxiosResponse<ApiResponse<User>>> {
    const token = localStorage.getItem(config.auth.tokenKey);
    
    if (!token) {
      throw new Error('No token found');
    }

    if (this.useMockResponse) {
      return Promise.resolve(this.createMockUserResponse());
    }
    
    return apiClient.get<ApiResponse<User>>(
      config.api.endpoints.auth.me,
      {
        headers: {
          Authorization: `Bearer ${token}`
        }
      }
    );
  }

  async validateToken(): Promise<boolean> {
    try {
      const token = localStorage.getItem(config.auth.tokenKey);
      
      if (!token) {
        return false;
      }

      const response = await apiClient.get(config.api.endpoints.auth.validateToken, {
        headers: {
          Authorization: `Bearer ${token}`
        }
      });

      return response.data.valid === true;
    } catch (error) {
      console.error('Token validation error:', error);
      return false;
    }
  }

  // Helper method to create mock login responses for development
  private createMockLoginResponse(credentials: { username: string; password: string }): AxiosResponse<ApiResponse<LoginResponse>> {
    const mockUser = {
      id: '1',
      username: credentials.username,
      email: `${credentials.username}@example.com`,
      role: credentials.username.toLowerCase() === 'admin' ? 'admin' : 'user'
    };
    
    const mockResponse: ApiResponse<LoginResponse> = {
      status: 'success',
      message: 'Login successful',
      data: {
        token: 'mock-jwt-token',
        user: mockUser
      },
      timestamp: new Date().toISOString()
    };

    localStorage.setItem(config.auth.tokenKey, 'mock-jwt-token');
    localStorage.setItem('user_data', JSON.stringify(mockUser));

    return {
      data: mockResponse,
      status: 200,
      statusText: 'OK',
      headers: {},
      config: {} as any
    };
  }

  private createMockUserResponse(): AxiosResponse<ApiResponse<User>> {
    const mockUser = JSON.parse(localStorage.getItem('user_data') || '{}');
    
    return {
      data: {
        status: 'success',
        message: 'User retrieved successfully',
        data: mockUser,
        timestamp: new Date().toISOString()
      },
      status: 200,
      statusText: 'OK',
      headers: {},
      config: {} as any
    };
  }
}

export const authService = new AuthService();
