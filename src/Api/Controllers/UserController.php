<?php
namespace BaseballAnalytics\Api\Controllers;

use BaseballAnalytics\Api\ApiResponse;
use BaseballAnalytics\Api\ApiMiddleware;
use BaseballAnalytics\Auth\UserManager;
use BaseballAnalytics\Auth\SessionManager;

class UserController {
    private $userManager;
    private $sessionManager;
    private $middleware;

    public function __construct() {
        $this->userManager = UserManager::getInstance();
        $this->sessionManager = SessionManager::getInstance();
        $this->middleware = new ApiMiddleware();
    }

    public function getProfile(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $user = $this->sessionManager->getUser();
        $profile = $this->userManager->getUserById($user['id']);

        if (!$profile) {
            ApiResponse::notFound('User profile not found')->send();
            return;
        }

        // Remove sensitive data
        unset($profile['password_hash']);
        
        ApiResponse::success([
            'profile' => $profile
        ])->send();
    }

    public function updateProfile(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data) {
            return;
        }

        // Validate allowed fields
        $allowedFields = ['email'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            ApiResponse::badRequest('No valid fields to update')->send();
            return;
        }

        // Validate email if present
        if (isset($updateData['email'])) {
            if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                ApiResponse::validationError(['email' => 'Invalid email format'])->send();
                return;
            }

            // Check if email is already taken
            $existingUser = $this->userManager->getUserByEmail($updateData['email']);
            if ($existingUser && $existingUser['id'] !== $this->sessionManager->getUser()['id']) {
                ApiResponse::validationError(['email' => 'Email already in use'])->send();
                return;
            }
        }

        $user = $this->sessionManager->getUser();
        if (!$this->userManager->updateUser($user['id'], $updateData)) {
            ApiResponse::serverError('Failed to update profile')->send();
            return;
        }

        // Get updated profile
        $updatedProfile = $this->userManager->getUserById($user['id']);
        unset($updatedProfile['password_hash']);

        ApiResponse::success([
            'profile' => $updatedProfile
        ], 'Profile updated successfully')->send();
    }

    public function updatePassword(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !$this->middleware->validateRequiredFields($data, ['current_password', 'new_password'])) {
            return;
        }

        // Validate current password
        $user = $this->sessionManager->getUser();
        $authenticated = $this->userManager->authenticateUser($user['username'], $data['current_password']);
        if (!$authenticated) {
            ApiResponse::unauthorized('Current password is incorrect')->send();
            return;
        }

        // Validate new password
        if (strlen($data['new_password']) < 8) {
            ApiResponse::validationError([
                'new_password' => 'Password must be at least 8 characters long'
            ])->send();
            return;
        }

        // Update password
        if (!$this->userManager->updatePassword($user['id'], $data['new_password'])) {
            ApiResponse::serverError('Failed to update password')->send();
            return;
        }

        ApiResponse::success(null, 'Password updated successfully')->send();
    }

    public function getPreferences(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $user = $this->sessionManager->getUser();
        // TODO: Implement user preferences retrieval
        // For now, return default preferences
        ApiResponse::success([
            'preferences' => [
                'notifications' => true,
                'theme' => 'light',
                'language' => 'en',
                'timezone' => 'UTC'
            ]
        ])->send();
    }

    public function updatePreferences(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data) {
            return;
        }

        $user = $this->sessionManager->getUser();
        // TODO: Implement user preferences update
        // For now, just acknowledge the request
        ApiResponse::success(null, 'Preferences updated successfully')->send();
    }

    public function getActivityLog(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $user = $this->sessionManager->getUser();
        // TODO: Implement activity log retrieval
        // For now, return empty log
        ApiResponse::success([
            'activities' => []
        ])->send();
    }

    public function deleteAccount(): void {
        if (!$this->middleware->validateContentType()) {
            return;
        }

        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !$this->middleware->validateRequiredFields($data, ['password'])) {
            return;
        }

        $user = $this->sessionManager->getUser();
        $authenticated = $this->userManager->authenticateUser($user['username'], $data['password']);
        if (!$authenticated) {
            ApiResponse::unauthorized('Invalid password')->send();
            return;
        }

        if (!$this->userManager->deactivateUser($user['id'])) {
            ApiResponse::serverError('Failed to delete account')->send();
            return;
        }

        $this->sessionManager->destroy();
        ApiResponse::success(null, 'Account deleted successfully')->send();
    }
} 