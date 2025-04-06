
import React from 'react';
import { Helmet } from 'react-helmet-async';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import PerformanceChart from '@/components/Dashboard/PerformanceChart';
import { Separator } from '@/components/ui/separator';
import ModelAnalysis from '@/components/Analysis/ModelAnalysis';

const Performance = () => {
  return (
    <div className="container px-6 py-8 mx-auto">
      <Helmet>
        <title>Performance Analysis | Baseball Analytics</title>
      </Helmet>

      <div className="flex flex-col space-y-2">
        <h1 className="text-3xl font-bold tracking-tight">Performance Analysis</h1>
        <p className="text-muted-foreground">
          View detailed performance metrics and analysis over time.
        </p>
      </div>

      <Separator className="my-6" />

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <ModelAnalysis className="col-span-1 md:col-span-2" />
        
        <Card className="col-span-1 md:col-span-2">
          <CardHeader>
            <CardTitle>Performance Breakdown</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-muted-foreground mb-4">
              Detailed breakdown of key performance indicators across different categories.
            </p>
            <div className="space-y-4">
              <div>
                <h3 className="font-medium">Batting Performance</h3>
                <p className="text-sm text-muted-foreground">
                  Team batting average has improved by 12% over the last month, primarily
                  driven by better performance against left-handed pitchers.
                </p>
              </div>
              <div>
                <h3 className="font-medium">Pitching Analysis</h3>
                <p className="text-sm text-muted-foreground">
                  ERA has decreased steadily, with relief pitchers showing the most significant
                  improvement in high-leverage situations.
                </p>
              </div>
              <div>
                <h3 className="font-medium">Defensive Metrics</h3>
                <p className="text-sm text-muted-foreground">
                  Fielding percentage has remained consistently above league average,
                  with particularly strong middle infield play.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <PerformanceChart />

        <Card className="col-span-1 md:col-span-2">
          <CardHeader>
            <CardTitle>Performance Trends</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div>
                <h3 className="font-medium">Home vs. Away</h3>
                <p className="text-sm text-muted-foreground">
                  The team has performed 18% better at home games compared to away games
                  over the last 20 games.
                </p>
              </div>
              <div>
                <h3 className="font-medium">Day vs. Night Games</h3>
                <p className="text-sm text-muted-foreground">
                  Offensive production increases by 7% during day games, while pitching
                  performance is more consistent regardless of game time.
                </p>
              </div>
              <div>
                <h3 className="font-medium">Against Division Rivals</h3>
                <p className="text-sm text-muted-foreground">
                  The team has a 62% win rate against division rivals this season,
                  compared to 54% against non-division teams.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default Performance;
