export const securityConfig = {
  // Rate limiting settings
  rateLimit: {
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // Limit each IP to 100 requests per windowMs
    message: 'Too many requests from this IP, please try again later',
  },

  // CORS settings
  cors: {
    origin: process.env.NODE_ENV === 'production' 
      ? process.env.ALLOWED_ORIGINS?.split(',') || []
      : '*',
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization'],
    exposedHeaders: ['Content-Range', 'X-Content-Range'],
    credentials: true,
    maxAge: 86400, // 24 hours
  },

  // Content Security Policy
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

  // Authentication settings
  auth: {
    jwtSecret: process.env.JWT_SECRET || 'your-default-jwt-secret-key',
    jwtExpiresIn: '24h',
    bcryptSaltRounds: 12,
    passwordPolicy: {
      minLength: 8,
      requireUppercase: true,
      requireLowercase: true,
      requireNumbers: true,
      requireSpecialChars: true,
    },
  },

  // Session settings
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

  // Security headers
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

  // Input validation
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

  // Logging and monitoring
  logging: {
    level: process.env.NODE_ENV === 'production' ? 'info' : 'debug',
    securityEvents: true,
    auditTrail: true,
  },
}; 