import jwt, { SignOptions } from 'jsonwebtoken';
import bcrypt from 'bcrypt';
import { securityConfig } from './config';
import { createLogger } from '../utils/logger';

const logger = createLogger('security-service');

export interface User {
  id: string;
  username: string;
  password: string;
  roles: string[];
}

export class SecurityService {
  private readonly jwtSecret: string;
  private readonly saltRounds: number;

  constructor() {
    this.jwtSecret = securityConfig.auth.jwtSecret;
    this.saltRounds = securityConfig.auth.bcryptSaltRounds;
  }

  async hashPassword(password: string): Promise<string> {
    try {
      return await bcrypt.hash(password, this.saltRounds);
    } catch (error) {
      logger.error('Error hashing password:', error);
      throw new Error('Password hashing failed');
    }
  }

  async verifyPassword(password: string, hash: string): Promise<boolean> {
    try {
      return await bcrypt.compare(password, hash);
    } catch (error) {
      logger.error('Error verifying password:', error);
      throw new Error('Password verification failed');
    }
  }

  generateToken(user: Omit<User, 'password'>): string {
    try {
      const signOptions: SignOptions = {
        expiresIn: parseInt(securityConfig.auth.jwtExpiresIn, 10)
      };
      
      return jwt.sign(
        {
          id: user.id,
          username: user.username,
          roles: user.roles,
        },
        this.jwtSecret,
        signOptions
      );
    } catch (error) {
      logger.error('Error generating token:', error);
      throw new Error('Token generation failed');
    }
  }

  verifyToken(token: string): Omit<User, 'password'> {
    try {
      return jwt.verify(token, this.jwtSecret) as Omit<User, 'password'>;
    } catch (error) {
      logger.error('Error verifying token:', error);
      throw new Error('Token verification failed');
    }
  }

  hasRole(user: Omit<User, 'password'>, requiredRole: string): boolean {
    return user.roles.includes(requiredRole);
  }

  hasAnyRole(user: Omit<User, 'password'>, requiredRoles: string[]): boolean {
    return requiredRoles.some(role => this.hasRole(user, role));
  }

  hasAllRoles(user: Omit<User, 'password'>, requiredRoles: string[]): boolean {
    return requiredRoles.every(role => this.hasRole(user, role));
  }

  validatePassword(password: string): boolean {
    const { minLength, requireUppercase, requireLowercase, requireNumbers, requireSpecialChars } = 
      securityConfig.auth.passwordPolicy;

    if (password.length < minLength) return false;
    if (requireUppercase && !/[A-Z]/.test(password)) return false;
    if (requireLowercase && !/[a-z]/.test(password)) return false;
    if (requireNumbers && !/[0-9]/.test(password)) return false;
    if (requireSpecialChars && !/[!@#$%^&*(),.?":{}|<>]/.test(password)) return false;

    return true;
  }

  sanitizeUser(user: User): Omit<User, 'password'> {
    const { password, ...sanitizedUser } = user;
    return sanitizedUser;
  }
} 