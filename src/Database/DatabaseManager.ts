import { Pool, PoolClient } from 'pg';
import { logger } from '../utils/logger';

interface DatabaseConfig {
    host: string;
    port: number;
    database: string;
    user: string;
    password: string;
}

export class DatabaseManager {
    private pool: Pool;
    private client: PoolClient | null = null;

    constructor(config: DatabaseConfig) {
        this.pool = new Pool({
            host: config.host,
            port: config.port,
            database: config.database,
            user: config.user,
            password: config.password,
            // Additional recommended settings
            max: 20, // Maximum number of clients in the pool
            idleTimeoutMillis: 30000, // Close idle clients after 30 seconds
            connectionTimeoutMillis: 2000, // Return an error after 2 seconds if connection could not be established
        });

        // Handle pool errors
        this.pool.on('error', (err) => {
            logger.error('Unexpected error on idle client', err);
            process.exit(-1);
        });
    }

    async getConnection(): Promise<PoolClient> {
        try {
            if (!this.client) {
                this.client = await this.pool.connect();
                logger.info('Database connection established');
            }
            return this.client;
        } catch (error) {
            logger.error('Error connecting to database:', error);
            throw error;
        }
    }

    async query(text: string, params?: any[]): Promise<any> {
        const client = await this.getConnection();
        try {
            const result = await client.query(text, params);
            return result;
        } catch (error) {
            logger.error('Error executing query:', error);
            throw error;
        }
    }

    async close(): Promise<void> {
        try {
            if (this.client) {
                await this.client.release();
                this.client = null;
            }
            await this.pool.end();
            logger.info('Database connection closed');
        } catch (error) {
            logger.error('Error closing database connection:', error);
            throw error;
        }
    }

    async transaction<T>(callback: (client: PoolClient) => Promise<T>): Promise<T> {
        const client = await this.getConnection();
        try {
            await client.query('BEGIN');
            const result = await callback(client);
            await client.query('COMMIT');
            return result;
        } catch (error) {
            await client.query('ROLLBACK');
            logger.error('Transaction error:', error);
            throw error;
        }
    }

    async createTables(): Promise<void> {
        const client = await this.getConnection();
        try {
            await client.query(`
                CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

                CREATE TABLE IF NOT EXISTS users (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    username VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS teams (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    name VARCHAR(255) NOT NULL,
                    city VARCHAR(255) NOT NULL,
                    league VARCHAR(50) NOT NULL,
                    division VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS players (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    team_id UUID REFERENCES teams(id),
                    first_name VARCHAR(255) NOT NULL,
                    last_name VARCHAR(255) NOT NULL,
                    position VARCHAR(50) NOT NULL,
                    jersey_number INTEGER,
                    bats VARCHAR(10),
                    throws VARCHAR(10),
                    birth_date DATE,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS games (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    home_team_id UUID REFERENCES teams(id),
                    away_team_id UUID REFERENCES teams(id),
                    game_date DATE NOT NULL,
                    start_time TIME WITH TIME ZONE,
                    end_time TIME WITH TIME ZONE,
                    venue VARCHAR(255),
                    weather_conditions VARCHAR(255),
                    temperature DECIMAL(4,1),
                    wind_speed INTEGER,
                    wind_direction VARCHAR(50),
                    home_score INTEGER DEFAULT 0,
                    away_score INTEGER DEFAULT 0,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS player_stats (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    player_id UUID REFERENCES players(id),
                    game_id UUID REFERENCES games(id),
                    at_bats INTEGER DEFAULT 0,
                    hits INTEGER DEFAULT 0,
                    runs INTEGER DEFAULT 0,
                    rbis INTEGER DEFAULT 0,
                    walks INTEGER DEFAULT 0,
                    strikeouts INTEGER DEFAULT 0,
                    home_runs INTEGER DEFAULT 0,
                    stolen_bases INTEGER DEFAULT 0,
                    errors INTEGER DEFAULT 0,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS pitcher_stats (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    player_id UUID REFERENCES players(id),
                    game_id UUID REFERENCES games(id),
                    innings_pitched DECIMAL(4,1) DEFAULT 0,
                    hits_allowed INTEGER DEFAULT 0,
                    runs_allowed INTEGER DEFAULT 0,
                    earned_runs INTEGER DEFAULT 0,
                    walks_allowed INTEGER DEFAULT 0,
                    strikeouts INTEGER DEFAULT 0,
                    home_runs_allowed INTEGER DEFAULT 0,
                    pitches_thrown INTEGER DEFAULT 0,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                );
            `);
            logger.info('Database tables created successfully');
        } catch (error) {
            logger.error('Error creating database tables:', error);
            throw error;
        }
    }
} 