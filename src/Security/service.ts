import jwt, { SignOptions } from 'jsonwebtoken';
import bcrypt from 'bcrypt';
import { securityConfig } from './config';
import { logger } from '../Logging/Logger';
import { PasswordValidator } from './PasswordValidator';
import { Redis } from 'ioredis';
import { RateLimiter } from './RateLimiter';

export interface User {
  id: string;
  username: string;
  password: string;
  roles: string[];
}

export class SecurityService {
  private readonly jwtSecret: string;
  private redis: Redis;
  private rateLimiter: RateLimiter;
  private passwordValidator: PasswordValidator;

  constructor(redis: Redis) {
    this.jwtSecret = process.env.JWT_SECRET || securityConfig.auth.jwtSecret;
    
    this.redis = redis;
    
    this.passwordValidator = new PasswordValidator({
      minLength: 8,
      requireUppercase: true,
      requireLowercase: true,
      requireNumbers: true,
      requireSpecialChars: true
    });

    this.rateLimiter = new RateLimiter({
      windowMs: 15 * 60 * 1000, // 15 minutes
      maxRequests: 100 // limit each IP to 100 requests per windowMs
    });
  }

  validatePassword(password: string): { isValid: boolean; errors: string[] } {
    return this.passwordValidator.validate(password);
  }

  getPasswordRequirements(): string[] {
    return this.passwordValidator.getRequirements();
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

  async isRateLimited(ip: string): Promise<boolean> {
    try {
      return await this.rateLimiter.isLimited(ip);
    } catch (error) {
      logger.error('Error checking rate limit:', error);
      return false;
    }
  }

  async recordAttempt(ip: string): Promise<void> {
    try {
      await this.rateLimiter.increment(ip);
    } catch (error) {
      logger.error('Error recording attempt:', error);
    }
  }

  async resetAttempts(ip: string): Promise<void> {
    try {
      await this.rateLimiter.reset(ip);
    } catch (error) {
      logger.error('Error resetting attempts:', error);
    }
  }

  async getRemainingAttempts(ip: string): Promise<number> {
    try {
      return await this.rateLimiter.getRemainingRequests(ip);
    } catch (error) {
      logger.error('Error getting remaining attempts:', error);
      return 0;
    }
  }

  async cleanup() {
    await this.redis.quit();
  }
} 