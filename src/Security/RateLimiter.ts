import Redis from 'ioredis';
import { logger } from '../utils/logger';

interface RateLimitConfig {
    windowMs: number;
    maxRequests: number;
}

export class RateLimiter {
    private redis: Redis;
    private windowMs: number;
    private maxRequests: number;

    constructor(config: RateLimitConfig) {
        this.redis = new Redis();
        this.windowMs = config.windowMs;
        this.maxRequests = config.maxRequests;
    }

    private getKey(ip: string): string {
        return `ratelimit:${ip}`;
    }

    async isLimited(ip: string): Promise<boolean> {
        const key = this.getKey(ip);
        try {
            const count = await this.redis.get(key);
            if (!count) return false;
            return parseInt(count, 10) >= this.maxRequests;
        } catch (error) {
            logger.error('Rate limiter error:', error);
            return false; // Fail open on Redis errors
        }
    }

    async increment(ip: string): Promise<void> {
        const key = this.getKey(ip);
        try {
            const multi = this.redis.multi();
            multi.incr(key);
            multi.pexpire(key, this.windowMs);
            await multi.exec();
        } catch (error) {
            logger.error('Rate limiter increment error:', error);
        }
    }

    async getRemainingRequests(ip: string): Promise<number> {
        const key = this.getKey(ip);
        try {
            const count = await this.redis.get(key);
            if (!count) return this.maxRequests;
            return Math.max(0, this.maxRequests - parseInt(count, 10));
        } catch (error) {
            logger.error('Rate limiter get remaining error:', error);
            return 0;
        }
    }

    async reset(ip: string): Promise<void> {
        const key = this.getKey(ip);
        try {
            await this.redis.del(key);
        } catch (error) {
            logger.error('Rate limiter reset error:', error);
        }
    }
} 