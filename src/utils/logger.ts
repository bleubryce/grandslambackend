import winston from 'winston';

const createLogger = (name: string) => {
    return winston.createLogger({
        level: 'info',
        defaultMeta: { service: name },
        format: winston.format.combine(
            winston.format.timestamp(),
            winston.format.json()
        ),
        transports: [
            new winston.transports.Console({
                format: winston.format.combine(
                    winston.format.colorize(),
                    winston.format.simple()
                )
            }),
            new winston.transports.File({ 
                filename: 'error.log', 
                level: 'error' 
            }),
            new winston.transports.File({ 
                filename: 'combined.log' 
            })
        ]
    });
};

export { createLogger };
export const logger = createLogger('default'); 