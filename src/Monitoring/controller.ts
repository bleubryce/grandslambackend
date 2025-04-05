import { Request, Response } from 'express';
import { MonitoringService } from './service';
import { createLogger } from '../utils/logger';

const logger = createLogger('monitoring-controller');
const monitoringService = MonitoringService.getInstance();

export class MonitoringController {
  getMetrics(req: Request, res: Response) {
    try {
      const metrics = monitoringService.getMetrics();
      return res.json(metrics);
    } catch (error) {
      logger.error('Error getting metrics:', error);
      return res.status(500).json({ error: 'Failed to retrieve metrics' });
    }
  }

  getMetricsHistory(req: Request, res: Response) {
    try {
      const { limit = '100', from, to } = req.query;
      let history = monitoringService.getMetricsHistory();

      if (from) {
        const fromTime = new Date(from as string).getTime();
        history = history.filter(metric => metric.timestamp >= fromTime);
      }

      if (to) {
        const toTime = new Date(to as string).getTime();
        history = history.filter(metric => metric.timestamp <= toTime);
      }

      const limitNum = Math.min(parseInt(limit as string, 10), 1000);
      history = history.slice(-limitNum);

      return res.json(history);
    } catch (error) {
      logger.error('Error getting metrics history:', error);
      return res.status(500).json({ error: 'Failed to retrieve metrics history' });
    }
  }

  getAlerts(req: Request, res: Response) {
    try {
      const { limit = '100', type } = req.query;
      let alerts = monitoringService.getAlerts(parseInt(limit as string, 10));

      if (type) {
        alerts = alerts.filter(alert => alert.type === type);
      }

      return res.json(alerts);
    } catch (error) {
      logger.error('Error getting alerts:', error);
      return res.status(500).json({ error: 'Failed to retrieve alerts' });
    }
  }

  clearAlerts(req: Request, res: Response) {
    try {
      monitoringService.clearAlerts();
      return res.json({ message: 'Alerts cleared successfully' });
    } catch (error) {
      logger.error('Error clearing alerts:', error);
      return res.status(500).json({ error: 'Failed to clear alerts' });
    }
  }

  getSystemHealth(req: Request, res: Response) {
    try {
      const metrics = monitoringService.getMetrics();
      const alerts = monitoringService.getAlerts(10);

      const health = {
        status: this.calculateSystemStatus(metrics, alerts),
        timestamp: Date.now(),
        metrics: {
          cpu: metrics.cpu,
          memory: metrics.memory,
          requests: metrics.requests,
        },
        recentAlerts: alerts,
      };

      return res.json(health);
    } catch (error) {
      logger.error('Error getting system health:', error);
      return res.status(500).json({ error: 'Failed to retrieve system health' });
    }
  }

  private calculateSystemStatus(metrics: any, alerts: any[]): 'healthy' | 'degraded' | 'critical' {
    // Check for critical alerts in the last hour
    const recentCriticalAlerts = alerts.filter(
      alert => alert.type === 'critical' && 
      alert.timestamp > Date.now() - 3600000
    ).length;

    if (recentCriticalAlerts > 0 || metrics.cpu.usage > 90 || metrics.memory.percentUsed > 95) {
      return 'critical';
    }

    if (metrics.cpu.usage > 70 || metrics.memory.percentUsed > 80 || 
        metrics.requests.avgResponseTime > 1000) {
      return 'degraded';
    }

    return 'healthy';
  }
} 