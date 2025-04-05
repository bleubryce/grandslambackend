import { Request, Response } from 'express';
import { MonitoringService, SystemMetrics, Alert } from '../service';
import { MonitoringController } from '../controller';

// Mock the MonitoringService module
jest.mock('../service', () => {
  const mockService = {
    getMetrics: jest.fn(),
    getMetricsHistory: jest.fn(),
    getAlerts: jest.fn(),
    clearAlerts: jest.fn(),
    getInstance: jest.fn(),
    on: jest.fn(),
    once: jest.fn(),
    emit: jest.fn(),
    removeAllListeners: jest.fn(),
  };
  return {
    MonitoringService: {
      getInstance: jest.fn(() => mockService)
    }
  };
});

describe('MonitoringController', () => {
  let mockRequest: Partial<Request>;
  let mockResponse: Partial<Response>;
  let monitoringService: jest.Mocked<any>;
  let monitoringController: MonitoringController;

  beforeEach(() => {
    mockRequest = {
      query: {},
    };

    mockResponse = {
      json: jest.fn().mockReturnThis(),
      status: jest.fn().mockReturnThis(),
    };

    // Reset all mocks before each test
    jest.clearAllMocks();

    // Get the mocked instance
    monitoringService = MonitoringService.getInstance();
    monitoringController = new MonitoringController();
  });

  describe('getMetrics', () => {
    it('should return current metrics', () => {
      const mockMetrics: SystemMetrics = {
        timestamp: Date.now(),
        cpu: { usage: 50, loadAverage: [1, 1, 1] },
        memory: { total: 1000, used: 500, free: 500, percentUsed: 50 },
        process: {
          uptime: 1000,
          memoryUsage: {
            rss: 100,
            heapTotal: 200,
            heapUsed: 150,
            external: 50,
            arrayBuffers: 10
          },
          cpuUsage: { user: 100, system: 50 }
        },
        requests: { total: 100, successful: 90, failed: 10, avgResponseTime: 100 },
      };

      monitoringService.getMetrics.mockReturnValue(mockMetrics);

      monitoringController.getMetrics(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.json).toHaveBeenCalledWith(mockMetrics);
    });

    it('should handle errors', () => {
      monitoringService.getMetrics.mockImplementation(() => {
        throw new Error('Test error');
      });

      monitoringController.getMetrics(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.status).toHaveBeenCalledWith(500);
      expect(mockResponse.json).toHaveBeenCalledWith({
        error: 'Failed to retrieve metrics'
      });
    });
  });

  describe('getMetricsHistory', () => {
    const mockHistory: SystemMetrics[] = [
      {
        timestamp: Date.now() - 1000,
        cpu: { usage: 40, loadAverage: [1, 1, 1] },
        memory: { total: 1000, used: 450, free: 550, percentUsed: 45 },
        process: {
          uptime: 900,
          memoryUsage: {
            rss: 90,
            heapTotal: 180,
            heapUsed: 135,
            external: 45,
            arrayBuffers: 9
          },
          cpuUsage: { user: 90, system: 45 }
        },
        requests: { total: 90, successful: 81, failed: 9, avgResponseTime: 95 },
      },
      {
        timestamp: Date.now(),
        cpu: { usage: 50, loadAverage: [1, 1, 1] },
        memory: { total: 1000, used: 500, free: 500, percentUsed: 50 },
        process: {
          uptime: 1000,
          memoryUsage: {
            rss: 100,
            heapTotal: 200,
            heapUsed: 150,
            external: 50,
            arrayBuffers: 10
          },
          cpuUsage: { user: 100, system: 50 }
        },
        requests: { total: 100, successful: 90, failed: 10, avgResponseTime: 100 },
      },
    ];

    it('should return metrics history with default limit', () => {
      monitoringService.getMetricsHistory.mockReturnValue(mockHistory);

      monitoringController.getMetricsHistory(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.json).toHaveBeenCalledWith(mockHistory);
    });

    it('should filter metrics by time range', () => {
      mockRequest.query = {
        from: new Date(Date.now() - 2000).toISOString(),
        to: new Date().toISOString(),
      };

      monitoringService.getMetricsHistory.mockReturnValue(mockHistory);

      monitoringController.getMetricsHistory(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.json).toHaveBeenCalledWith(mockHistory);
    });

    it('should handle errors', () => {
      monitoringService.getMetricsHistory.mockImplementation(() => {
        throw new Error('Test error');
      });

      monitoringController.getMetricsHistory(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.status).toHaveBeenCalledWith(500);
      expect(mockResponse.json).toHaveBeenCalledWith({
        error: 'Failed to retrieve metrics history'
      });
    });
  });

  describe('getAlerts', () => {
    const mockAlerts: Alert[] = [
      {
        id: '1',
        type: 'warning',
        message: 'High CPU usage',
        timestamp: Date.now(),
        metrics: {
          cpu: { usage: 85, loadAverage: [2, 2, 2] },
          memory: { total: 1000, used: 800, free: 200, percentUsed: 80 },
          requests: { total: 200, successful: 180, failed: 20, avgResponseTime: 150 },
        },
      },
      {
        id: '2',
        type: 'error',
        message: 'High memory usage',
        timestamp: Date.now(),
        metrics: {
          cpu: { usage: 70, loadAverage: [1.5, 1.5, 1.5] },
          memory: { total: 1000, used: 900, free: 100, percentUsed: 90 },
          requests: { total: 150, successful: 135, failed: 15, avgResponseTime: 120 },
        },
      },
    ];

    it('should return alerts with default limit', () => {
      monitoringService.getAlerts.mockReturnValue(mockAlerts);

      monitoringController.getAlerts(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.json).toHaveBeenCalledWith(mockAlerts);
    });

    it('should filter alerts by type', () => {
      mockRequest.query = { type: 'warning' };
      monitoringService.getAlerts.mockReturnValue(mockAlerts);

      monitoringController.getAlerts(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.json).toHaveBeenCalledWith(
        expect.arrayContaining([
          expect.objectContaining({ type: 'warning' })
        ])
      );
    });

    it('should handle errors', () => {
      monitoringService.getAlerts.mockImplementation(() => {
        throw new Error('Test error');
      });

      monitoringController.getAlerts(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.status).toHaveBeenCalledWith(500);
      expect(mockResponse.json).toHaveBeenCalledWith({
        error: 'Failed to retrieve alerts'
      });
    });
  });

  describe('clearAlerts', () => {
    it('should clear all alerts', () => {
      monitoringController.clearAlerts(mockRequest as Request, mockResponse as Response);

      expect(monitoringService.clearAlerts).toHaveBeenCalled();
      expect(mockResponse.json).toHaveBeenCalledWith({
        message: 'Alerts cleared successfully'
      });
    });

    it('should handle errors', () => {
      monitoringService.clearAlerts.mockImplementation(() => {
        throw new Error('Test error');
      });

      monitoringController.clearAlerts(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.status).toHaveBeenCalledWith(500);
      expect(mockResponse.json).toHaveBeenCalledWith({
        error: 'Failed to clear alerts'
      });
    });
  });

  describe('getSystemHealth', () => {
    it('should return system health status', () => {
      const mockMetrics: SystemMetrics = {
        timestamp: Date.now(),
        cpu: { usage: 50, loadAverage: [1, 1, 1] },
        memory: { total: 1000, used: 600, free: 400, percentUsed: 60 },
        process: {
          uptime: 1000,
          memoryUsage: {
            rss: 100,
            heapTotal: 200,
            heapUsed: 150,
            external: 50,
            arrayBuffers: 10
          },
          cpuUsage: { user: 100, system: 50 }
        },
        requests: { total: 100, successful: 90, failed: 10, avgResponseTime: 100 },
      };
      const mockAlerts: Alert[] = [];

      monitoringService.getMetrics.mockReturnValue(mockMetrics);
      monitoringService.getAlerts.mockReturnValue(mockAlerts);

      monitoringController.getSystemHealth(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.json).toHaveBeenCalledWith(
        expect.objectContaining({
          status: 'healthy',
          metrics: expect.any(Object),
          recentAlerts: expect.any(Array),
        })
      );
    });

    it('should detect degraded system status', () => {
      const mockMetrics: SystemMetrics = {
        timestamp: Date.now(),
        cpu: { usage: 75, loadAverage: [2, 2, 2] },
        memory: { total: 1000, used: 850, free: 150, percentUsed: 85 },
        process: {
          uptime: 1000,
          memoryUsage: {
            rss: 150,
            heapTotal: 300,
            heapUsed: 250,
            external: 75,
            arrayBuffers: 15
          },
          cpuUsage: { user: 150, system: 75 }
        },
        requests: { total: 200, successful: 160, failed: 40, avgResponseTime: 1500 },
      };
      const mockAlerts: Alert[] = [];

      monitoringService.getMetrics.mockReturnValue(mockMetrics);
      monitoringService.getAlerts.mockReturnValue(mockAlerts);

      monitoringController.getSystemHealth(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.json).toHaveBeenCalledWith(
        expect.objectContaining({
          status: 'degraded',
        })
      );
    });

    it('should detect critical system status', () => {
      const mockMetrics: SystemMetrics = {
        timestamp: Date.now(),
        cpu: { usage: 95, loadAverage: [3, 3, 3] },
        memory: { total: 1000, used: 980, free: 20, percentUsed: 98 },
        process: {
          uptime: 1000,
          memoryUsage: {
            rss: 200,
            heapTotal: 400,
            heapUsed: 350,
            external: 100,
            arrayBuffers: 20
          },
          cpuUsage: { user: 200, system: 100 }
        },
        requests: { total: 300, successful: 210, failed: 90, avgResponseTime: 2000 },
      };
      const mockAlerts: Alert[] = [
        {
          id: '1',
          type: 'critical',
          message: 'Critical error',
          timestamp: Date.now(),
          metrics: {
            cpu: { usage: 95, loadAverage: [3, 3, 3] },
            memory: { total: 1000, used: 980, free: 20, percentUsed: 98 },
            requests: { total: 300, successful: 210, failed: 90, avgResponseTime: 2000 },
          },
        },
      ];

      monitoringService.getMetrics.mockReturnValue(mockMetrics);
      monitoringService.getAlerts.mockReturnValue(mockAlerts);

      monitoringController.getSystemHealth(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.json).toHaveBeenCalledWith(
        expect.objectContaining({
          status: 'critical',
        })
      );
    });

    it('should handle errors', () => {
      monitoringService.getMetrics.mockImplementation(() => {
        throw new Error('Test error');
      });

      monitoringController.getSystemHealth(mockRequest as Request, mockResponse as Response);

      expect(mockResponse.status).toHaveBeenCalledWith(500);
      expect(mockResponse.json).toHaveBeenCalledWith({
        error: 'Failed to retrieve system health'
      });
    });
  });
}); 