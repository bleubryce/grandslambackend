import { createLogger } from '../utils/logger';
import { EventEmitter } from 'events';
import os from 'os';
import { performance } from 'perf_hooks';
import { NotificationService } from './notificationService';

const logger = createLogger('monitoring-service');

export interface SystemMetrics {
  timestamp: number;
  cpu: {
    usage: number;
    loadAverage: number[];
  };
  memory: {
    total: number;
    used: number;
    free: number;
    percentUsed: number;
  };
  process: {
    uptime: number;
    memoryUsage: {
      rss: number;
      heapTotal: number;
      heapUsed: number;
      external: number;
      arrayBuffers: number;
    };
    cpuUsage: {
      user: number;
      system: number;
    };
  };
  requests: {
    total: number;
    successful: number;
    failed: number;
    avgResponseTime: number;
  };
}

export interface Alert {
  id: string;
  type: 'info' | 'warning' | 'error' | 'critical';
  message: string;
  timestamp: number;
  metrics: Partial<SystemMetrics>;
}

export class MonitoringService extends EventEmitter {
  private static instance: MonitoringService | null = null;
  private metrics: SystemMetrics = {
    timestamp: Date.now(),
    cpu: {
      usage: 0,
      loadAverage: os.loadavg(),
    },
    memory: {
      total: os.totalmem(),
      used: os.totalmem() - os.freemem(),
      free: os.freemem(),
      percentUsed: ((os.totalmem() - os.freemem()) / os.totalmem()) * 100,
    },
    process: {
      uptime: process.uptime(),
      memoryUsage: process.memoryUsage(),
      cpuUsage: process.cpuUsage(),
    },
    requests: {
      total: 0,
      successful: 0,
      failed: 0,
      avgResponseTime: 0,
    },
  };
  private alerts: Alert[] = [];
  private readonly metricsHistory: SystemMetrics[] = [];
  private readonly maxHistoryLength: number = 1000;
  private requestStats = {
    count: 0,
    successful: 0,
    failed: 0,
    responseTimes: [] as number[],
  };
  private notificationService: NotificationService | null = null;
  private metricsInterval: NodeJS.Timeout | null = null;
  private lastCpuUsage: number = 0;

  private constructor() {
    super();
    this.startMetricsCollection();
  }

  static getInstance(): MonitoringService {
    if (!MonitoringService.instance) {
      MonitoringService.instance = new MonitoringService();
    }
    return MonitoringService.instance;
  }

  // For testing purposes only
  static resetInstance(): void {
    if (MonitoringService.instance) {
      if (MonitoringService.instance.metricsInterval) {
        clearInterval(MonitoringService.instance.metricsInterval);
        MonitoringService.instance.metricsInterval = null;
      }
      MonitoringService.instance.removeAllListeners();
      MonitoringService.instance.alerts = [];
      MonitoringService.instance.metricsHistory.length = 0;
      MonitoringService.instance.requestStats = {
        count: 0,
        successful: 0,
        failed: 0,
        responseTimes: [],
      };
      MonitoringService.instance.metrics = {
        timestamp: Date.now(),
        cpu: {
          usage: 0,
          loadAverage: [0, 0, 0],
        },
        memory: {
          total: 8589934592,
          used: 0,
          free: 8589934592,
          percentUsed: 0,
        },
        process: {
          uptime: process.uptime(),
          memoryUsage: process.memoryUsage(),
          cpuUsage: process.cpuUsage(),
        },
        requests: {
          total: 0,
          successful: 0,
          failed: 0,
          avgResponseTime: 0,
        },
      };
      MonitoringService.instance.lastCpuUsage = 0;
      MonitoringService.instance = null;
    }
    MonitoringService.instance = new MonitoringService();
  }

  setNotificationService(notificationService: NotificationService): void {
    this.notificationService = notificationService;
    logger.info('Notification service configured');
  }

  getMetrics(): SystemMetrics {
    return { ...this.metrics };
  }

  getMetricsHistory(): SystemMetrics[] {
    return [...this.metricsHistory];
  }

  getAlerts(limit = 100): Alert[] {
    return this.alerts.slice(-limit);
  }

  clearAlerts(): void {
    this.alerts = [];
    logger.info('Alerts cleared');
  }

  trackRequest(startTime: number, statusCode: number): void {
    const duration = performance.now() - startTime;
    this.requestStats.count++;
    
    if (statusCode >= 200 && statusCode < 400) {
      this.requestStats.successful++;
    } else {
      this.requestStats.failed++;
    }

    this.requestStats.responseTimes.push(duration);

    // Keep only the last 1000 response times
    if (this.requestStats.responseTimes.length > 1000) {
      this.requestStats.responseTimes.shift();
    }

    // Update metrics immediately
    this.metrics.requests = {
      total: this.requestStats.count,
      successful: this.requestStats.successful,
      failed: this.requestStats.failed,
      avgResponseTime: this.calculateAverageResponseTime(),
    };

    // Add to metrics history
    const newMetrics = { ...this.metrics, timestamp: Date.now() };
    this.metricsHistory.push(newMetrics);

    // Keep only the last maxHistoryLength metrics
    if (this.metricsHistory.length > this.maxHistoryLength) {
      this.metricsHistory.shift();
    }

    // Emit metrics update
    this.emit('metrics', this.metrics);

    // Check thresholds after updating metrics
    this.checkThresholds();
  }

  private startMetricsCollection(): void {
    if (this.metricsInterval) {
      clearInterval(this.metricsInterval);
    }
    this.metricsInterval = setInterval(() => {
      this.updateMetrics();
      this.checkThresholds();
    }, 5000); // Update every 5 seconds
    this.metricsInterval.unref(); // Don't keep the process alive
  }

