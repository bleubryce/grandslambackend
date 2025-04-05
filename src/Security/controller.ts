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

      // In a real application, you would fetch the user from a database
      const user: User = {
        id: '123',
        username,
        password: await securityService.hashPassword(password),
        roles: ['user']
      };

      const isValidPassword = await securityService.verifyPassword(password, user.password);
      if (!isValidPassword) {
        return res.status(401).json({ error: 'Invalid credentials' });
      }

      const token = securityService.generateToken(securityService.sanitizeUser(user));
      return res.json({ token });
    } catch (error) {
      logger.error('Login error:', error);
      return res.status(500).json({ error: 'Internal server error' });
    }
  }

  async register(req: Request<{}, {}, RegisterRequest>, res: Response) {
    try {
      const { username, password, roles = ['user'] } = req.body;

      if (!securityService.validatePassword(password)) {
        return res.status(400).json({ error: 'Password does not meet requirements' });
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
      return res.status(201).json({ token });
    } catch (error) {
      logger.error('Registration error:', error);
      return res.status(500).json({ error: 'Internal server error' });
    }
  }

  async validateToken(req: Request, res: Response) {
    try {
      const authHeader = req.headers.authorization;
      if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return res.status(401).json({ error: 'No token provided' });
      }

      const token = authHeader.split(' ')[1];
      const user = securityService.verifyToken(token);
      return res.json({ user });
    } catch (error) {
      logger.error('Token validation error:', error);
      return res.status(401).json({ error: 'Invalid token' });
    }
  }
} 