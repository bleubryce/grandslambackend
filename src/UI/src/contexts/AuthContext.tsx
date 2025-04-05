
import React, { createContext, useState, useContext, useEffect, ReactNode } from 'react';
import { authService, User, LoginResponse, ApiResponse } from '../services/api';
import { config } from '../config';
import { useToast } from '@/hooks/use-toast';

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (credentials: { username: string; password: string }) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<boolean>;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextType | null>(null);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const { toast } = useToast();

  useEffect(() => {
    checkAuth().finally(() => setLoading(false));
  }, []);

  const checkAuth = async (): Promise<boolean> => {
    const token = localStorage.getItem(config.auth.tokenKey);
    if (!token) {
      return false;
    }

    try {
      // We'd normally validate the token here via an API call
      // This is a placeholder until we have a real endpoint
      const userData = JSON.parse(localStorage.getItem('user_data') || 'null');
      if (userData) {
        setUser(userData);
        return true;
      }
      return false;
    } catch (error) {
      console.error('Token validation failed:', error);
      localStorage.removeItem(config.auth.tokenKey);
      localStorage.removeItem('user_data');
      return false;
    }
  };

  const login = async (credentials: { username: string; password: string }) => {
    setLoading(true);
    try {
      const response = await authService.login(credentials);
      const { token, user } = response.data.data;
      localStorage.setItem(config.auth.tokenKey, token);
      localStorage.setItem('user_data', JSON.stringify(user));
      setUser(user);
      toast({
        title: 'Welcome back!',
        description: `You've successfully logged in as ${user.username}`,
      });
    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    setLoading(true);
    try {
      await authService.logout();
    } catch (error) {
      console.error('Logout API call failed:', error);
      // Continue with logout process even if API call fails
    } finally {
      localStorage.removeItem(config.auth.tokenKey);
      localStorage.removeItem('user_data');
      setUser(null);
      setLoading(false);
      toast({
        title: 'Logged out',
        description: 'You have been successfully logged out',
      });
    }
  };

  return (
    <AuthContext.Provider value={{ 
      user, 
      loading, 
      login, 
      logout, 
      checkAuth,
      isAuthenticated: !!user 
    }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
