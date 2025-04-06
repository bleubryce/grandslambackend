
import React from 'react';
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { AlertTriangle, Download, CheckCircle2, TrendingUp } from "lucide-react";
import { mlbService } from "@/services/mlbService";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

interface PredictionResultsProps {
  predictionResult: any;
  predictionError: Error | null;
  analysisType: 'team' | 'player' | 'game' | 'ml';
  entityId: string;
  modelVersion: string;
}

export const PredictionResults: React.FC<PredictionResultsProps> = ({
  predictionResult,
  predictionError,
  analysisType,
  entityId,
  modelVersion
}) => {
  const handleExport = () => {
    if (!predictionResult) return;
    
    // Format the results for export
    const exportData = {
      type: analysisType,
      id: entityId,
      modelVersion,
      timestamp: new Date().toISOString(),
      ...predictionResult
    };
    
    mlbService.exportToExcel([exportData], `${analysisType}-analysis-${entityId}`);
  };
  
  // Determine if a result has high confidence
  const getConfidenceLevel = (value: number) => {
    if (value >= 0.8) return { label: "High", color: "text-green-500" };
    if (value >= 0.6) return { label: "Medium", color: "text-amber-500" };
    return { label: "Low", color: "text-red-500" };
  };

  if (predictionError) {
    return (
      <Alert variant="destructive">
        <AlertTriangle className="h-4 w-4" />
        <AlertDescription>
          {predictionError.message || 'An error occurred during analysis'}
        </AlertDescription>
      </Alert>
    );
  }
  
  if (!predictionResult) {
    return null;
  }
  
  // Check if we have factors in the prediction results
  const hasFactors = predictionResult.factors && Array.isArray(predictionResult.factors);
  // Check if we have predictions in a specific format
  const hasPredictions = predictionResult.predictions && typeof predictionResult.predictions === 'object';
  
  return (
    <div className="mt-4 border rounded-md p-4">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h3 className="font-semibold flex items-center">
            <CheckCircle2 className="h-5 w-5 text-green-500 mr-2" />
            Analysis Complete
          </h3>
          <span className="text-sm text-muted-foreground">Model v{modelVersion}</span>
        </div>
        <Badge variant="outline" className="text-sm">
          {analysisType.charAt(0).toUpperCase() + analysisType.slice(1)} Analysis
        </Badge>
      </div>
      
      <Tabs defaultValue="results" className="w-full">
        <TabsList className="grid grid-cols-2">
          <TabsTrigger value="results">Results</TabsTrigger>
          <TabsTrigger value="raw">Raw Data</TabsTrigger>
        </TabsList>
        
        <TabsContent value="results" className="py-2">
          <div className="space-y-4">
            {predictionResult.confidence && (
              <Card className="overflow-hidden">
                <CardContent className="p-0">
                  <div className="p-4">
                    <h4 className="text-sm font-medium mb-1">Confidence Level</h4>
                    <div className="flex items-center">
                      <div className="text-xl font-bold">
                        {(predictionResult.confidence * 100).toFixed(1)}%
                      </div>
                      <Badge variant="outline" className={`ml-2 ${getConfidenceLevel(predictionResult.confidence).color}`}>
                        {getConfidenceLevel(predictionResult.confidence).label}
                      </Badge>
                    </div>
                  </div>
                  <div className="bg-muted h-2">
                    <div 
                      className="bg-primary h-full" 
                      style={{ width: `${predictionResult.confidence * 100}%` }}
                    ></div>
                  </div>
                </CardContent>
              </Card>
            )}
            
            {hasPredictions && (
              <div>
                <h4 className="text-sm font-medium mb-2">Predictions</h4>
                <div className="grid grid-cols-2 gap-2">
                  {Object.entries(predictionResult.predictions).map(([key, value]) => (
                    <div key={key} className="p-2 border rounded-md">
                      <div className="text-xs text-muted-foreground">{key}</div>
                      <div className="text-base font-semibold flex items-center">
                        {typeof value === 'number' ? value.toFixed(3) : String(value)}
                        <TrendingUp className="h-3 w-3 ml-1 text-green-500" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
            
            {hasFactors && (
              <div>
                <h4 className="text-sm font-medium mb-2">Contributing Factors</h4>
                <div className="flex flex-wrap gap-2">
                  {predictionResult.factors.map((factor: string, index: number) => (
                    <Badge key={index} variant="secondary">{factor}</Badge>
                  ))}
                </div>
              </div>
            )}
          </div>
        </TabsContent>
        
        <TabsContent value="raw">
          <div className="text-sm">
            <p className="text-muted-foreground mb-2">Analysis completed at {new Date().toLocaleString()}</p>
            <pre className="bg-muted p-2 rounded-md overflow-auto max-h-52 text-xs">
              {JSON.stringify(predictionResult, null, 2)}
            </pre>
          </div>
        </TabsContent>
      </Tabs>
      
      <div className="mt-4 flex justify-end">
        <Button variant="outline" onClick={handleExport} className="flex items-center">
          <Download className="w-4 h-4 mr-2" />
          Export Results
        </Button>
      </div>
    </div>
  );
};

export default PredictionResults;
