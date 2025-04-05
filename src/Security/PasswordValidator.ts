import { createLogger } from '../utils/logger';

const logger = createLogger('password-validator');

export interface PasswordPolicy {
  minLength: number;
  requireUppercase: boolean;
  requireLowercase: boolean;
  requireNumbers: boolean;
  requireSpecialChars: boolean;
  maxLength?: number;
  preventCommonPasswords?: boolean;
  preventPersonalInfo?: boolean;
}

export interface ValidationResult {
  isValid: boolean;
  errors: string[];
}

export class PasswordValidator {
  private policy: PasswordPolicy;
  private commonPasswords: Set<string>;

  constructor(policy: Partial<PasswordPolicy> = {}) {
    this.policy = {
      minLength: policy.minLength || 12,
      requireUppercase: policy.requireUppercase !== false,
      requireLowercase: policy.requireLowercase !== false,
      requireNumbers: policy.requireNumbers !== false,
      requireSpecialChars: policy.requireSpecialChars !== false,
      maxLength: policy.maxLength || 128,
      preventCommonPasswords: policy.preventCommonPasswords !== false,
      preventPersonalInfo: policy.preventPersonalInfo !== false
    };

    // Initialize common passwords set (in a real app, load from a file or database)
    this.commonPasswords = new Set(['password', '123456', 'qwerty', 'admin']);
  }

  validate(password: string, personalInfo?: { [key: string]: string }): ValidationResult {
    const errors: string[] = [];

    // Check length
    if (password.length < this.policy.minLength) {
      errors.push(`Password must be at least ${this.policy.minLength} characters long`);
    }
    if (this.policy.maxLength && password.length > this.policy.maxLength) {
      errors.push(`Password must not exceed ${this.policy.maxLength} characters`);
    }

    // Check character requirements
    if (this.policy.requireUppercase && !/[A-Z]/.test(password)) {
      errors.push('Password must contain at least one uppercase letter');
    }
    if (this.policy.requireLowercase && !/[a-z]/.test(password)) {
      errors.push('Password must contain at least one lowercase letter');
    }
    if (this.policy.requireNumbers && !/\d/.test(password)) {
      errors.push('Password must contain at least one number');
    }
    if (this.policy.requireSpecialChars && !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
      errors.push('Password must contain at least one special character');
    }

    // Check for common passwords
    if (this.policy.preventCommonPasswords && this.commonPasswords.has(password.toLowerCase())) {
      errors.push('Password is too common');
    }

    // Check for personal information in password
    if (this.policy.preventPersonalInfo && personalInfo) {
      for (const [key, value] of Object.entries(personalInfo)) {
        if (value && password.toLowerCase().includes(value.toLowerCase())) {
          errors.push(`Password must not contain personal information (${key})`);
          break;
        }
      }
    }

    return {
      isValid: errors.length === 0,
      errors
    };
  }

  getPasswordRequirements(): string[] {
    const requirements = [
      `Password must be at least ${this.policy.minLength} characters long`,
    ];

    if (this.policy.maxLength) {
      requirements.push(`Password must not exceed ${this.policy.maxLength} characters`);
    }
    if (this.policy.requireUppercase) {
      requirements.push('Password must contain at least one uppercase letter');
    }
    if (this.policy.requireLowercase) {
      requirements.push('Password must contain at least one lowercase letter');
    }
    if (this.policy.requireNumbers) {
      requirements.push('Password must contain at least one number');
    }
    if (this.policy.requireSpecialChars) {
      requirements.push('Password must contain at least one special character');
    }
    if (this.policy.preventCommonPasswords) {
      requirements.push('Password must not be a commonly used password');
    }
    if (this.policy.preventPersonalInfo) {
      requirements.push('Password must not contain personal information');
    }

    return requirements;
  }
} 