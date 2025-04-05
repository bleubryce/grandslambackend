import { Request, Response, NextFunction } from 'express';
import { performance } from 'perf_hooks';
import { MonitoringService } from './service';
import { createLogger } from '../utils/logger';

const logger = createLogger('monitoring-middleware');
const monitoringService = MonitoringService.getInstance();

export interface RequestMetrics {
  method: string;
  path: string;
  statusCode: number;
  responseTime: number;
  timestamp: number;
  userAgent?: string;
  ip?: string;
}

export const requestMonitor = (req: Request, res: Response, next: NextFunction) => {
  const startTime = performance.now();
  const originalEnd = res.end;
  const originalJson = res.json;

  // Track response time and status code
  res.end = function(chunk?: any, encoding?: any, callback?: any): Response {
    const responseTime = performance.now() - startTime;
    monitoringService.trackRequest(startTime, res.statusCode);

    const metrics: RequestMetrics = {
      method: req.method,
      path: req.path,
      statusCode: res.statusCode,
      responseTime,
      timestamp: Date.now(),
      userAgent: req.headers['user-agent'],
      ip: req.ip,
    };

    logger.info('Request completed', { metrics });

    return originalEnd.call(this, chunk, encoding, callback);
  };

  // Track JSON responses
  res.json = function(body: any): Response {
    const responseTime = performance.now() - startTime;
    monitoringService.trackRequest(startTime, res.statusCode);

    const metrics: RequestMetrics = {
      method: req.method,
      path: req.path,
      statusCode: res.statusCode,
      responseTime,
      timestamp: Date.now(),
      userAgent: req.headers['user-agent'],
      ip: req.ip,
    };

    logger.info('JSON response completed', { metrics });

    return originalJson.call(this, body);
  };

  next();
};

export const errorMonitor = (error: Error, req: Request, res: Response, next: NextFunction) => {
  const responseTime = performance.now() - (req as any).startTime;
  monitoringService.trackRequest((req as any).startTime, res.statusCode || 500);

  const metrics: RequestMetrics = {
    method: req.method,
    path: req.path,
    statusCode: res.statusCode || 500,
    responseTime,
    timestamp: Date.now(),
    userAgent: req.headers['user-agent'],
    ip: req.ip,
  };

  logger.error('Request error', { error: error.message, metrics });

  next(error);
}; 