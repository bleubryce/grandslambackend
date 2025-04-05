interface PasswordConfig {
  minLength: number;
  requireUppercase: boolean;
  requireLowercase: boolean;
  requireNumbers: boolean;
  requireSpecialChars: boolean;
}

interface ValidationResult {
  isValid: boolean;
  errors: string[];
}

export class PasswordValidator {
  private config: PasswordConfig;

  constructor(config: PasswordConfig) {
    this.config = config;
  }

  validate(password: string): ValidationResult {
    const errors: string[] = [];

    // Check minimum length
    if (password.length < this.config.minLength) {
      errors.push(`Password must be at least ${this.config.minLength} characters long`);
    }

    // Check for uppercase letters
    if (this.config.requireUppercase && !/[A-Z]/.test(password)) {
      errors.push('Password must contain at least one uppercase letter');
    }

    // Check for lowercase letters
    if (this.config.requireLowercase && !/[a-z]/.test(password)) {
      errors.push('Password must contain at least one lowercase letter');
    }

    // Check for numbers
    if (this.config.requireNumbers && !/\d/.test(password)) {
      errors.push('Password must contain at least one number');
    }

    // Check for special characters
    if (this.config.requireSpecialChars && !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
      errors.push('Password must contain at least one special character');
    }

    return {
      isValid: errors.length === 0,
      errors
    };
  }

  getRequirements(): string[] {
    const requirements: string[] = [
      `Minimum length: ${this.config.minLength} characters`
    ];

    if (this.config.requireUppercase) {
      requirements.push('Must contain at least one uppercase letter');
    }

    if (this.config.requireLowercase) {
      requirements.push('Must contain at least one lowercase letter');
    }

    if (this.config.requireNumbers) {
      requirements.push('Must contain at least one number');
    }

    if (this.config.requireSpecialChars) {
      requirements.push('Must contain at least one special character');
    }

    return requirements;
  }
} 