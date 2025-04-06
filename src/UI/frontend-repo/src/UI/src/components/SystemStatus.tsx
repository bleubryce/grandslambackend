
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { RefreshCcw } from "lucide-react";
import { useHealthCheck } from "@/hooks/useHealthCheck";
import { config } from "@/config";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

export const SystemStatus = () => {
  const {
    backendHealth,
    backendMessage,
    websocketHealth,
    websocketMessage,
    modelHealth,
    modelMessage,
    environmentHealth,
    environmentMessage,
    isLoading,
    refetch,
  } = useHealthCheck();

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>System Status</CardTitle>
        <CardDescription>
          Current status of the baseball analytics system
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex items-center justify-between">
          <span className="text-sm font-medium">Backend API:</span>
          <Badge
            variant={backendHealth ? "default" : "destructive"}
            className="ml-auto"
          >
            {backendHealth ? "Healthy" : "Unhealthy"}
          </Badge>
        </div>
        <div className="text-xs text-muted-foreground">{backendMessage}</div>

        <div className="flex items-center justify-between">
          <span className="text-sm font-medium">WebSocket Server:</span>
          <Badge
            variant={websocketHealth ? "default" : "destructive"}
            className="ml-auto"
          >
            {websocketHealth ? "Connected" : "Disconnected"}
          </Badge>
        </div>
        <div className="text-xs text-muted-foreground">{websocketMessage}</div>

        {config.app.isModelEnabled && (
          <>
            <div className="flex items-center justify-between">
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <span className="text-sm font-medium cursor-help">ML Model (v{config.app.modelVersion}):</span>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Machine Learning model for baseball analytics predictions</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
              <Badge
                variant={modelHealth ? "default" : "destructive"}
                className="ml-auto"
              >
                {modelHealth ? "Available" : "Unavailable"}
              </Badge>
            </div>
            <div className="text-xs text-muted-foreground">{modelMessage}</div>
          </>
        )}

        <div className="flex items-center justify-between">
          <span className="text-sm font-medium">Environment:</span>
          <Badge
            variant={environmentHealth ? "default" : "destructive"}
            className="ml-auto"
          >
            {environmentHealth ? "Configured" : "Missing Config"}
          </Badge>
        </div>
        <div className="text-xs text-muted-foreground">
          {environmentMessage}
        </div>
      </CardContent>
      <CardFooter>
        <Button
          variant="outline"
          size="sm"
          onClick={() => refetch()}
          disabled={isLoading}
          className="w-full"
        >
          <RefreshCcw className="mr-2 h-4 w-4" />
          {isLoading ? "Checking..." : "Check Status"}
        </Button>
      </CardFooter>
    </Card>
  );
};

export default SystemStatus;
