
import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { modelApi } from '@/services/apiClient';
import { toast } from '@/hooks/use-toast';
import { config } from '@/config';

type ModelType = 'team' | 'player' | 'game' | 'ml';
type ModelInput = Record<string, any>;

export function useModelAnalysis() {
  const [isModelEnabled] = useState(config.app.isModelEnabled);
  const [modelVersion] = useState(config.app.modelVersion);

  // Get model information
  const { data: modelInfo } = useQuery({
    queryKey: ['modelInfo'],
    queryFn: async () => {
      if (!isModelEnabled) return null;
      const response = await modelApi.getModelInfo('ml');
      return response.data;
    },
    enabled: isModelEnabled,
  });

  // Get model metrics/performance data
  const { data: modelMetrics } = useQuery({
    queryKey: ['modelMetrics'],
    queryFn: async () => {
      if (!isModelEnabled) return null;
      const response = await modelApi.getModelMetrics('ml');
      return response.data;
    },
    enabled: isModelEnabled,
  });

  // Run model prediction
  const predictionMutation = useMutation({
    mutationFn: async ({ modelType, inputData }: { modelType: ModelType, inputData: ModelInput }) => {
      if (!isModelEnabled) {
        throw new Error('Model analysis is disabled in the configuration');
      }
      const response = await modelApi.predict(modelType, inputData);
      return response.data;
    },
    onSuccess: (data) => {
      toast({
        title: "Analysis Complete",
        description: "The model has successfully processed your data",
      });
      return data;
    },
    onError: (error: Error) => {
      toast({
        variant: "destructive",
        title: "Analysis Failed",
        description: error.message || "An error occurred during model analysis",
      });
    },
  });

  return {
    isModelEnabled,
    modelVersion,
    modelInfo,
    modelMetrics,
    runPrediction: predictionMutation.mutate,
    isPredicting: predictionMutation.isPending,
    predictionResult: predictionMutation.data,
    predictionError: predictionMutation.error,
    resetPrediction: predictionMutation.reset,
  };
}

export default useModelAnalysis;
