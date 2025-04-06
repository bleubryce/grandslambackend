
import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { AlertTriangle, InfoIcon } from "lucide-react";
import { useModelAnalysis } from "@/hooks/useModelAnalysis";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { useToast } from "@/hooks/use-toast";
import AnalysisForm from "./AnalysisForm";
import PredictionResults from "./PredictionResults";
import ModelMetrics from "./ModelMetrics";
import ModelInfo from "./ModelInfo";

export const ModelAnalysisPanel: React.FC<{ className?: string }> = ({ className }) => {
  const [analysisType, setAnalysisType] = useState<'team' | 'player' | 'game' | 'ml'>('team');
  const [entityId, setEntityId] = useState<string>('');
  const [activeTab, setActiveTab] = useState<string>('run');
  const { toast } = useToast();
  
  const { 
    isModelEnabled,
    modelVersion,
    modelInfo,
    modelMetrics,
    runPrediction,
    isPredicting,
    predictionResult,
    predictionError,
  } = useModelAnalysis();

  // Add effect to show toast when tab changes to provide feedback
  useEffect(() => {
    toast({
      title: `Switched to ${activeTab === 'run' ? 'Run Analysis' : activeTab === 'metrics' ? 'Model Metrics' : 'Model Info'} tab`,
      description: "Click on interactive elements to analyze baseball data",
      duration: 2000,
    });
  }, [activeTab, toast]);

  const handleRunAnalysis = () => {
    if (!entityId) {
      toast({
        title: "Input required",
        description: "Please enter an entity ID to analyze",
        variant: "destructive",
      });
      return;
    }
    
    const id = parseInt(entityId);
    if (isNaN(id)) {
      toast({
        title: "Invalid ID",
        description: "Please enter a valid numeric ID",
        variant: "destructive",
      });
      return;
    }
    
    toast({
      title: "Running analysis",
      description: `Analyzing ${analysisType} with ID ${id}`,
    });
    
    runPrediction({ 
      modelType: analysisType,
      inputData: { id, type: analysisType }
    });
  };

  // Handle tab change with clear user feedback
  const handleTabChange = (value: string) => {
    setActiveTab(value);
    console.log(`Tab changed to: ${value}`);
  };

  if (!isModelEnabled) {
    return (
      <Card className={className}>
        <CardHeader>
          <CardTitle>Model Analysis</CardTitle>
        </CardHeader>
        <CardContent>
          <Alert variant="warning">
            <AlertTriangle className="h-4 w-4" />
            <AlertDescription>
              Model analysis is currently disabled in system configuration.
              Please enable it in the environment settings to use this feature.
            </AlertDescription>
          </Alert>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          Advanced Model Analysis
          <InfoIcon 
            className="h-4 w-4 text-muted-foreground cursor-help" 
            onClick={() => toast({
              title: "Advanced Model Analysis",
              description: "Run predictions and view model metrics for baseball analytics",
            })}
          />
        </CardTitle>
      </CardHeader>
      <CardContent>
        <Tabs value={activeTab} onValueChange={handleTabChange}>
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="run" className="active:bg-primary">Run Analysis</TabsTrigger>
            <TabsTrigger value="metrics" className="active:bg-primary">Model Metrics</TabsTrigger>
            <TabsTrigger value="info" className="active:bg-primary">Model Info</TabsTrigger>
          </TabsList>
          
          <TabsContent value="run" className="space-y-4 mt-4">
            <AnalysisForm 
              analysisType={analysisType}
              setAnalysisType={setAnalysisType}
              entityId={entityId}
              setEntityId={setEntityId}
              isPredicting={isPredicting}
              handleRunAnalysis={handleRunAnalysis}
            />
            
            <PredictionResults 
              predictionResult={predictionResult}
              predictionError={predictionError}
              analysisType={analysisType}
              entityId={entityId}
              modelVersion={modelVersion}
            />
          </TabsContent>
          
          <TabsContent value="metrics" className="mt-4">
            <ModelMetrics modelMetrics={modelMetrics} />
          </TabsContent>
          
          <TabsContent value="info" className="mt-4">
            <ModelInfo modelInfo={modelInfo} modelVersion={modelVersion} />
          </TabsContent>
        </Tabs>
      </CardContent>
    </Card>
  );
};

export default ModelAnalysisPanel;