  private updateMetrics(): void {
    const newMetrics: SystemMetrics = {
      timestamp: Date.now(),
      cpu: {
        usage: this.calculateCPUUsage(),
        loadAverage: os.loadavg(),
      },
      memory: {
        total: os.totalmem(),
        used: os.totalmem() - os.freemem(),
        free: os.freemem(),
        percentUsed: ((os.totalmem() - os.freemem()) / os.totalmem()) * 100,
      },
      process: {
        uptime: process.uptime(),
        memoryUsage: process.memoryUsage(),
        cpuUsage: process.cpuUsage(),
      },
      requests: { ...this.metrics.requests }, // Preserve request stats
    };

    this.metrics = newMetrics;
    this.metricsHistory.push({ ...newMetrics }); // Clone metrics before pushing

    // Keep only the last maxHistoryLength metrics
    if (this.metricsHistory.length > this.maxHistoryLength) {
      this.metricsHistory.shift();
    }

    this.emit('metrics', newMetrics);
  }

  private calculateCPUUsage(): number {
    const cpus = os.cpus();
    let totalIdle = 0;
    let totalTick = 0;

    cpus.forEach(cpu => {
      for (const type in cpu.times) {
        totalTick += cpu.times[type as keyof typeof cpu.times];
      }
      totalIdle += cpu.times.idle;
    });

    const usage = Math.round(((totalTick - totalIdle) / totalTick) * 100);
    this.lastCpuUsage = usage;
    return usage;
  }

  private calculateAverageResponseTime(): number {
    if (this.requestStats.responseTimes.length === 0) {
      return 0;
    }
    const sum = this.requestStats.responseTimes.reduce((a, b) => a + b, 0);
    return sum / this.requestStats.responseTimes.length;
  }

  private updateRequestMetrics(): void {
    this.metrics.requests = {
      total: this.requestStats.count,
      successful: this.requestStats.successful,
      failed: this.requestStats.failed,
      avgResponseTime: this.calculateAverageResponseTime(),
    };
  }

  private checkThresholds(): void {
    const alerts: Alert[] = [];
    const now = Date.now();

    // CPU Usage threshold (80%)
    if (this.metrics.cpu.usage > 80) {
      // Check if we already have a CPU alert in the last minute
      const lastCpuAlert = this.alerts
        .slice()
        .reverse()
        .find(alert => alert.message.includes('CPU usage'));

      if (!lastCpuAlert || now - lastCpuAlert.timestamp > 60000) {
        alerts.push({
          id: `${now}-${Math.random().toString(36).substr(2, 9)}`,
          type: 'warning',
          message: `High CPU usage: ${this.metrics.cpu.usage}%`,
          timestamp: now,
          metrics: {
            cpu: this.metrics.cpu,
            memory: this.metrics.memory,
            requests: this.metrics.requests,
          },
        });
      }
    }

    // Memory Usage threshold (90%)
    if (this.metrics.memory.percentUsed > 90) {
      // Check if we already have a memory alert in the last minute
      const lastMemoryAlert = this.alerts
        .slice()
        .reverse()
        .find(alert => alert.message.includes('memory usage'));

      if (!lastMemoryAlert || now - lastMemoryAlert.timestamp > 60000) {
        alerts.push({
          id: `${now}-${Math.random().toString(36).substr(2, 9)}`,
          type: 'warning',
          message: `High memory usage: ${this.metrics.memory.percentUsed}%`,
          timestamp: now,
          metrics: {
            cpu: this.metrics.cpu,
            memory: this.metrics.memory,
            requests: this.metrics.requests,
          },
        });
      }
    }

    // Error Rate threshold (10%)
    if (this.metrics.requests.total > 0) {
      const errorRate = (this.metrics.requests.failed / this.metrics.requests.total) * 100;
      if (errorRate > 10) {
        // Check if we already have an error rate alert in the last minute
        const lastErrorAlert = this.alerts
          .slice()
          .reverse()
          .find(alert => alert.message.includes('error rate'));

        if (!lastErrorAlert || now - lastErrorAlert.timestamp > 60000) {
          alerts.push({
            id: `${now}-${Math.random().toString(36).substr(2, 9)}`,
            type: 'error',
            message: `High error rate: ${errorRate.toFixed(2)}%`,
            timestamp: now,
            metrics: {
              cpu: this.metrics.cpu,
              memory: this.metrics.memory,
              requests: this.metrics.requests,
            },
          });
        }
      }
    }

    // Response Time threshold (2000ms)
    if (this.metrics.requests.avgResponseTime > 2000) {
      // Check if we already have a response time alert in the last minute
      const lastResponseTimeAlert = this.alerts
        .slice()
        .reverse()
        .find(alert => alert.message.includes('response time'));

      if (!lastResponseTimeAlert || now - lastResponseTimeAlert.timestamp > 60000) {
        alerts.push({
          id: `${now}-${Math.random().toString(36).substr(2, 9)}`,
          type: 'warning',
          message: `High average response time: ${this.metrics.requests.avgResponseTime.toFixed(2)}ms`,
          timestamp: now,
          metrics: {
            cpu: this.metrics.cpu,
            memory: this.metrics.memory,
            requests: this.metrics.requests,
          },
        });
      }
    }

    // Add new alerts and notify
    for (const alert of alerts) {
      this.alerts.push(alert);
      this.emit('alert', alert);
      logger.warn('Alert created', { alert });

      // Send notification if service is configured
      if (this.notificationService) {
        this.notificationService.sendNotification(alert).catch(error => {
          logger.error('Failed to send alert notification:', error);
        });
      }
    }

    // Keep only the last 1000 alerts
    if (this.alerts.length > 1000) {
      this.alerts = this.alerts.slice(-1000);
    }
  }
} 