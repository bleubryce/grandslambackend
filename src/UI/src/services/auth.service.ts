import axios, { AxiosInstance } from 'axios';
import config from '@/config';

interface LoginCredentials {
  username: string;
  password: string;
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

  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    try {
      const response = await this.api.post<AuthResponse>('/api/auth/login', credentials);
      this.setToken(response.data.token);
      return response.data;
    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    }
  }

  logout(): void {
    localStorage.removeItem(AuthService.TOKEN_KEY);
    // Clear any user data from state management if you're using any
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

  // Method to get authenticated API instance
  getAuthenticatedApi(): AxiosInstance {
    return this.api;
  }
}

export const authService = new AuthService();
export default authService; 