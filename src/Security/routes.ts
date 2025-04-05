import { Router } from 'express';
import { SecurityController } from './controller';

const router: Router = Router();
const securityController = new SecurityController();

// Authentication routes
router.post('/login', securityController.login.bind(securityController));
router.post('/register', securityController.register.bind(securityController));
router.get('/validate-token', securityController.validateToken.bind(securityController));

export default router; 