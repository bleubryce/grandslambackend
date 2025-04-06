
import React from 'react';
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Loader2 } from "lucide-react";

interface AnalysisFormProps {
  analysisType: 'team' | 'player' | 'game' | 'ml';
  setAnalysisType: (type: 'team' | 'player' | 'game' | 'ml') => void;
  entityId: string;
  setEntityId: (id: string) => void;
  isPredicting: boolean;
  handleRunAnalysis: () => void;
}

export const AnalysisForm: React.FC<AnalysisFormProps> = ({
  analysisType,
  setAnalysisType,
  entityId,
  setEntityId,
  isPredicting,
  handleRunAnalysis
}) => {
  return (
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
        disabled={isPredicting || !entityId} 
        className="w-full"
      >
        {isPredicting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
        {isPredicting ? 'Running Analysis...' : 'Run Analysis'}
      </Button>
    </div>
  );
};

export default AnalysisForm;
