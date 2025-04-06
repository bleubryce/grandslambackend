
import { Router } from 'express';
import { SecurityController } from './controller';
import { requireAuth } from './authMiddleware';

const router = Router();
const securityController = new SecurityController();

// Authentication routes
router.post('/login', securityController.login.bind(securityController));
router.post('/register', securityController.register.bind(securityController));
router.get('/validate', securityController.validateToken.bind(securityController));
router.get('/me', requireAuth, securityController.getCurrentUser.bind(securityController));
router.post('/logout', requireAuth, (req, res) => {
  // In a stateless JWT authentication system, the client simply discards the token
  // Server-side we just return a success response
  res.status(200).json({ 
    success: true, 
    message: 'Logged out successfully' 
  });
});

export default router;
