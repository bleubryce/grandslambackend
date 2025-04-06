
import React from 'react';
import { CheckCircle, Info } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

interface ModelInfoProps {
  modelInfo: Record<string, any> | null;
  modelVersion: string;
}

export const ModelInfo: React.FC<ModelInfoProps> = ({ modelInfo, modelVersion }) => {
  // Function to format model information values
  const formatValue = (key: string, value: any): React.ReactNode => {
    if (typeof value === 'object') {
      return (
        <div className="text-sm bg-muted p-1 rounded-md max-h-24 overflow-auto">
          <pre className="text-xs">{JSON.stringify(value, null, 2)}</pre>
        </div>
      );
    }
    
    // Return formatted value based on key
    switch (key) {
      case 'training_date':
      case 'updated':
      case 'created':
        try {
          return new Date(value).toLocaleDateString();
        } catch (e) {
          return String(value);
        }
      default:
        return String(value);
    }
  };
  
  // Determine if a key is important to highlight
  const isImportantKey = (key: string): boolean => {
    return ['algorithm', 'framework', 'type', 'status', 'accuracy'].includes(key);
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-medium">Model Information</h3>
      
      <div className="grid grid-cols-2 gap-4">
        <div className="p-3 border rounded-md">
          <div className="text-sm font-medium text-muted-foreground">Version</div>
          <div className="text-2xl font-bold">{modelVersion}</div>
        </div>
        
        <div className="p-3 border rounded-md">
          <div className="text-sm font-medium text-muted-foreground">Status</div>
          <div className="flex items-center">
            <CheckCircle className="h-5 w-5 text-green-500 mr-2" />
            <span className="text-2xl font-bold">Active</span>
          </div>
        </div>
        
        {modelInfo && Object.entries(modelInfo).map(([key, value]) => (
          key !== 'version' && (
            <div key={key} className="p-3 border rounded-md">
              <div className="flex justify-between items-center">
                <div className="flex items-center text-sm font-medium text-muted-foreground">
                  {key}
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <Info className="h-4 w-4 ml-1 text-muted-foreground cursor-help" />
                      </TooltipTrigger>
                      <TooltipContent>
                        <p>Information about model {key}</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                
                {isImportantKey(key) && (
                  <Badge variant="outline" className="ml-2">
                    {String(value).toUpperCase()}
                  </Badge>
                )}
              </div>
              <div className="text-base font-semibold mt-1">
                {formatValue(key, value)}
              </div>
            </div>
          )
        ))}
      </div>
    </div>
  );
};

export default ModelInfo;
