import { Alert } from './service';
import { createLogger } from '../utils/logger';
import nodemailer from 'nodemailer';
import { WebClient } from '@slack/web-api';

const logger = createLogger('notification-service');

export interface NotificationConfig {
  email?: {
    enabled: boolean;
    smtp: {
      host: string;
      port: number;
      secure: boolean;
      auth: {
        user: string;
        pass: string;
      };
    };
    recipients: string[];
  };
  slack?: {
    enabled: boolean;
    token: string;
    channel: string;
  };
}

export class NotificationService {
  private static instance: NotificationService;
  private emailTransporter: nodemailer.Transporter | null = null;
  private slackClient: WebClient | null = null;
  private config: NotificationConfig;

  private constructor(config: NotificationConfig) {
    this.config = config;
    this.initializeServices();
  }

  static getInstance(config?: NotificationConfig): NotificationService {
    if (!NotificationService.instance && config) {
      NotificationService.instance = new NotificationService(config);
    } else if (config) {
      // If instance exists but new config is provided, update the config
      NotificationService.instance.config = config;
      NotificationService.instance.initializeServices();
    }
    return NotificationService.instance;
  }

  // For testing purposes only
  static resetInstance(): void {
    NotificationService.instance = undefined as any;
  }

  private async initializeServices(): Promise<void> {
    if (this.config.email?.enabled) {
      try {
        const transporter = nodemailer.createTransport(this.config.email.smtp);
        await transporter.verify();
        this.emailTransporter = transporter;
        logger.info('Email notification service initialized');
      } catch (error) {
        logger.error('Failed to initialize email service:', error);
        this.emailTransporter = null;
      }
    }

    if (this.config.slack?.enabled) {
      try {
        const client = new WebClient(this.config.slack.token);
        // Test the connection by checking if the client has the required methods
        if (!client || !client.chat || typeof client.chat.postMessage !== 'function') {
          throw new Error('Failed to initialize Slack client: missing required methods');
        }
        this.slackClient = client;
        logger.info('Slack notification service initialized');
      } catch (error) {
        logger.error('Failed to initialize Slack service:', error);
        this.slackClient = null;
      }
    }
  }

  async sendNotification(alert: Alert): Promise<void> {
    const promises: Promise<void>[] = [];

    if (this.shouldNotify(alert)) {
      if (this.emailTransporter && this.config.email?.enabled) {
        promises.push(this.sendEmailNotification(alert).catch(error => {
          logger.error('Failed to send email notification:', error);
        }));
      }

      if (this.slackClient && this.config.slack?.enabled) {
        promises.push(this.sendSlackNotification(alert).catch(error => {
          logger.error('Failed to send Slack notification:', error);
        }));
      }
    }

    await Promise.all(promises);
  }

  private shouldNotify(alert: Alert): boolean {
    // Only notify for error and critical alerts
    return ['error', 'critical'].includes(alert.type);
  }

  private async sendEmailNotification(alert: Alert): Promise<void> {
    if (!this.emailTransporter || !this.config.email?.recipients.length) {
      throw new Error('Email service not properly configured');
    }

    const subject = `[${alert.type.toUpperCase()}] System Alert: ${alert.message}`;
    const html = this.formatEmailBody(alert);

    await this.emailTransporter.sendMail({
      from: this.config.email.smtp.auth.user,
      to: this.config.email.recipients,
      subject,
      html,
    });
    logger.info('Email notification sent', { alertId: alert.id });
  }

  private async sendSlackNotification(alert: Alert): Promise<void> {
    if (!this.slackClient || !this.config.slack?.channel) {
      throw new Error('Slack service not properly configured');
    }

    const message = this.formatSlackMessage(alert);

    await this.slackClient.chat.postMessage({
      channel: this.config.slack.channel,
      text: message,
      blocks: [
        {
          type: 'section',
          text: {
            type: 'mrkdwn',
            text: message,
          },
        },
      ],
    });
    logger.info('Slack notification sent', { alertId: alert.id });
  }

  private formatEmailBody(alert: Alert): string {
    return `
      <h2>System Alert</h2>
      <p><strong>Type:</strong> ${alert.type}</p>
      <p><strong>Message:</strong> ${alert.message}</p>
      <p><strong>Time:</strong> ${new Date(alert.timestamp).toLocaleString()}</p>
      <h3>System Metrics</h3>
      <ul>
        ${alert.metrics.cpu ? `<li>CPU Usage: ${alert.metrics.cpu.usage}%</li>` : ''}
        ${alert.metrics.memory ? `<li>Memory Usage: ${alert.metrics.memory.percentUsed}%</li>` : ''}
        ${alert.metrics.requests ? `
          <li>Request Stats:
            <ul>
              <li>Total: ${alert.metrics.requests.total}</li>
              <li>Failed: ${alert.metrics.requests.failed}</li>
              <li>Avg Response Time: ${alert.metrics.requests.avgResponseTime}ms</li>
            </ul>
          </li>
        ` : ''}
      </ul>
    `;
  }

  private formatSlackMessage(alert: Alert): string {
    const metrics = [
      alert.metrics.cpu ? `CPU Usage: ${alert.metrics.cpu.usage}%` : null,
      alert.metrics.memory ? `Memory Usage: ${alert.metrics.memory.percentUsed}%` : null,
      alert.metrics.requests ? `Failed Requests: ${alert.metrics.requests.failed}` : null,
    ].filter(Boolean).join(' | ');

    return `
🚨 *${alert.type.toUpperCase()} Alert*
*Message:* ${alert.message}
*Time:* ${new Date(alert.timestamp).toLocaleString()}
*Metrics:* ${metrics}
    `.trim();
  }
} 