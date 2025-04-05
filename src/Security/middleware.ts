import { Request, Response, NextFunction } from 'express';
import rateLimit from 'express-rate-limit';
import helmet from 'helmet';
import cors from 'cors';
import { expressCspHeader } from 'express-csp-header';
import xss from 'xss-clean';
import hpp from 'hpp';
import { securityConfig } from './config';
import { createLogger } from '../utils/logger';

const logger = createLogger('security');

// Define the User interface
interface User {
  id: string;
  username: string;
  roles: string[];
}

// Extend the Express Request type to include the user property
declare global {
  namespace Express {
    interface Request {
      user?: User;
    }
  }
}

// Rate limiting middleware
export const rateLimiter = rateLimit(securityConfig.rateLimit);

// CORS middleware
export const corsMiddleware = cors(securityConfig.cors);

// Content Security Policy middleware
export const cspMiddleware = expressCspHeader({
  policies: {
    'default-src': securityConfig.csp.directives.defaultSrc,
    'script-src': securityConfig.csp.directives.scriptSrc,
    'style-src': securityConfig.csp.directives.styleSrc,
    'font-src': securityConfig.csp.directives.fontSrc,
    'img-src': securityConfig.csp.directives.imgSrc,
    'connect-src': securityConfig.csp.directives.connectSrc,
  }
});

// Security headers middleware using helmet
export const securityHeaders = helmet({
  hsts: securityConfig.headers.hsts,
  noSniff: securityConfig.headers.noSniff,
  xssFilter: securityConfig.headers.xssFilter,
  frameguard: securityConfig.headers.frameguard,
  referrerPolicy: {
    policy: securityConfig.headers.referrerPolicy,
  },
});

// Input sanitization middleware
export const sanitizeInput = (req: Request, _res: Response, next: NextFunction) => {
  if (securityConfig.validation.sanitization.enabled) {
    const sanitize = (obj: any): any => {
      if (!obj) return obj;

      if (typeof obj === 'string') {
        let result = obj;
        if (securityConfig.validation.sanitization.options.stripTags) {
          result = result.replace(/<[^>]*>/g, '');
        }
        if (securityConfig.validation.sanitization.options.stripSpecialChars) {
          result = result.replace(/[^\w\s-]/g, '');
        }
        if (securityConfig.validation.sanitization.options.escapeHTML) {
          result = result
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        }
        return result;
      }

      if (Array.isArray(obj)) {
        return obj.map(item => sanitize(item));
      }

      if (typeof obj === 'object') {
        const result: any = {};
        for (const key in obj) {
          if (Object.prototype.hasOwnProperty.call(obj, key)) {
            result[key] = sanitize(obj[key]);
          }
        }
        return result;
      }

      return obj;
    };

    req.body = sanitize(req.body);
    req.query = sanitize(req.query);
    req.params = sanitize(req.params);
  }
  next();
};

// Request size limit middleware
export const requestSizeLimit = (req: Request, res: Response, next: NextFunction) => {
  const contentLength = parseInt(req.headers['content-length'] || '0', 10);
  const maxSize = parseInt(securityConfig.validation.maxRequestSize, 10);

  if (contentLength > maxSize) {
    logger.warn(`Request size ${contentLength} exceeds limit ${maxSize}`);
    return res.status(413).json({
      error: 'Request entity too large',
      maxSize: securityConfig.validation.maxRequestSize,
    });
  }
  next();
};

// Security logging middleware
export const securityLogger = (req: Request, res: Response, next: NextFunction) => {
  if (securityConfig.logging.securityEvents) {
    const logData = {
      timestamp: new Date().toISOString(),
      method: req.method,
      url: req.url,
      ip: req.ip,
      userAgent: req.headers['user-agent'],
      referrer: req.headers.referer || req.headers.referrer,
    };
    logger.info('Security event', logData);
  }
  next();
};

// Audit trail middleware
export const auditTrail = (req: Request, res: Response, next: NextFunction) => {
  if (securityConfig.logging.auditTrail && req.user) {
    const logData = {
      timestamp: new Date().toISOString(),
      user: req.user,
      action: `${req.method} ${req.url}`,
      ip: req.ip,
      userAgent: req.headers['user-agent'],
    };
    logger.info('Audit trail', logData);
  }
  next();
};

// XSS prevention middleware
export const xssMiddleware = xss();

// HTTP Parameter Pollution prevention middleware
export const hppMiddleware = hpp();

// Combine all security middleware
export const securityMiddleware = [
  securityLogger,
  rateLimiter,
  corsMiddleware,
  securityHeaders,
  cspMiddleware,
  xssMiddleware,
  hppMiddleware,
  sanitizeInput,
  requestSizeLimit,
  auditTrail,
]; 