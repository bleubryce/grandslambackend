export interface SecurityConfig {
  auth: {
    jwtSecret: string;
    jwtExpiresIn: string;
    passwordPolicy: {
      minLength: number;
      requireUppercase: boolean;
      requireLowercase: boolean;
      requireNumbers: boolean;
      requireSpecialChars: boolean;
      maxLength: number;
      preventCommonPasswords: boolean;
      preventPersonalInfo: boolean;
    };
  };
  redis: {
    host: string;
    port: number;
    password?: string;
  };
  rateLimit: {
    windowMs: number;
    max: number;
    message: string;
  };
  cors: {
    origin: string | string[];
    methods: string[];
    allowedHeaders: string[];
    exposedHeaders: string[];
    credentials: boolean;
    maxAge: number;
  };
  csp: {
    directives: {
      defaultSrc: string[];
      scriptSrc: string[];
      styleSrc: string[];
      fontSrc: string[];
      imgSrc: string[];
      connectSrc: string[];
    };
  };
  session: {
    secret: string;
    name: string;
    resave: boolean;
    saveUninitialized: boolean;
    cookie: {
      secure: boolean;
      httpOnly: boolean;
      maxAge: number;
      sameSite: 'strict' | 'lax' | 'none' | undefined;
    };
  };
  headers: {
    hsts: {
      maxAge: number;
      includeSubDomains: boolean;
      preload: boolean;
    };
    noSniff: boolean;
    xssFilter: boolean;
    frameguard: {
      action: 'deny' | 'sameorigin' | 'allow-from' | 'sameorigin' | 'allow-from' | undefined;
    };
    referrerPolicy: 'no-referrer' | 'no-referrer-when-downgrade' | 'origin' | 'origin-when-cross-origin' | 'same-origin' | 'strict-origin' | 'strict-origin-when-cross-origin' | 'unsafe-url' | undefined;
  };
  validation: {
    sanitization: {
      enabled: boolean;
      options: {
        stripTags: boolean;
        stripSpecialChars: boolean;
        escapeHTML: boolean;
      };
    };
    maxRequestSize: string;
  };
  logging: {
    level: string;
    securityEvents: boolean;
    auditTrail: boolean;
  };
}

export const securityConfig: SecurityConfig = {
  auth: {
    jwtSecret: process.env.JWT_SECRET || 'your-development-jwt-secret',
    jwtExpiresIn: process.env.JWT_EXPIRES_IN || '24h',
    passwordPolicy: {
      minLength: 12,
      requireUppercase: true,
      requireLowercase: true,
      requireNumbers: true,
      requireSpecialChars: true,
      maxLength: 128,
      preventCommonPasswords: true,
      preventPersonalInfo: true
    }
  },
  redis: {
    host: process.env.REDIS_HOST || 'localhost',
    port: parseInt(process.env.REDIS_PORT || '6379'),
    password: process.env.REDIS_PASSWORD
  },
  rateLimit: {
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // limit each IP to 100 requests per windowMs
    message: 'Too many requests from this IP, please try again later'
  },
  cors: {
    origin: process.env.NODE_ENV === 'production' 
      ? process.env.CORS_ORIGIN || 'https://app.grandslam.com'
      : 'http://localhost:8080',
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization'],
    exposedHeaders: ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'],
    credentials: true,
    maxAge: 86400 // 24 hours
  },
  csp: {
    directives: {
      defaultSrc: ["'self'"],
      scriptSrc: ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
      styleSrc: ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],
      fontSrc: ["'self'", 'https://fonts.gstatic.com'],
      imgSrc: ["'self'", 'data:', 'https:'],
      connectSrc: ["'self'", 'wss:', 'https:'],
    },
  },
  session: {
    secret: process.env.SESSION_SECRET || 'your-default-session-secret',
    name: 'sessionId',
    resave: false,
    saveUninitialized: false,
    cookie: {
      secure: process.env.NODE_ENV === 'production',
      httpOnly: true,
      maxAge: 24 * 60 * 60 * 1000, // 24 hours
      sameSite: 'strict' as const,
    },
  },
  headers: {
    hsts: {
      maxAge: 31536000, // 1 year
      includeSubDomains: true,
      preload: true,
    },
    noSniff: true,
    xssFilter: true,
    frameguard: {
      action: 'deny' as const,
    },
    referrerPolicy: 'same-origin' as const,
  },
  validation: {
    sanitization: {
      enabled: true,
      options: {
        stripTags: true,
        stripSpecialChars: true,
        escapeHTML: true,
      },
    },
    maxRequestSize: '10mb',
  },
  logging: {
    level: process.env.NODE_ENV === 'production' ? 'info' : 'debug',
    securityEvents: true,
    auditTrail: true,
  },
}; 