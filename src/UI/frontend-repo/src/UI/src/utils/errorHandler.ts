
import { AxiosError } from 'axios';
import { toast } from '@/hooks/use-toast';
import { ApiErrorResponse } from '../services/api';

export const handleApiError = (error: AxiosError<ApiErrorResponse>) => {
  let errorMessage = 'An unexpected error occurred';
  
  if (error.response) {
    // Server responded with error
    const { status, data } = error.response;
    
    switch (status) {
      case 401:
        errorMessage = 'Authentication failed. Please log in again.';
        // Handle unauthorized - redirect to login page or refresh token
        localStorage.removeItem('jwt_token');
        window.location.href = '/login';
        break;
      case 403:
        errorMessage = 'You do not have permission to perform this action';
        break;
      case 404:
        errorMessage = 'The requested resource was not found';
        break;
      case 422:
        errorMessage = 'Validation failed';
        if (data?.errors) {
          const firstError = Object.values(data.errors)[0]?.[0];
          if (firstError) {
            errorMessage = firstError;
          }
        }
        break;
      case 500:
        errorMessage = 'Server error. Please try again later';
        break;
      default:
        errorMessage = data?.message || errorMessage;
    }
  } else if (error.request) {
    // Request made but no response
    errorMessage = 'No response received from server. Please check your connection';
  } else {
    // Error in request setup
    errorMessage = error.message || errorMessage;
  }
  
  // Show error toast
  toast({
    title: 'Error',
    description: errorMessage,
    variant: 'destructive',
  });
  
  return Promise.reject(error);
};
