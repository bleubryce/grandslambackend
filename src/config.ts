import dotenv from 'dotenv';

dotenv.config();

interface DatabaseConfig {
    host: string;
    port: number;
    database: string;
    user: string;
    password: string;
}

interface JWTConfig {
    secret: string;
    expiresIn: string;
}

interface RateLimitConfig {
    windowMs: number;
    maxRequests: number;
}

interface PasswordConfig {
    minLength: number;
    requireUppercase: boolean;
    requireLowercase: boolean;
    requireNumbers: boolean;
    requireSpecialChars: boolean;
}

interface SecurityConfig {
    jwt: JWTConfig;
    rateLimit: RateLimitConfig;
    password: PasswordConfig;
    cors: {
        origin: string | string[];
        methods: string[];
        allowedHeaders: string[];
        exposedHeaders: string[];
        credentials: boolean;
        maxAge: number;
    };
}

interface AnalysisConfig {
    modelPath: string;
    batchSize: number;
    maxConcurrent: number;
}

export interface Config {
    port: number;
    database: DatabaseConfig;
    security: SecurityConfig;
    analysis: AnalysisConfig;
}

export const config: Config = {
    port: parseInt(process.env.PORT || '3001', 10),
    database: {
        host: process.env.DB_HOST || 'localhost',
        port: parseInt(process.env.DB_PORT || '5432', 10),
        database: process.env.DB_NAME || 'baseball_analytics',
        user: process.env.DB_USER || 'postgres',
        password: process.env.DB_PASSWORD || 'postgres'
    },
    security: {
        jwt: {
            secret: process.env.JWT_SECRET || 'your-secret-key',
            expiresIn: process.env.JWT_EXPIRES_IN || '24h'
        },
        rateLimit: {
            windowMs: parseInt(process.env.RATE_LIMIT_WINDOW || '900000', 10), // 15 minutes
            maxRequests: parseInt(process.env.RATE_LIMIT_MAX || '100', 10)
        },
        password: {
            minLength: parseInt(process.env.PASSWORD_MIN_LENGTH || '8', 10),
            requireUppercase: process.env.PASSWORD_REQUIRE_UPPERCASE !== 'false',
            requireLowercase: process.env.PASSWORD_REQUIRE_LOWERCASE !== 'false',
            requireNumbers: process.env.PASSWORD_REQUIRE_NUMBERS !== 'false',
            requireSpecialChars: process.env.PASSWORD_REQUIRE_SPECIAL !== 'false'
        },
        cors: {
            origin: ['http://localhost:3000', 'http://localhost:3001', 'http://localhost:3002', 'http://localhost:3003', 'http://localhost:3004', 'http://localhost:3005'],
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization'],
            exposedHeaders: ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
            credentials: true,
            maxAge: 86400
        }
    },
    analysis: {
        modelPath: process.env.MODEL_PATH || './models',
        batchSize: parseInt(process.env.ANALYSIS_BATCH_SIZE || '100', 10),
        maxConcurrent: parseInt(process.env.ANALYSIS_MAX_CONCURRENT || '5', 10)
    }
}; 