
import { useQuery } from '@tanstack/react-query';
import { healthService } from '../services/healthService';
import { config } from '../config';

export const useHealthCheck = () => {
  // Check backend API health
  const backendHealth = useQuery({
    queryKey: ['health', 'backend'],
    queryFn: healthService.checkBackendHealth,
    staleTime: 60000, // 1 minute
    refetchOnWindowFocus: true,
    retry: 2,
  });

  // Check WebSocket server health
  const websocketHealth = useQuery({
    queryKey: ['health', 'websocket'],
    queryFn: healthService.checkWebSocketHealth,
    staleTime: 60000, // 1 minute
    refetchOnWindowFocus: true,
    retry: 2,
  });

  // Check ML model availability
  const modelHealth = useQuery({
    queryKey: ['health', 'model'],
    queryFn: healthService.checkModelHealth,
    staleTime: 60000, // 1 minute
    refetchOnWindowFocus: true,
    retry: 2,
    enabled: config.app.isModelEnabled,
  });

  // Check environment configuration
  const envConfig = healthService.checkEnvironmentConfig();

  return {
    backendHealth: backendHealth.data?.healthy || false,
    backendStatus: backendHealth.status,
    backendMessage: backendHealth.data?.message || 'Checking backend health...',
    
    websocketHealth: websocketHealth.data?.healthy || false,
    websocketStatus: websocketHealth.status,
    websocketMessage: websocketHealth.data?.message || 'Checking WebSocket health...',
    
    modelHealth: config.app.isModelEnabled ? (modelHealth.data?.healthy || false) : null,
    modelStatus: config.app.isModelEnabled ? modelHealth.status : 'disabled',
    modelMessage: config.app.isModelEnabled 
      ? (modelHealth.data?.message || 'Checking ML model health...') 
      : 'ML model is disabled in configuration',
    
    environmentHealth: envConfig.healthy,
    environmentMessage: envConfig.message,
    
    isLoading: backendHealth.isLoading || websocketHealth.isLoading || (config.app.isModelEnabled && modelHealth.isLoading),
    isError: backendHealth.isError || websocketHealth.isError || (config.app.isModelEnabled && modelHealth.isError),
    
    refetch: () => {
      backendHealth.refetch();
      websocketHealth.refetch();
      if (config.app.isModelEnabled) {
        modelHealth.refetch();
      }
    },
  };
};

export default useHealthCheck;
