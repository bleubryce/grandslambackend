import { Request, Response, NextFunction } from 'express';
import { Redis } from 'ioredis';
import { SecurityService, User } from './service';
import { logger } from '../Logging/Logger';

// Extend Express Request type
declare global {
  namespace Express {
    interface Request {
      user?: Omit<User, 'password'>;
    }
  }
}

const redisConfig = {
  host: process.env.REDIS_HOST || 'localhost',
  port: parseInt(process.env.REDIS_PORT || '6379', 10),
  password: process.env.REDIS_PASSWORD || undefined
};

const redis = new Redis(redisConfig);

const securityService = new SecurityService(redis);

export const requireAuth = (req: Request, res: Response, next: NextFunction) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return res.status(401).json({ error: 'No token provided' });
    }

    const token = authHeader.split(' ')[1];
    const user = securityService.verifyToken(token);
    req.user = user;
    next();
  } catch (error) {
    logger.error('Authentication error:', error);
    return res.status(401).json({ error: 'Invalid token' });
  }
};

export const requireRole = (role: string) => {
  return (req: Request, res: Response, next: NextFunction) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' });
    }

    if (!securityService.hasRole(req.user, role)) {
      return res.status(403).json({ error: 'Insufficient permissions' });
    }

    next();
  };
};

export const requireAnyRole = (roles: string[]) => {
  return (req: Request, res: Response, next: NextFunction) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' });
    }

    if (!securityService.hasAnyRole(req.user, roles)) {
      return res.status(403).json({ error: 'Insufficient permissions' });
    }

    next();
  };
};

export const requireAllRoles = (roles: string[]) => {
  return (req: Request, res: Response, next: NextFunction) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' });
    }

    if (!securityService.hasAllRoles(req.user, roles)) {
      return res.status(403).json({ error: 'Insufficient permissions' });
    }

    next();
  };
};

export async function authMiddleware(req: Request, res: Response, next: NextFunction) {
  const ip = req.ip || req.connection.remoteAddress || 'unknown';

  try {
    if (await securityService.isRateLimited(ip)) {
      const remaining = await securityService.getRemainingAttempts(ip);
      return res.status(429).json({
        error: 'Too many requests',
        message: 'Please try again later',
        remainingAttempts: remaining
      });
    }

    await securityService.recordAttempt(ip);
    next();
  } catch (error) {
    logger.error('Auth middleware error:', error);
    next(error);
  }
} 