
import React, { createContext, useState, useContext, useEffect, ReactNode } from 'react';
import { authService } from '../services/authService';
import { User, ApiResponse } from '../services/types';
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
    // On initial load, check if user is already authenticated
    const storedUser = localStorage.getItem('user_data');
    if (storedUser) {
      try {
        setUser(JSON.parse(storedUser));
      } catch (e) {
        console.error('Failed to parse stored user data:', e);
        localStorage.removeItem('user_data');
      }
    }
    
    checkAuth().finally(() => setLoading(false));
  }, []);

  const checkAuth = async (): Promise<boolean> => {
    const token = localStorage.getItem(config.auth.tokenKey);
    if (!token) {
      return false;
    }

    try {
      const response = await authService.getCurrentUser();
      const userData = response.data.data;
      
      if (userData) {
        setUser(userData);
        localStorage.setItem('user_data', JSON.stringify(userData));
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
      
      // Check if response contains expected data
      if (!response?.data?.data?.token) {
        throw new Error('Invalid response format from server');
      }
      
      const { token, user } = response.data.data;
      
      localStorage.setItem(config.auth.tokenKey, token);
      localStorage.setItem('user_data', JSON.stringify(user));
      setUser(user);
      
      toast({
        title: "Login successful",
        description: `Welcome back, ${user.username}!`,
      });
    } catch (error: any) {
      console.error('Login failed:', error);
      let errorMsg = 'Login failed. Please try again.';
      
      if (error?.response?.data?.error) {
        errorMsg = error.response.data.error;
      } else if (error?.message) {
        errorMsg = error.message;
      }
      
      toast({
        title: "Login failed",
        description: errorMsg,
        variant: "destructive",
      });
      
      throw new Error(errorMsg);
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
