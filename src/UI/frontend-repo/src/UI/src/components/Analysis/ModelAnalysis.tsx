
import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/components/ui/use-toast";
import { Loader2, Download } from "lucide-react";
import { mlbService, ModelAnalysisRequest } from '@/services/mlbService';

interface ModelAnalysisProps {
  className?: string;
}

export const ModelAnalysis: React.FC<ModelAnalysisProps> = ({ className }) => {
  const { toast } = useToast();
  const [analysisType, setAnalysisType] = useState<'team' | 'player' | 'game' | 'ml'>('team');
  const [entityId, setEntityId] = useState<string>('');
  const [loading, setLoading] = useState(false);
  const [results, setResults] = useState<any>(null);

  const handleRunAnalysis = async () => {
    if (!entityId) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please enter an ID to analyze",
      });
      return;
    }

    const id = parseInt(entityId);
    if (isNaN(id)) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "ID must be a number",
      });
      return;
    }

    setLoading(true);

    try {
      const request: ModelAnalysisRequest = {
        type: analysisType,
        id
      };

      const response = await mlbService.runModelAnalysis(request);
      setResults(response);
      
      toast({
        title: "Analysis Complete",
        description: `${analysisType.charAt(0).toUpperCase() + analysisType.slice(1)} analysis completed successfully.`,
      });
    } catch (error) {
      console.error('Analysis error:', error);
      toast({
        variant: "destructive",
        title: "Analysis Failed",
        description: "Could not complete the requested analysis.",
      });
    } finally {
      setLoading(false);
    }
  };

  const handleExport = () => {
    if (!results) return;
    
    // Format the results for export
    const exportData = {
      type: analysisType,
      id: entityId,
      timestamp: results.timestamp,
      modelVersion: results.modelVersion,
      ...results.results
    };
    
    mlbService.exportToExcel([exportData], `${analysisType}-analysis-${entityId}`);
  };

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>Advanced Model Analysis</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="analysis-type">Analysis Type</Label>
              <Select 
                value={analysisType} 
                onValueChange={(value) => setAnalysisType(value as any)}
              >
                <SelectTrigger id="analysis-type">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="team">Team Analysis</SelectItem>
                  <SelectItem value="player">Player Analysis</SelectItem>
                  <SelectItem value="game">Game Analysis</SelectItem>
                  <SelectItem value="ml">ML Prediction</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="entity-id">ID</Label>
              <Input
                id="entity-id"
                type="number"
                placeholder="Enter ID"
                value={entityId}
                onChange={(e) => setEntityId(e.target.value)}
              />
            </div>
          </div>
          
          <Button 
            onClick={handleRunAnalysis} 
            disabled={loading} 
            className="w-full"
          >
            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Run Analysis
          </Button>
          
          {results && (
            <div className="mt-4 border rounded-md p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="font-semibold">Results</h3>
                <span className="text-sm text-muted-foreground">Model v{results.modelVersion}</span>
              </div>
              <div className="text-sm">
                <p className="text-muted-foreground mb-2">Analysis completed at {new Date(results.timestamp).toLocaleString()}</p>
                <pre className="bg-muted p-2 rounded-md overflow-auto max-h-52">
                  {JSON.stringify(results.results, null, 2)}
                </pre>
              </div>
            </div>
          )}
        </div>
      </CardContent>
      {results && (
        <CardFooter className="justify-end">
          <Button variant="outline" onClick={handleExport} className="flex items-center">
            <Download className="w-4 h-4 mr-2" />
            Export Results
          </Button>
        </CardFooter>
      )}
    </Card>
  );
};

export default ModelAnalysis;
