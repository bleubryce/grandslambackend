import { apiClient } from './apiClient';
import { config } from '@/config';
import { ApiResponse, ModelMetrics, ModelParameters, PredictionResult } from './types';

class ModelService {
  // Model Training
  async trainModel(modelType: string, data: any, parameters: ModelParameters) {
    try {
      const response = await apiClient.post<ApiResponse<ModelMetrics>>(
        `${config.api.endpoints.model}/train/${modelType}`,
        { data, parameters }
      );
      return response.data;
    } catch (error) {
      console.error('Error training model:', error);
      throw error;
    }
  }

  // Model Prediction
  async predict(modelType: string, inputData: any): Promise<ApiResponse<PredictionResult>> {
    try {
      const response = await apiClient.post(
        `${config.api.endpoints.model}/predict/${modelType}`,
        inputData
      );
      return response.data;
    } catch (error) {
      console.error('Error making prediction:', error);
      throw error;
    }
  }

  // Model Performance Metrics
  async getModelMetrics(modelType: string, timeRange?: { start: Date; end: Date }) {
    try {
      const response = await apiClient.get<ApiResponse<ModelMetrics[]>>(
        `${config.api.endpoints.model}/metrics/${modelType}`,
        { params: timeRange }
      );
      return response.data;
    } catch (error) {
      console.error('Error getting model metrics:', error);
      throw error;
    }
  }

  // Get Model Parameters
  async getModelParameters(modelType: string) {
    try {
      const response = await apiClient.get<ApiResponse<ModelParameters>>(
        `${config.api.endpoints.model}/parameters/${modelType}`
      );
      return response.data;
    } catch (error) {
      console.error('Error getting model parameters:', error);
      throw error;
    }
  }

  // Check Model Health
  async checkModelHealth(): Promise<boolean> {
    try {
      const response = await apiClient.get(`${config.api.endpoints.model}/health`);
      return response.data.status === 'healthy';
    } catch (error) {
      console.error('Error checking model health:', error);
      return false;
    }
  }

  // Get Model Version
  async getModelVersion(): Promise<string> {
    try {
      const response = await apiClient.get(`${config.api.endpoints.model}/version`);
      return response.data.version;
    } catch (error) {
      console.error('Error getting model version:', error);
      throw error;
    }
  }
}

export const modelService = new ModelService(); 