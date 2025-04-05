import { Request, Response, NextFunction } from 'express';
import { createLogger } from '../utils/logger';
import { Redis } from 'ioredis';

const logger = createLogger('rate-limiter');

interface RateLimitConfig {
  windowMs: number;  // Time window in milliseconds
  max: number;       // Max number of requests per window
  message?: string;  // Custom error message
  statusCode?: number; // Custom status code
  keyGenerator?: (req: Request) => string; // Custom key generator
}

export class RateLimiter {
  private redis: Redis;
  private config: RateLimitConfig;

  constructor(redis: Redis, config: RateLimitConfig) {
    this.redis = redis;
    this.config = {
      windowMs: config.windowMs || 15 * 60 * 1000, // Default: 15 minutes
      max: config.max || 100,                      // Default: 100 requests per window
      message: config.message || 'Too many requests, please try again later.',
      statusCode: config.statusCode || 429,
      keyGenerator: config.keyGenerator || ((req: Request) => {
        return `rate-limit:${req.ip}`;
      })
    };
  }

  middleware = async (req: Request, res: Response, next: NextFunction) => {
    try {
      const key = this.config.keyGenerator!(req);
      const current = await this.redis.incr(key);

      // Set expiry on first request
      if (current === 1) {
        await this.redis.pexpire(key, this.config.windowMs);
      }

      // Set rate limit headers
      res.setHeader('X-RateLimit-Limit', this.config.max);
      res.setHeader('X-RateLimit-Remaining', Math.max(0, this.config.max - current));

      if (current > this.config.max) {
        const ttl = await this.redis.pttl(key);
        res.setHeader('X-RateLimit-Reset', new Date(Date.now() + ttl).toISOString());
        return res.status(this.config.statusCode!).json({
          error: this.config.message
        });
      }

      next();
    } catch (error) {
      logger.error('Rate limiting error:', error);
      next(error);
    }
  };
} 