import { useState, useCallback, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import authService from '@/services/auth.service';

interface User {
  id: string;
  username: string;
  email: string;
  role: string;
}

interface UseAuthReturn {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (username: string, password: string) => Promise<void>;
  logout: () => void;
}

export const useAuth = (): UseAuthReturn => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const navigate = useNavigate();

  const checkAuth = useCallback(async () => {
    try {
      if (authService.isAuthenticated()) {
        // You might want to fetch user data here if needed
        const token = authService.getToken();
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1]));
          setUser({
            id: payload.sub,
            username: payload.username,
            email: payload.email,
            role: payload.role,
          });
        }
      } else {
        setUser(null);
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      setUser(null);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  const login = useCallback(async (username: string, password: string) => {
    try {
      setIsLoading(true);
      const response = await authService.login({ username, password });
      setUser(response.user);
      navigate('/dashboard');
    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, [navigate]);

  const logout = useCallback(() => {
    authService.logout();
    setUser(null);
    navigate('/login');
  }, [navigate]);

  return {
    user,
    isAuthenticated: !!user,
    isLoading,
    login,
    logout,
  };
}; 