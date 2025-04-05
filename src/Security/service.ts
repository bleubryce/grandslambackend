import jwt, { SignOptions } from 'jsonwebtoken';
import bcrypt from 'bcrypt';
import { securityConfig } from './config';
import { createLogger } from '../utils/logger';
import { PasswordValidator, PasswordPolicy } from './PasswordValidator';
import Redis from 'ioredis';
import { RateLimiter } from './RateLimiter';

const logger = createLogger('security-service');

export interface User {
  id: string;
  username: string;
  password: string;
  roles: string[];
}

export class SecurityService {
  private readonly jwtSecret: string;
  private readonly passwordValidator: PasswordValidator;
  private readonly redis: Redis;
  private readonly rateLimiter: RateLimiter;

  constructor() {
    this.jwtSecret = process.env.JWT_SECRET || securityConfig.auth.jwtSecret;
    
    // Initialize password validator with policy
    const passwordPolicy: PasswordPolicy = {
      minLength: 12,
      requireUppercase: true,
      requireLowercase: true,
      requireNumbers: true,
      requireSpecialChars: true,
      maxLength: 128,
      preventCommonPasswords: true,
      preventPersonalInfo: true
    };
    this.passwordValidator = new PasswordValidator(passwordPolicy);

    // Initialize Redis client
    this.redis = new Redis({
      host: process.env.REDIS_HOST || 'localhost',
      port: parseInt(process.env.REDIS_PORT || '6379'),
      password: process.env.REDIS_PASSWORD,
    });

    // Initialize rate limiter
    this.rateLimiter = new RateLimiter(this.redis, {
      windowMs: 15 * 60 * 1000, // 15 minutes
      max: 100 // limit each IP to 100 requests per windowMs
    });
  }

  async validatePassword(password: string, personalInfo?: { [key: string]: string }): Promise<{ isValid: boolean; errors: string[] }> {
    return this.passwordValidator.validate(password, personalInfo);
  }

  getPasswordRequirements(): string[] {
    return this.passwordValidator.getPasswordRequirements();
  }

  async hashPassword(password: string): Promise<string> {
    const saltRounds = 12;
    return bcrypt.hash(password, saltRounds);
  }

  async verifyPassword(plainPassword: string, hashedPassword: string): Promise<boolean> {
    return bcrypt.compare(plainPassword, hashedPassword);
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

  sanitizeUser(user: User): Omit<User, 'password'> {
    const { password, ...sanitizedUser } = user;
    return sanitizedUser;
  }

  getRateLimiterMiddleware() {
    return this.rateLimiter.middleware;
  }

  async cleanup() {
    await this.redis.quit();
  }
} 