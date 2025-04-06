import { Request, Response, NextFunction } from 'express';
import { requireAuth, requireRole, requireAnyRole, requireAllRoles } from '../authMiddleware';
import { SecurityService } from '../service';

const mockUser = { id: '123', username: 'testuser', roles: ['user'] };
const mockSecurityService = new SecurityService();

jest.spyOn(SecurityService.prototype, 'verifyToken').mockImplementation(() => mockUser);
jest.spyOn(SecurityService.prototype, 'hasRole').mockImplementation(() => true);
jest.spyOn(SecurityService.prototype, 'hasAnyRole').mockImplementation(() => true);
jest.spyOn(SecurityService.prototype, 'hasAllRoles').mockImplementation(() => true);

describe('Authentication Middleware', () => {
  let mockRequest: Partial<Request>;
  let mockResponse: Partial<Response>;
  let nextFunction: NextFunction;

  beforeEach(() => {
    mockRequest = {
      headers: {
        authorization: 'Bearer valid-token'
      }
    };
    mockResponse = {
      status: jest.fn().mockReturnThis(),
      json: jest.fn()
    };
    nextFunction = jest.fn();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  describe('requireAuth', () => {
    it('should call next() when token is valid', async () => {
      await requireAuth(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(nextFunction).toHaveBeenCalled();
      expect(mockResponse.status).not.toHaveBeenCalled();
      expect(mockRequest.user).toEqual(mockUser);
    });

    it('should return 401 when no token is provided', async () => {
      mockRequest.headers = {};
      await requireAuth(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(mockResponse.status).toHaveBeenCalledWith(401);
      expect(mockResponse.json).toHaveBeenCalledWith({ error: 'No token provided' });
    });

    it('should return 401 when token is invalid', async () => {
      jest.spyOn(SecurityService.prototype, 'verifyToken').mockImplementationOnce(() => {
        throw new Error('Invalid token');
      });
      await requireAuth(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(mockResponse.status).toHaveBeenCalledWith(401);
      expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Invalid token' });
    });
  });

  describe('requireRole', () => {
    beforeEach(async () => {
      // Set up user by running requireAuth first
      await requireAuth(mockRequest as Request, mockResponse as Response, jest.fn());
      jest.clearAllMocks(); // Clear mocks after setup
    });

    it('should call next() when user has required role', async () => {
      await requireRole('user')(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(nextFunction).toHaveBeenCalled();
      expect(mockResponse.status).not.toHaveBeenCalled();
    });

    it('should return 403 when user does not have required role', async () => {
      jest.spyOn(SecurityService.prototype, 'hasRole').mockImplementationOnce(() => false);
      await requireRole('admin')(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(mockResponse.status).toHaveBeenCalledWith(403);
      expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Insufficient permissions' });
    });
  });

  describe('requireAnyRole', () => {
    beforeEach(async () => {
      // Set up user by running requireAuth first
      await requireAuth(mockRequest as Request, mockResponse as Response, jest.fn());
      jest.clearAllMocks(); // Clear mocks after setup
    });

    it('should call next() when user has any of the required roles', async () => {
      await requireAnyRole(['user', 'admin'])(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(nextFunction).toHaveBeenCalled();
      expect(mockResponse.status).not.toHaveBeenCalled();
    });

    it('should return 403 when user does not have any of the required roles', async () => {
      jest.spyOn(SecurityService.prototype, 'hasAnyRole').mockImplementationOnce(() => false);
      await requireAnyRole(['admin', 'superuser'])(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(mockResponse.status).toHaveBeenCalledWith(403);
      expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Insufficient permissions' });
    });
  });

  describe('requireAllRoles', () => {
    beforeEach(async () => {
      // Set up user by running requireAuth first
      await requireAuth(mockRequest as Request, mockResponse as Response, jest.fn());
      jest.clearAllMocks(); // Clear mocks after setup
    });

    it('should call next() when user has all required roles', async () => {
      await requireAllRoles(['user', 'admin'])(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(nextFunction).toHaveBeenCalled();
      expect(mockResponse.status).not.toHaveBeenCalled();
    });

    it('should return 403 when user does not have all required roles', async () => {
      jest.spyOn(SecurityService.prototype, 'hasAllRoles').mockImplementationOnce(() => false);
      await requireAllRoles(['admin', 'superuser'])(mockRequest as Request, mockResponse as Response, nextFunction);
      expect(mockResponse.status).toHaveBeenCalledWith(403);
      expect(mockResponse.json).toHaveBeenCalledWith({ error: 'Insufficient permissions' });
    });
  });
}); 