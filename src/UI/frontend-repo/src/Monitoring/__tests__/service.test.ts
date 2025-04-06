import { MonitoringService, SystemMetrics, Alert } from '../service';
import { NotificationService } from '../notificationService';
import { performance } from 'perf_hooks';

describe('MonitoringService', () => {
  let service: MonitoringService;

  beforeEach(() => {
    MonitoringService.resetInstance();
    service = MonitoringService.getInstance();
  });

  afterEach(() => {
    MonitoringService.resetInstance();
  });

  describe('Request Tracking', () => {
    it('should track successful requests', () => {
      const startTime = performance.now();
      service.trackRequest(startTime, 200);

      const metrics = service.getMetrics();
      expect(metrics.requests.total).toBe(1);
      expect(metrics.requests.successful).toBe(1);
      expect(metrics.requests.failed).toBe(0);
      expect(metrics.requests.avgResponseTime).toBeGreaterThan(0);
    });

    it('should track failed requests', () => {
      const startTime = performance.now();
      service.trackRequest(startTime, 500);

      const metrics = service.getMetrics();
      expect(metrics.requests.total).toBe(1);
      expect(metrics.requests.successful).toBe(0);
      expect(metrics.requests.failed).toBe(1);
      expect(metrics.requests.avgResponseTime).toBeGreaterThan(0);
    });

    it('should calculate average response time', () => {
      const startTime = performance.now();
      service.trackRequest(startTime, 200);
      service.trackRequest(startTime, 200);
      service.trackRequest(startTime, 500);

      const metrics = service.getMetrics();
      expect(metrics.requests.total).toBe(3);
      expect(metrics.requests.avgResponseTime).toBeGreaterThan(0);
    });
  });

  describe('Metrics Collection', () => {
    it('should collect system metrics', () => {
      const metrics = service.getMetrics();

      expect(metrics.cpu).toBeDefined();
      expect(metrics.memory).toBeDefined();
      expect(metrics.process).toBeDefined();
      expect(metrics.requests).toBeDefined();
    });

    it('should maintain metrics history', () => {
      const startTime = performance.now();
      service.trackRequest(startTime, 200);

      const history = service.getMetricsHistory();
      expect(history.length).toBeGreaterThan(0);
      expect(history[0].requests.total).toBe(1);
    });

    it('should limit metrics history length', () => {
      const maxLength = 1000;
      const startTime = performance.now();

      // Add more metrics than the limit
      for (let i = 0; i < maxLength + 10; i++) {
        service.trackRequest(startTime, 200);
      }

      const history = service.getMetricsHistory();
      expect(history.length).toBeLessThanOrEqual(maxLength);
    });
  });

  describe('Alert Management', () => {
    it('should create alerts for high CPU usage', () => {
      // Simulate high CPU usage
      const metrics = service.getMetrics();
      metrics.cpu.usage = 85;
      metrics.memory.percentUsed = 0; // Ensure memory usage is low
      (service as any).metrics = metrics;
      (service as any).lastCpuUsage = 85; // Set lastCpuUsage to match
      (service as any).checkThresholds();

      const alerts = service.getAlerts();
      const cpuAlerts = alerts.filter(alert => alert.message.includes('CPU usage'));
      expect(cpuAlerts.length).toBe(1);
      expect(cpuAlerts[0].type).toBe('warning');
      expect(cpuAlerts[0].message).toContain('High CPU usage');
    });

    it('should create alerts for high memory usage', () => {
      // Simulate high memory usage
      const metrics = service.getMetrics();
      metrics.memory = {
        total: 8589934592,
        used: 8160437814,
        free: 429496778,
        percentUsed: 95
      };
      metrics.cpu.usage = 0; // Ensure CPU usage is low
      (service as any).metrics = metrics;
      (service as any).checkThresholds();

      const alerts = service.getAlerts();
      const memoryAlerts = alerts.filter(alert => alert.message.includes('memory usage'));
      expect(memoryAlerts.length).toBe(1);
      expect(memoryAlerts[0].type).toBe('warning');
      expect(memoryAlerts[0].message).toContain('High memory usage');
    });

    it('should create alerts for high error rate', () => {
      const startTime = performance.now();
      // Create a high error rate
      for (let i = 0; i < 10; i++) {
        service.trackRequest(startTime, 500);
      }

      const alerts = service.getAlerts();
      const errorAlerts = alerts.filter(alert => alert.message.includes('error rate'));
      expect(errorAlerts.length).toBe(1);
      expect(errorAlerts[0].type).toBe('error');
      expect(errorAlerts[0].message).toContain('High error rate');
    });

    it('should limit alert history length', () => {
      const maxAlerts = 1000;
      const startTime = performance.now();

      // Add more alerts than the limit
      for (let i = 0; i < maxAlerts + 10; i++) {
        service.trackRequest(startTime, 500);
      }

      const alerts = service.getAlerts();
      expect(alerts.length).toBeLessThanOrEqual(maxAlerts);
    });

    it('should clear alerts', () => {
      const startTime = performance.now();
      service.trackRequest(startTime, 500);

      expect(service.getAlerts().length).toBeGreaterThan(0);
      service.clearAlerts();
      expect(service.getAlerts().length).toBe(0);
    });
  });

  describe('Event Emission', () => {
    it('should emit metrics updates', (done) => {
      service.once('metrics', (metrics: SystemMetrics) => {
        expect(metrics).toBeDefined();
        expect(metrics.timestamp).toBeDefined();
        done();
      });

      const startTime = performance.now();
      service.trackRequest(startTime, 200);
    });

    it('should emit alerts', (done) => {
      service.once('alert', (alert: Alert) => {
        expect(alert).toBeDefined();
        expect(alert.type).toBe('warning');
        expect(alert.message).toContain('High memory usage');
        done();
      });

      // Simulate high memory usage
      const metrics = service.getMetrics();
      metrics.memory.percentUsed = 95;
      (service as any).metrics = metrics;
      (service as any).checkThresholds();
    });
  });
}); 