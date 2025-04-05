import { Router } from 'express';
import { MonitoringController } from './controller';
import { requireAuth, requireRole } from '../Security/authMiddleware';

const router = Router();
const monitoringController = new MonitoringController();

// Protected monitoring routes - require admin role
router.get('/metrics', requireAuth, requireRole('admin'), monitoringController.getMetrics.bind(monitoringController));
router.get('/metrics/history', requireAuth, requireRole('admin'), monitoringController.getMetricsHistory.bind(monitoringController));
router.get('/alerts', requireAuth, requireRole('admin'), monitoringController.getAlerts.bind(monitoringController));
router.delete('/alerts', requireAuth, requireRole('admin'), monitoringController.clearAlerts.bind(monitoringController));

// System health endpoint - accessible to all authenticated users
router.get('/health', requireAuth, monitoringController.getSystemHealth.bind(monitoringController));

export default router; 