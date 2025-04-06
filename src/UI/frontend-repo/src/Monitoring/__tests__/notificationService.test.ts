import { NotificationService, NotificationConfig } from '../notificationService';
import { Alert } from '../service';
import nodemailer from 'nodemailer';
import { WebClient } from '@slack/web-api';

jest.mock('nodemailer');
jest.mock('@slack/web-api');

describe('NotificationService', () => {
  let notificationService: NotificationService;
  let mockConfig: NotificationConfig;
  let mockAlert: Alert;

  beforeEach(() => {
    // Reset singleton instance
    NotificationService.resetInstance();

    // Reset mocks
    jest.clearAllMocks();

    // Mock nodemailer
    const mockTransporter = {
      verify: jest.fn().mockResolvedValue(true),
      sendMail: jest.fn().mockResolvedValue({ messageId: 'test-id' }),
    };
    (nodemailer.createTransport as jest.Mock).mockReturnValue(mockTransporter);

    // Mock Slack WebClient
    const mockSlackClient = {
      chat: {
        postMessage: jest.fn().mockResolvedValue({ ok: true }),
      },
    };
    (WebClient as unknown as jest.Mock).mockImplementation(() => mockSlackClient);

    mockConfig = {
      email: {
        enabled: true,
        smtp: {
          host: 'smtp.test.com',
          port: 587,
          secure: false,
          auth: {
            user: 'test@test.com',
            pass: 'password',
          },
        },
        recipients: ['admin@test.com'],
      },
      slack: {
        enabled: true,
        token: 'test-token',
        channel: 'test-channel',
      },
    };

    mockAlert = {
      id: 'test-alert',
      type: 'critical',
      message: 'Test alert message',
      timestamp: Date.now(),
      metrics: {
        cpu: { usage: 90, loadAverage: [2, 2, 2] },
        memory: { total: 1000, used: 900, free: 100, percentUsed: 90 },
        requests: { total: 100, successful: 80, failed: 20, avgResponseTime: 500 },
      },
    };

    notificationService = NotificationService.getInstance(mockConfig);
  });

  afterEach(() => {
    NotificationService.resetInstance();
  });

  describe('Initialization', () => {
    it('should initialize email service when enabled', async () => {
      const transporter = nodemailer.createTransport();
      expect(nodemailer.createTransport).toHaveBeenCalledWith(mockConfig.email?.smtp);
      expect(transporter.verify).toHaveBeenCalled();
    });

    it('should initialize slack service when enabled', () => {
      expect(WebClient).toHaveBeenCalledWith(mockConfig.slack?.token);
    });

    it('should handle email service initialization failure', async () => {
      const mockTransporter = {
        verify: jest.fn().mockRejectedValue(new Error('SMTP error')),
        // Intentionally missing sendMail method to simulate initialization failure
      };
      (nodemailer.createTransport as jest.Mock).mockReturnValue(mockTransporter);

      notificationService = NotificationService.getInstance({
        ...mockConfig,
        email: { ...mockConfig.email!, enabled: true },
      });

      await expect(notificationService.sendNotification(mockAlert)).resolves.not.toThrow();
      // Verify that no email notification was sent since initialization failed
      expect(mockTransporter).not.toHaveProperty('sendMail');
    });

    it('should handle slack service initialization failure', async () => {
      const mockFailedClient = {
        // Intentionally missing chat.postMessage method to simulate initialization failure
        chat: {},
      };
      (WebClient as unknown as jest.Mock).mockImplementation(() => mockFailedClient);

      notificationService = NotificationService.getInstance({
        ...mockConfig,
        slack: { ...mockConfig.slack!, enabled: true },
      });

      await expect(notificationService.sendNotification(mockAlert)).resolves.not.toThrow();
      // Verify that no Slack notification was sent since initialization failed
      expect(mockFailedClient.chat).not.toHaveProperty('postMessage');
    });
  });

  describe('Notification Sending', () => {
    it('should send both email and slack notifications for critical alerts', async () => {
      await notificationService.sendNotification(mockAlert);

      // Check email was sent
      const emailTransporter = nodemailer.createTransport();
      expect(emailTransporter.sendMail).toHaveBeenCalledWith({
        from: mockConfig.email!.smtp.auth.user,
        to: mockConfig.email!.recipients,
        subject: expect.stringContaining(mockAlert.type.toUpperCase()),
        html: expect.stringContaining(mockAlert.message),
      });

      // Check Slack message was sent
      const slackClient = new WebClient();
      expect(slackClient.chat.postMessage).toHaveBeenCalledWith({
        channel: mockConfig.slack!.channel,
        text: expect.stringContaining(mockAlert.type.toUpperCase()),
        blocks: expect.arrayContaining([
          expect.objectContaining({
            type: 'section',
            text: expect.objectContaining({
              type: 'mrkdwn',
              text: expect.stringContaining(mockAlert.message),
            }),
          }),
        ]),
      });
    });

    it('should send notifications only for error and critical alerts', async () => {
      const warningAlert: Alert = { ...mockAlert, type: 'warning' };
      await notificationService.sendNotification(warningAlert);

      // Check no notifications were sent for warning
      const emailTransporter = nodemailer.createTransport();
      const slackClient = new WebClient();
      expect(emailTransporter.sendMail).not.toHaveBeenCalled();
      expect(slackClient.chat.postMessage).not.toHaveBeenCalled();
    });

    it('should handle email notification failure', async () => {
      const mockTransporter = {
        verify: jest.fn().mockResolvedValue(true),
        sendMail: jest.fn().mockRejectedValue(new Error('SMTP error')),
      };
      (nodemailer.createTransport as jest.Mock).mockReturnValue(mockTransporter);

      // Recreate service with new mock
      notificationService = NotificationService.getInstance(mockConfig);
      await notificationService.sendNotification(mockAlert);

      // Slack notification should still be sent
      const slackClient = new WebClient();
      expect(slackClient.chat.postMessage).toHaveBeenCalled();
    });

    it('should handle slack notification failure', async () => {
      const mockSlackClient = {
        chat: {
          postMessage: jest.fn().mockRejectedValue(new Error('Slack error')),
        },
      };
      (WebClient as unknown as jest.Mock).mockImplementation(() => mockSlackClient);

      // Recreate service with new mock
      notificationService = NotificationService.getInstance(mockConfig);
      await notificationService.sendNotification(mockAlert);

      // Email notification should still be sent
      const emailTransporter = nodemailer.createTransport();
      expect(emailTransporter.sendMail).toHaveBeenCalled();
    });
  });

  describe('Message Formatting', () => {
    it('should format email message correctly', async () => {
      await notificationService.sendNotification(mockAlert);

      const emailTransporter = nodemailer.createTransport();
      const emailCall = (emailTransporter.sendMail as jest.Mock).mock.calls[0][0];

      expect(emailCall.subject).toContain(mockAlert.type.toUpperCase());
      expect(emailCall.html).toContain(mockAlert.message);
      expect(emailCall.html).toContain(mockAlert.metrics.cpu!.usage.toString());
      expect(emailCall.html).toContain(mockAlert.metrics.memory!.percentUsed.toString());
    });

    it('should format slack message correctly', async () => {
      await notificationService.sendNotification(mockAlert);

      const slackClient = new WebClient();
      const slackCall = (slackClient.chat.postMessage as jest.Mock).mock.calls[0][0];

      expect(slackCall.text).toContain(mockAlert.type.toUpperCase());
      expect(slackCall.text).toContain(mockAlert.message);
      expect(slackCall.text).toContain(mockAlert.metrics.cpu!.usage.toString());
      expect(slackCall.text).toContain(mockAlert.metrics.memory!.percentUsed.toString());
    });

    it('should handle missing metrics in formatting', async () => {
      const alertWithoutMetrics: Alert = {
        ...mockAlert,
        metrics: {},
      };

      await notificationService.sendNotification(alertWithoutMetrics);

      // Should not throw errors when metrics are missing
      const emailTransporter = nodemailer.createTransport();
      const slackClient = new WebClient();
      expect(emailTransporter.sendMail).toHaveBeenCalled();
      expect(slackClient.chat.postMessage).toHaveBeenCalled();
    });
  });
}); 