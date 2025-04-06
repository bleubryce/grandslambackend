import { apiClient } from './apiClient';
import { config } from '@/config';
import { ApiResponse } from './types';

export interface ModelParameters {
  learningRate?: number;
  epochs?: number;
  batchSize?: number;
  layers?: number[];
  activationFunction?: string;
}

export interface ModelMetrics {
  accuracy: number;
  loss: number;
  precision: number;
  recall: number;
  f1Score: number;
  timestamp: string;
}

export interface PredictionResult {
  prediction: number | number[];
  confidence: number;
  metadata: Record<string, any>;
}

export interface TrainingProgress {
  epoch: number;
  loss: number;
  accuracy: number;
  validationLoss?: number;
  validationAccuracy?: number;
}

class ModelService {
  // Model Training
  async trainModel(modelType: string, data: any, parameters: ModelParameters) {
    return apiClient.post<ApiResponse<ModelMetrics>>(
      `${config.api.endpoints.analysis}/train/${modelType}`,
      { data, parameters }
    );
  }

  // Real-time Training Progress
  subscribeToTrainingProgress(modelType: string, callback: (progress: TrainingProgress) => void) {
    const ws = new WebSocket(`${config.api.wsEndpoint}/model/training/${modelType}`);
    
    ws.onmessage = (event) => {
      const progress: TrainingProgress = JSON.parse(event.data);
      callback(progress);
    };

    return () => ws.close();
  }

  // Model Prediction
  async predict(modelType: string, inputData: any): Promise<ApiResponse<PredictionResult>> {
    return apiClient.post(`${config.api.endpoints.analysis}/predict/${modelType}`, inputData);
  }

  // Batch Predictions
  async batchPredict(modelType: string, inputDataArray: any[]): Promise<ApiResponse<PredictionResult[]>> {
    return apiClient.post(`${config.api.endpoints.analysis}/batch-predict/${modelType}`, inputDataArray);
  }

  // Model Performance Metrics
  async getModelMetrics(modelType: string, timeRange?: { start: Date; end: Date }) {
    return apiClient.get<ApiResponse<ModelMetrics[]>>(
      `${config.api.endpoints.analysis}/metrics/${modelType}`,
      { params: timeRange }
    );
  }

  // Model Version Management
  async getModelVersions(modelType: string) {
    return apiClient.get(`${config.api.endpoints.analysis}/versions/${modelType}`);
  }

  async switchModelVersion(modelType: string, version: string) {
    return apiClient.post(`${config.api.endpoints.analysis}/switch-version/${modelType}`, { version });
  }

  // Model Parameters
  async updateModelParameters(modelType: string, parameters: ModelParameters) {
    return apiClient.put(`${config.api.endpoints.analysis}/parameters/${modelType}`, parameters);
  }

  async getModelParameters(modelType: string) {
    return apiClient.get<ApiResponse<ModelParameters>>(`${config.api.endpoints.analysis}/parameters/${modelType}`);
  }

  // Data Visualization
  async getFeatureImportance(modelType: string) {
    return apiClient.get(`${config.api.endpoints.analysis}/feature-importance/${modelType}`);
  }

  async getPredictionDistribution(modelType: string) {
    return apiClient.get(`${config.api.endpoints.analysis}/prediction-distribution/${modelType}`);
  }

  // Model Health Check
  async checkModelHealth(modelType: string) {
    return apiClient.get(`${config.api.endpoints.analysis}/health/${modelType}`);
  }

  // Export Model Results
  async exportResults(modelType: string, format: 'csv' | 'json' | 'xlsx') {
    return apiClient.get(`${config.api.endpoints.analysis}/export/${modelType}`, {
      params: { format },
      responseType: 'blob'
    });
  }
}

export const modelService = new ModelService(); 