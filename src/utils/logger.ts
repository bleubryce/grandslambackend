import winston from 'winston';
const { format } = winston;
const { combine, timestamp, label, printf } = format;

const logFormat = printf((info) => {
  const { level, message, label: logLabel, timestamp: ts, ...rest } = info;
  const metadata = Object.keys(rest).length ? `\n${JSON.stringify(rest, null, 2)}` : '';
  return `${ts} [${logLabel || 'app'}] ${level}: ${message}${metadata}`;
});

export const createLogger = (component: string) => {
  return winston.createLogger({
    level: process.env.NODE_ENV === 'production' ? 'info' : 'debug',
    format: combine(
      label({ label: component }),
      timestamp(),
      logFormat
    ),
    transports: [
      new winston.transports.Console({
        format: combine(
          format.colorize(),
          logFormat
        ),
      }),
      new winston.transports.File({
        filename: 'logs/error.log',
        level: 'error',
      }),
      new winston.transports.File({
        filename: 'logs/combined.log',
      }),
    ],
  });
}; 