import axios from 'axios';
import config from '@/config';

export interface LoginCredentials {
  username: string;
  password: string;
}

export interface User {
  id: string;
  username: string;
  roles: string[];
}

export interface AuthResponse {
  token: string;
  user: User;
}

class AuthService {
  private token: string | null = null;
  private user: User | null = null;

  constructor() {
    // Try to restore session from localStorage
    this.token = localStorage.getItem('token');
    const userStr = localStorage.getItem('user');
    if (userStr) {
      try {
        this.user = JSON.parse(userStr);
      } catch (error) {
        console.error('Failed to parse stored user:', error);
      }
    }
  }

  async login(credentials: LoginCredentials): Promise<User> {
    try {
      const response = await axios.post<AuthResponse>('/auth/login', credentials);
      this.token = response.data.token;
      this.user = response.data.user;
      
      // Store session
      localStorage.setItem('token', this.token);
      localStorage.setItem('user', JSON.stringify(this.user));

      // Set default Authorization header for future requests
      axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;

      return this.user;
    } catch (error) {
      if (axios.isAxiosError(error) && error.response) {
        throw new Error(error.response.data.error || 'Login failed');
      }
      throw error;
    }
  }

  async logout(): Promise<void> {
    try {
      if (this.token) {
        await axios.post('/auth/logout');
      }
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      this.token = null;
      this.user = null;
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      delete axios.defaults.headers.common['Authorization'];
    }
  }

  async register(credentials: LoginCredentials): Promise<User> {
    try {
      const response = await axios.post<AuthResponse>('/auth/register', credentials);
      this.token = response.data.token;
      this.user = response.data.user;
      
      localStorage.setItem('token', this.token);
      localStorage.setItem('user', JSON.stringify(this.user));
      
      axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;

      return this.user;
    } catch (error) {
      if (axios.isAxiosError(error) && error.response) {
        throw new Error(error.response.data.error || 'Registration failed');
      }
      throw error;
    }
  }

  getToken(): string | null {
    return this.token;
  }

  getUser(): User | null {
    return this.user;
  }

  isAuthenticated(): boolean {
    return !!this.token && !!this.user;
  }

  hasRole(role: string): boolean {
    return this.user?.roles.includes(role) || false;
  }

  hasAnyRole(roles: string[]): boolean {
    return this.user?.roles.some(role => roles.includes(role)) || false;
  }

  async validateToken(): Promise<boolean> {
    if (!this.token) return false;
    
    try {
      await axios.get('/auth/validate');
      return true;
    } catch (error) {
      this.logout();
      return false;
    }
  }

  setupAxiosInterceptors() {
    axios.interceptors.request.use(
      (config) => {
        if (this.token) {
          config.headers.Authorization = `Bearer ${this.token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    axios.interceptors.response.use(
      (response) => response,
      async (error) => {
        if (axios.isAxiosError(error) && error.response?.status === 401) {
          await this.logout();
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }
}

export const authService = new AuthService();
export default authService; 