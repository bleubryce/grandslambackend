import { Request, Response } from 'express';
import { SecurityService, User } from './service';
import { createLogger } from '../utils/logger';

const logger = createLogger('security-controller');
const securityService = new SecurityService();

export interface LoginRequest {
  username: string;
  password: string;
}

export interface RegisterRequest extends LoginRequest {
  roles?: string[];
}

export class SecurityController {
  async login(req: Request<{}, {}, LoginRequest>, res: Response) {
    try {
      const { username, password } = req.body;
      
      if (!username || !password) {
        return res.status(400).json({ 
          error: 'Username and password are required',
          success: false 
        });
      }

      // In a real application, you would fetch the user from a database
      // For demo purposes, accept any non-empty credentials
      const user: User = {
        id: '123',
        username,
        password: await securityService.hashPassword(password),
        roles: ['user']
      };

      const isValidPassword = await securityService.verifyPassword(password, user.password);
      if (!isValidPassword) {
        return res.status(401).json({ 
          error: 'Invalid credentials',
          success: false
        });
      }

      const token = securityService.generateToken(securityService.sanitizeUser(user));
      return res.status(200).json({ 
        token,
        user: securityService.sanitizeUser(user),
        success: true,
        message: 'Login successful'
      });
    } catch (error) {
      logger.error('Login error:', error);
      return res.status(500).json({ 
        error: 'Internal server error',
        success: false
      });
    }
  }

  async register(req: Request<{}, {}, RegisterRequest>, res: Response) {
    try {
      const { username, password, roles = ['user'] } = req.body;

      if (!securityService.validatePassword(password)) {
        return res.status(400).json({ 
          error: 'Password does not meet requirements',
          success: false
        });
      }

      // In a real application, you would check if the username is already taken
      // and save the user to a database
      const user: User = {
        id: '123',
        username,
        password: await securityService.hashPassword(password),
        roles
      };

      const token = securityService.generateToken(securityService.sanitizeUser(user));
      return res.status(201).json({ 
        token,
        user: securityService.sanitizeUser(user),
        success: true,
        message: 'Registration successful'
      });
    } catch (error) {
      logger.error('Registration error:', error);
      return res.status(500).json({ 
        error: 'Internal server error',
        success: false
      });
    }
  }

  async validateToken(req: Request, res: Response) {
    try {
      const authHeader = req.headers.authorization;
      if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return res.status(401).json({ 
          error: 'No token provided', 
          success: false
        });
      }

      const token = authHeader.split(' ')[1];
      const user = securityService.verifyToken(token);
      return res.status(200).json({ 
        user,
        success: true,
        message: 'Token valid'
      });
    } catch (error) {
      logger.error('Token validation error:', error);
      return res.status(401).json({ 
        error: 'Invalid token',
        success: false
      });
    }
  }

  async getCurrentUser(req: Request, res: Response) {
    try {
      if (!req.user) {
        return res.status(401).json({ 
          error: 'Not authenticated', 
          success: false
        });
      }
      
      return res.status(200).json({ 
        user: req.user,
        success: true,
        message: 'User retrieved successfully'
      });
    } catch (error) {
      logger.error('Get current user error:', error);
      return res.status(500).json({ 
        error: 'Internal server error',
        success: false
      });
    }
  }
}
