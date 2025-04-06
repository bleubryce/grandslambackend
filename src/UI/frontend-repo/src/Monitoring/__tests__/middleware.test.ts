import { Request, Response } from 'express';
import { MonitoringService } from '../service';
import { requestMonitor, errorMonitor } from '../middleware';

jest.mock('../service', () => {
  const mockService = {
    getMetrics: jest.fn(),
    getMetricsHistory: jest.fn(),
    getAlerts: jest.fn(),
    clearAlerts: jest.fn(),
    trackRequest: jest.fn(),
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

describe('Monitoring Middleware', () => {
  let mockRequest: Partial<Request>;
  let mockResponse: Partial<Response>;
  let nextFunction: jest.Mock;
  let monitoringService: jest.Mocked<any>;

  beforeEach(() => {
    jest.clearAllMocks();
    
    mockRequest = {
      method: 'GET',
      path: '/test',
      headers: {
        'user-agent': 'test-agent',
      },
      ip: '127.0.0.1',
    };

    mockResponse = {
      statusCode: 200,
      end: jest.fn(),
      json: jest.fn(),
    };

    nextFunction = jest.fn();
    monitoringService = MonitoringService.getInstance();
  });

  describe('requestMonitor', () => {
    it('should track successful requests with end()', () => {
      requestMonitor(mockRequest as Request, mockResponse as Response, nextFunction);

      expect(nextFunction).toHaveBeenCalled();
      expect(mockResponse.end).toBeDefined();

      (mockResponse.end as jest.Mock)();

      expect(monitoringService.trackRequest).toHaveBeenCalledWith(
        expect.any(Number),
        200
      );
    });

    it('should track successful requests with json()', () => {
      requestMonitor(mockRequest as Request, mockResponse as Response, nextFunction);

      expect(nextFunction).toHaveBeenCalled();
      expect(mockResponse.json).toBeDefined();

      (mockResponse.json as jest.Mock)({ data: 'test' });

      expect(monitoringService.trackRequest).toHaveBeenCalledWith(
        expect.any(Number),
        200
      );
    });

    it('should track failed requests', () => {
      mockResponse.statusCode = 500;

      requestMonitor(mockRequest as Request, mockResponse as Response, nextFunction);
      (mockResponse.end as jest.Mock)();

      expect(monitoringService.trackRequest).toHaveBeenCalledWith(
        expect.any(Number),
        500
      );
    });

    it('should preserve original response methods', () => {
      const originalEnd = mockResponse.end;
      const originalJson = mockResponse.json;

      requestMonitor(mockRequest as Request, mockResponse as Response, nextFunction);

      expect(mockResponse.end).not.toBe(originalEnd);
      expect(mockResponse.json).not.toBe(originalJson);

      // Test end() method
      (mockResponse.end as jest.Mock)();
      expect(monitoringService.trackRequest).toHaveBeenCalledTimes(1);
      expect(monitoringService.trackRequest).toHaveBeenLastCalledWith(
        expect.any(Number),
        200
      );

      // Reset mock
      jest.clearAllMocks();

      // Test json() method separately
      (mockResponse.json as jest.Mock)({ data: 'test' });
      expect(monitoringService.trackRequest).toHaveBeenCalledTimes(1);
      expect(monitoringService.trackRequest).toHaveBeenLastCalledWith(
        expect.any(Number),
        200
      );
    });
  });

  describe('errorMonitor', () => {
    it('should track error responses', () => {
      const error = new Error('Test error');
      (mockRequest as any).startTime = performance.now();
      mockResponse.statusCode = 500;

      errorMonitor(error, mockRequest as Request, mockResponse as Response, nextFunction);

      expect(monitoringService.trackRequest).toHaveBeenCalledWith(
        expect.any(Number),
        500
      );
      expect(nextFunction).toHaveBeenCalledWith(error);
    });

    it('should use default status code 500 if not set', () => {
      const error = new Error('Test error');
      mockResponse.statusCode = undefined;
      (mockRequest as any).startTime = performance.now();

      errorMonitor(error, mockRequest as Request, mockResponse as Response, nextFunction);

      expect(monitoringService.trackRequest).toHaveBeenCalledWith(
        expect.any(Number),
        500
      );
    });

    it('should include request details in error tracking', () => {
      const error = new Error('Test error');
      (mockRequest as any).startTime = performance.now();
      mockResponse.statusCode = 500;

      errorMonitor(error, mockRequest as Request, mockResponse as Response, nextFunction);

      expect(monitoringService.trackRequest).toHaveBeenCalledWith(
        expect.any(Number),
        500
      );
    });
  });
}); 