import axios, { AxiosInstance } from 'axios';
import config from '@/config';

interface LoginCredentials {
  username: string;
  password: string;
}

interface RegisterCredentials {
  username: string;
  email: string;
  password: string;
  confirmPassword: string;
}

interface AuthResponse {
  token: string;
  user: {
    id: string;
    username: string;
    email: string;
    role: string;
  };
}

class AuthService {
  private api: AxiosInstance;
  private static TOKEN_KEY = 'auth_token';
  private static REMEMBER_ME_KEY = 'remember_me';

  constructor() {
    this.api = axios.create({
      baseURL: config.api.baseUrl,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Add token to requests if it exists
    this.api.interceptors.request.use((config) => {
      const token = this.getToken();
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    });

    // Handle token expiration
    this.api.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          this.logout();
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  async register(credentials: RegisterCredentials): Promise<void> {
    try {
      await this.api.post('/api/auth/register', credentials);
    } catch (error) {
      console.error('Registration failed:', error);
      throw error;
    }
  }

  async verifyEmail(token: string): Promise<void> {
    try {
      await this.api.post('/api/auth/verify-email', { token });
    } catch (error) {
      console.error('Email verification failed:', error);
      throw error;
    }
  }

  async resendVerificationEmail(email: string): Promise<void> {
    try {
      await this.api.post('/api/auth/resend-verification', { email });
    } catch (error) {
      console.error('Resend verification failed:', error);
      throw error;
    }
  }

  async login(credentials: LoginCredentials, rememberMe: boolean = false): Promise<AuthResponse> {
    try {
      const response = await this.api.post<AuthResponse>('/api/auth/login', credentials);
      this.setToken(response.data.token);
      if (rememberMe) {
        localStorage.setItem(AuthService.REMEMBER_ME_KEY, 'true');
      }
      return response.data;
    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    }
  }

  async requestPasswordReset(email: string): Promise<void> {
    try {
      await this.api.post('/api/auth/request-password-reset', { email });
    } catch (error) {
      console.error('Password reset request failed:', error);
      throw error;
    }
  }

  async resetPassword(token: string, newPassword: string): Promise<void> {
    try {
      await this.api.post('/api/auth/reset-password', { token, newPassword });
    } catch (error) {
      console.error('Password reset failed:', error);
      throw error;
    }
  }

  async updateProfile(userId: string, data: any): Promise<void> {
    try {
      await this.api.put(`/api/users/${userId}/profile`, data);
    } catch (error) {
      console.error('Profile update failed:', error);
      throw error;
    }
  }

  logout(): void {
    localStorage.removeItem(AuthService.TOKEN_KEY);
    localStorage.removeItem(AuthService.REMEMBER_ME_KEY);
  }

  getToken(): string | null {
    return localStorage.getItem(AuthService.TOKEN_KEY);
  }

  private setToken(token: string): void {
    localStorage.setItem(AuthService.TOKEN_KEY, token);
  }

  isAuthenticated(): boolean {
    const token = this.getToken();
    if (!token) return false;

    try {
      // Basic token expiration check (assuming JWT)
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.exp * 1000 > Date.now();
    } catch {
      return false;
    }
  }

  isRememberMeEnabled(): boolean {
    return localStorage.getItem(AuthService.REMEMBER_ME_KEY) === 'true';
  }

  // Method to get authenticated API instance
  getAuthenticatedApi(): AxiosInstance {
    return this.api;
  }
}

export const authService = new AuthService();
export default authService; 