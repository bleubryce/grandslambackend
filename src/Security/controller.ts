import { Request, Response } from 'express';
import bcrypt from 'bcrypt';
import jwt, { SignOptions, Secret } from 'jsonwebtoken';
import { DatabaseManager } from '../Database/DatabaseManager';
import { config } from '../config';
import { logger } from '../utils/logger';
import { RateLimiter } from './RateLimiter';
import { PasswordValidator } from './PasswordValidator';

export class SecurityController {
    private dbManager: DatabaseManager;
    private rateLimiter: RateLimiter;
    private passwordValidator: PasswordValidator;
    private jwtSecret: Secret;
    private jwtExpiresIn: number;

    constructor() {
        this.dbManager = new DatabaseManager(config.database);
        this.rateLimiter = new RateLimiter(config.security.rateLimit);
        this.passwordValidator = new PasswordValidator(config.security.password);
        this.jwtSecret = Buffer.from(config.security.jwt.secret, 'utf-8');
        // Convert JWT expiration time to seconds if it's in the format '24h'
        this.jwtExpiresIn = config.security.jwt.expiresIn.endsWith('h')
            ? parseInt(config.security.jwt.expiresIn.slice(0, -1), 10) * 3600
            : parseInt(config.security.jwt.expiresIn, 10);
    }

    async login(req: Request, res: Response): Promise<void> {
        try {
            const { username, password } = req.body;
            const ip = req.ip || '0.0.0.0';

            // Check rate limiting
            if (await this.rateLimiter.isLimited(ip)) {
                res.status(429).json({ error: 'Too many login attempts. Please try again later.' });
                return;
            }

            // Get user from database
            const dbClient = await this.dbManager.getConnection();
            const user = await dbClient.query('SELECT * FROM users WHERE username = $1', [username]);

            if (!user.rows[0]) {
                res.status(401).json({ error: 'Invalid credentials' });
                return;
            }

            // Verify password
            const isValid = await bcrypt.compare(password, user.rows[0].password);
            if (!isValid) {
                await this.rateLimiter.increment(ip);
                res.status(401).json({ error: 'Invalid credentials' });
                return;
            }

            // Generate JWT token
            const signOptions: SignOptions = { expiresIn: this.jwtExpiresIn };
            const token = jwt.sign(
                { userId: user.rows[0].id, username },
                this.jwtSecret,
                signOptions
            );

            res.json({ token });
            logger.info(`User ${username} logged in successfully`);
        } catch (error) {
            logger.error('Login error:', error);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    async register(req: Request, res: Response): Promise<void> {
        try {
            const { username, password, email } = req.body;

            // Validate password
            const passwordValidation = this.passwordValidator.validate(password);
            if (!passwordValidation.isValid) {
                res.status(400).json({ errors: passwordValidation.errors });
                return;
            }

            // Check if username already exists
            const dbClient = await this.dbManager.getConnection();
            const existingUser = await dbClient.query('SELECT * FROM users WHERE username = $1', [username]);

            if (existingUser.rows[0]) {
                res.status(400).json({ error: 'Username already exists' });
                return;
            }

            // Hash password and create user
            const hashedPassword = await bcrypt.hash(password, 10);
            const result = await dbClient.query(
                'INSERT INTO users (username, password, email) VALUES ($1, $2, $3) RETURNING id',
                [username, hashedPassword, email]
            );

            // Generate JWT token
            const signOptions: SignOptions = { expiresIn: this.jwtExpiresIn };
            const token = jwt.sign(
                { userId: result.rows[0].id, username },
                this.jwtSecret,
                signOptions
            );

            res.status(201).json({ token });
            logger.info(`New user registered: ${username}`);
        } catch (error) {
            logger.error('Registration error:', error);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    async validateToken(req: Request, res: Response): Promise<void> {
        try {
            const token = req.headers.authorization?.split(' ')[1];

            if (!token) {
                res.status(401).json({ error: 'No token provided' });
                return;
            }

            try {
                const decoded = jwt.verify(token, this.jwtSecret);
                res.json({ valid: true, user: decoded });
            } catch (error) {
                res.status(401).json({ error: 'Invalid token' });
            }
        } catch (error) {
            logger.error('Token validation error:', error);
            res.status(500).json({ error: 'Internal server error' });
        }
    }
} 