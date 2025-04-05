<?php
namespace BaseballAnalytics\Api\Controllers;

use BaseballAnalytics\Api\ApiResponse;
use BaseballAnalytics\Api\ApiMiddleware;
use BaseballAnalytics\Auth\UserManager;
use BaseballAnalytics\Auth\SessionManager;
use BaseballAnalytics\Auth\TwoFactorAuth;

class AuthController {
    private $userManager;
    private $sessionManager;
    private $twoFactorAuth;
    private $middleware;

    public function __construct() {
        $this->userManager = UserManager::getInstance();
        $this->sessionManager = SessionManager::getInstance();
        $this->twoFactorAuth = new TwoFactorAuth();
        $this->middleware = new ApiMiddleware();
    }

    public function login(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !$this->middleware->validateRequiredFields($data, ['username', 'password'])) {
            return;
        }

        $user = $this->userManager->authenticateUser($data['username'], $data['password']);
        if (!$user) {
            ApiResponse::unauthorized('Invalid credentials')->send();
            return;
        }

        // Check if 2FA is enabled
        if ($this->twoFactorAuth->isTwoFactorEnabled($user['id'])) {
            $this->sessionManager->setUser($user);
            ApiResponse::success([
                'requires_2fa' => true,
                'user_id' => $user['id']
            ], 'Two-factor authentication required')->send();
            return;
        }

        // Generate session token
        $this->sessionManager->setUser($user);
        $token = $this->sessionManager->setCsrfToken();

        ApiResponse::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ], 'Login successful')->send();
    }

    public function verify2fa(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !$this->middleware->validateRequiredFields($data, ['code', 'user_id'])) {
            return;
        }

        if (!$this->twoFactorAuth->verifyCode($data['user_id'], $data['code'])) {
            ApiResponse::unauthorized('Invalid 2FA code')->send();
            return;
        }

        $user = $this->userManager->getUserById($data['user_id']);
        if (!$user) {
            ApiResponse::notFound('User not found')->send();
            return;
        }

        // Generate session token
        $this->sessionManager->setUser($user);
        $token = $this->sessionManager->setCsrfToken();

        ApiResponse::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ], '2FA verification successful')->send();
    }

    public function register(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !$this->middleware->validateRequiredFields($data, ['username', 'email', 'password'])) {
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            ApiResponse::validationError(['email' => 'Invalid email format'])->send();
            return;
        }

        // Check if email already exists
        if ($this->userManager->getUserByEmail($data['email'])) {
            ApiResponse::validationError(['email' => 'Email already registered'])->send();
            return;
        }

        // Create user
        if (!$this->userManager->createUser($data['username'], $data['email'], $data['password'])) {
            ApiResponse::serverError('Failed to create user')->send();
            return;
        }

        ApiResponse::created(null, 'User registered successfully')->send();
    }

    public function setup2fa(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $user = $this->sessionManager->getUser();
        try {
            $setup = $this->twoFactorAuth->setupTwoFactor($user['id']);
            ApiResponse::success([
                'secret_key' => $setup['secret_key'],
                'backup_codes' => $setup['backup_codes']
            ], '2FA setup initialized')->send();
        } catch (\Exception $e) {
            ApiResponse::serverError('Failed to setup 2FA')->send();
        }
    }

    public function enable2fa(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !$this->middleware->validateRequiredFields($data, ['code'])) {
            return;
        }

        $user = $this->sessionManager->getUser();
        if (!$this->twoFactorAuth->verifyCode($user['id'], $data['code'])) {
            ApiResponse::badRequest('Invalid verification code')->send();
            return;
        }

        if (!$this->twoFactorAuth->enableTwoFactor($user['id'])) {
            ApiResponse::serverError('Failed to enable 2FA')->send();
            return;
        }

        ApiResponse::success(null, '2FA enabled successfully')->send();
    }

    public function disable2fa(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !$this->middleware->validateRequiredFields($data, ['code'])) {
            return;
        }

        $user = $this->sessionManager->getUser();
        if (!$this->twoFactorAuth->verifyCode($user['id'], $data['code'])) {
            ApiResponse::badRequest('Invalid verification code')->send();
            return;
        }

        if (!$this->twoFactorAuth->disableTwoFactor($user['id'])) {
            ApiResponse::serverError('Failed to disable 2FA')->send();
            return;
        }

        ApiResponse::success(null, '2FA disabled successfully')->send();
    }

    public function logout(): void {
        $this->sessionManager->destroy();
        ApiResponse::success(null, 'Logged out successfully')->send();
    }

    public function refreshToken(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $token = $this->sessionManager->setCsrfToken();
        ApiResponse::success(['token' => $token], 'Token refreshed')->send();
    }

    public function getBackupCodes(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $user = $this->sessionManager->getUser();
        $codes = $this->twoFactorAuth->getBackupCodes($user['id']);
        
        ApiResponse::success(['backup_codes' => $codes], 'Backup codes retrieved')->send();
    }

    public function regenerateBackupCodes(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $user = $this->sessionManager->getUser();
        $codes = $this->twoFactorAuth->regenerateBackupCodes($user['id']);
        
        if (empty($codes)) {
            ApiResponse::serverError('Failed to regenerate backup codes')->send();
            return;
        }

        ApiResponse::success(['backup_codes' => $codes], 'Backup codes regenerated')->send();
    }
} 