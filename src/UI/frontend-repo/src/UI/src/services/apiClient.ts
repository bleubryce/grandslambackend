import axios from 'axios';
import { config } from '@/config';
import { handleApiError } from '@/utils/errorHandler';

// Create an axios instance with default configuration
export const apiClient = axios.create({
  baseURL: config.api.baseUrl,
  timeout: config.api.timeout,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add request interceptor to include auth token in requests
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem(config.auth.tokenKey);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add response interceptor to handle common errors
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Clear auth state on unauthorized
      localStorage.removeItem(config.auth.tokenKey);
      localStorage.removeItem('user_data');
      
      // Try to validate token
      const authService = (await import('./authService')).authService;
      const isValid = await authService.validateToken();
      
      if (!isValid) {
        window.location.href = '/login';
      }
    }
    
    return handleApiError(error);
  }
);

// Model-specific API methods
export const modelApi = {
  // Run model prediction with provided data
  predict: async (modelType: string, inputData: any) => {
    return apiClient.post(`${config.api.endpoints.analysis}/${modelType}`, inputData);
  },
  
  // Get model metadata (version, parameters, etc)
  getModelInfo: async (modelType: string) => {
    return apiClient.get(`${config.api.endpoints.analysis}/model-info/${modelType}`);
  },
  
  // Get model performance metrics
  getModelMetrics: async (modelType: string) => {
    return apiClient.get(`${config.api.endpoints.analysis}/metrics/${modelType}`);
  }
};

export default apiClient;
