
import React from 'react';
import { Loader2, Info, AlertTriangle } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription } from "@/components/ui/alert";

interface ModelMetricsProps {
  modelMetrics: Record<string, any> | null;
}

export const ModelMetrics: React.FC<ModelMetricsProps> = ({ modelMetrics }) => {
  // Helper function to determine if a metric should be displayed as a percentage
  const isPercentageMetric = (key: string) => {
    return ['accuracy', 'precision', 'recall', 'f1_score', 'r2', 'confidence'].includes(key.toLowerCase());
  };

  // Helper function to get description for common metrics
  const getMetricDescription = (key: string) => {
    const descriptions: Record<string, string> = {
      'accuracy': 'Percentage of correct predictions',
      'precision': 'Ratio of true positives to all predicted positives',
      'recall': 'Ratio of true positives to all actual positives',
      'f1_score': 'Harmonic mean of precision and recall',
      'r2': 'Coefficient of determination (goodness of fit)',
      'mse': 'Mean Squared Error - average of squared differences',
      'rmse': 'Root Mean Squared Error - square root of MSE',
      'mae': 'Mean Absolute Error - average of absolute differences',
      'confidence': 'Model confidence in its predictions'
    };
    
    return descriptions[key.toLowerCase()] || 'Model performance metric';
  };

  // Helper function to get color based on metric value
  const getMetricColor = (key: string, value: number) => {
    const lowerKey = key.toLowerCase();
    
    if (isPercentageMetric(key)) {
      if (value >= 0.9) return "bg-green-500";
      if (value >= 0.7) return "bg-amber-500";
      return "bg-red-500";
    }
    
    // For error metrics (lower is better)
    if (['mse', 'rmse', 'mae'].includes(lowerKey)) {
      // Inverse scale for error metrics (lower is better)
      if (value <= 0.05) return "bg-green-500";
      if (value <= 0.15) return "bg-amber-500";
      return "bg-red-500";
    }
    
    return "bg-blue-500";
  };

  // Get badge variant based on metric value
  const getBadgeVariant = (key: string, value: number): "success" | "warning" | "destructive" | "default" => {
    const lowerKey = key.toLowerCase();
    
    if (isPercentageMetric(key)) {
      if (value >= 0.9) return "success";
      if (value >= 0.7) return "warning";
      return "destructive";
    }
    
    // For error metrics (lower is better)
    if (['mse', 'rmse', 'mae'].includes(lowerKey)) {
      if (value <= 0.05) return "success";
      if (value <= 0.15) return "warning";
      return "destructive";
    }
    
    return "default";
  };

  // Get appropriate badge label based on metric type and value
  const getBadgeLabel = (key: string, value: number): string => {
    const lowerKey = key.toLowerCase();
    
    if (isPercentageMetric(key)) {
      if (value >= 0.9) return "Excellent";
      if (value >= 0.7) return "Good";
      return "Poor";
    }
    
    // For error metrics
    if (['mse', 'rmse', 'mae'].includes(lowerKey)) {
      if (value <= 0.05) return "Low Error";
      if (value <= 0.15) return "Moderate Error";
      return "High Error";
    }
    
    return "Metric";
  };

  if (!modelMetrics) {
    return (
      <div className="space-y-4">
        <h3 className="text-lg font-medium">Model Performance Metrics</h3>
        <div className="flex items-center justify-center p-6">
          <Loader2 className="h-6 w-6 animate-spin mr-2" />
          <span>Loading metrics...</span>
        </div>
      </div>
    );
  }

  if (Object.keys(modelMetrics).length === 0) {
    return (
      <div className="space-y-4">
        <h3 className="text-lg font-medium">Model Performance Metrics</h3>
        <Alert>
          <AlertTriangle className="h-4 w-4" />
          <AlertDescription>
            No metrics available for this model yet. Run a prediction to generate metrics.
          </AlertDescription>
        </Alert>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-medium">Model Performance Metrics</h3>
      
      <div className="space-y-3">
        {Object.entries(modelMetrics).map(([key, value]) => (
          <div key={key} className="p-3 border rounded-md">
            <div className="flex items-center justify-between mb-1">
              <div className="flex items-center">
                <div className="text-sm font-medium text-muted-foreground">{key}</div>
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Info className="h-4 w-4 ml-1 text-muted-foreground cursor-help" />
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>{getMetricDescription(key)}</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              </div>
              
              <div className="flex items-center">
                {typeof value === 'number' && (
                  <Badge variant={getBadgeVariant(key, value)} className="mr-2">
                    {getBadgeLabel(key, value)}
                  </Badge>
                )}
                <div className="text-base font-bold">
                  {typeof value === 'number' ? (
                    isPercentageMetric(key) ? 
                      `${(value * 100).toFixed(2)}%` : 
                      value.toFixed(4)
                  ) : JSON.stringify(value)}
                </div>
              </div>
            </div>
            
            {typeof value === 'number' && (
              <div className="mt-2">
                {isPercentageMetric(key) ? (
                  <Progress 
                    value={value * 100} 
                    className="h-2"
                    indicatorClassName={getMetricColor(key, value)}
                  />
                ) : (
                  <Progress 
                    // Inverse scale for error metrics, capped at max 1.0
                    value={(1 - Math.min(value, 1)) * 100} 
                    className="h-2"
                    indicatorClassName={getMetricColor(key, value)}
                  />
                )}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default ModelMetrics;
