import { SecurityService, User } from '../service';
import { securityConfig } from '../config';

describe('SecurityService', () => {
  let securityService: SecurityService;
  let testUser: User;

  beforeEach(() => {
    securityService = new SecurityService();
    testUser = {
      id: '123',
      username: 'testuser',
      password: 'hashedPassword123',
      roles: ['user', 'admin']
    };
  });

  describe('Password Management', () => {
    it('should hash passwords correctly', async () => {
      const password = 'TestPassword123!';
      const hash = await securityService.hashPassword(password);
      expect(hash).toBeDefined();
      expect(hash).not.toBe(password);
    });

    it('should verify passwords correctly', async () => {
      const password = 'TestPassword123!';
      const hash = await securityService.hashPassword(password);
      const isValid = await securityService.verifyPassword(password, hash);
      expect(isValid).toBe(true);
    });

    it('should reject incorrect passwords', async () => {
      const password = 'TestPassword123!';
      const hash = await securityService.hashPassword(password);
      const isValid = await securityService.verifyPassword('wrongpassword', hash);
      expect(isValid).toBe(false);
    });

    it('should validate password requirements', () => {
      const validPassword = 'TestPassword123!';
      const invalidPassword = 'weak';
      expect(securityService.validatePassword(validPassword)).toBe(true);
      expect(securityService.validatePassword(invalidPassword)).toBe(false);
    });
  });

  describe('Token Management', () => {
    it('should generate valid tokens', () => {
      const sanitizedUser = securityService.sanitizeUser(testUser);
      const token = securityService.generateToken(sanitizedUser);
      expect(token).toBeDefined();
      expect(typeof token).toBe('string');
    });

    it('should verify valid tokens', () => {
      const sanitizedUser = securityService.sanitizeUser(testUser);
      const token = securityService.generateToken(sanitizedUser);
      const decodedUser = securityService.verifyToken(token);
      expect(decodedUser.id).toBe(testUser.id);
      expect(decodedUser.username).toBe(testUser.username);
      expect(decodedUser.roles).toEqual(testUser.roles);
    });

    it('should reject invalid tokens', () => {
      expect(() => {
        securityService.verifyToken('invalid.token.here');
      }).toThrow();
    });
  });

  describe('Role Management', () => {
    it('should check single role correctly', () => {
      const sanitizedUser = securityService.sanitizeUser(testUser);
      expect(securityService.hasRole(sanitizedUser, 'admin')).toBe(true);
      expect(securityService.hasRole(sanitizedUser, 'superuser')).toBe(false);
    });

    it('should check multiple roles with ANY condition', () => {
      const sanitizedUser = securityService.sanitizeUser(testUser);
      expect(securityService.hasAnyRole(sanitizedUser, ['admin', 'superuser'])).toBe(true);
      expect(securityService.hasAnyRole(sanitizedUser, ['superuser', 'manager'])).toBe(false);
    });

    it('should check multiple roles with ALL condition', () => {
      const sanitizedUser = securityService.sanitizeUser(testUser);
      expect(securityService.hasAllRoles(sanitizedUser, ['user', 'admin'])).toBe(true);
      expect(securityService.hasAllRoles(sanitizedUser, ['admin', 'superuser'])).toBe(false);
    });
  });

  describe('User Sanitization', () => {
    it('should remove password from user object', () => {
      const sanitizedUser = securityService.sanitizeUser(testUser);
      expect(sanitizedUser.id).toBe(testUser.id);
      expect(sanitizedUser.username).toBe(testUser.username);
      expect(sanitizedUser.roles).toEqual(testUser.roles);
      expect((sanitizedUser as any).password).toBeUndefined();
    });
  });
}); 