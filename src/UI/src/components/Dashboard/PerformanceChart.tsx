
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { useTeams } from "@/hooks/useTeams";
import { usePlayers } from "@/hooks/usePlayers";
import { Skeleton } from "@/components/ui/skeleton";
import { useState, useEffect } from "react";
import { format, subMonths } from "date-fns";

// Generate monthly data points for the last 7 months
const generateMonthlyData = () => {
  const months = [];
  for (let i = 6; i >= 0; i--) {
    const date = subMonths(new Date(), i);
    months.push({
      month: format(date, 'MMM'),
      batting: 0,
      pitching: 0,
      defense: 0
    });
  }
  return months;
};

const PerformanceChart = () => {
  const { players, isLoading: playersLoading } = usePlayers();
  const { teams, isLoading: teamsLoading } = useTeams();
  const [performanceData, setPerformanceData] = useState(generateMonthlyData());
  const isLoading = playersLoading || teamsLoading;

  useEffect(() => {
    if (!isLoading && players && players.length > 0) {
      // In a real app, we'd use real monthly stats from an API
      // Here we'll generate realistic-looking random data that improves over time
      
      const months = generateMonthlyData();
      
      // Generate realistic-looking data that shows improvement over time
      months.forEach((month, index) => {
        // Batting average typically between .240 and .310
        month.batting = 0.240 + (0.070 * (index / 6)) + (Math.random() * 0.020);
        
        // ERA typically between 4.50 and 2.80 (lower is better, so decrease over time)
        month.pitching = 4.50 - (1.70 * (index / 6)) - (Math.random() * 0.30);
        
        // Fielding percentage typically between .970 and .990
        month.defense = 0.970 + (0.020 * (index / 6)) + (Math.random() * 0.005);
      });
      
      setPerformanceData(months);
    }
  }, [isLoading, players]);

  return (
    <Card className="col-span-4">
      <CardHeader>
        <CardTitle>Team Performance Metrics</CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <Skeleton className="h-[350px] w-full" />
        ) : (
          <ResponsiveContainer width="100%" height={350}>
            <LineChart
              data={performanceData}
              margin={{
                top: 5,
                right: 10,
                left: 10,
                bottom: 0,
              }}
            >
              <XAxis
                dataKey="month"
                stroke="#888888"
                fontSize={12}
                tickLine={false}
                axisLine={false}
              />
              <YAxis
                stroke="#888888"
                fontSize={12}
                tickLine={false}
                axisLine={false}
                tickFormatter={(value) => `${value}`}
              />
              <Tooltip />
              <Legend />
              <Line
                type="monotone"
                dataKey="batting"
                name="Batting Avg"
                stroke="#E31937"
                activeDot={{ r: 8 }}
                strokeWidth={2}
              />
              <Line
                type="monotone"
                dataKey="pitching"
                name="ERA"
                stroke="#0A2351"
                strokeWidth={2}
              />
              <Line
                type="monotone"
                dataKey="defense"
                name="Fielding Pct"
                stroke="#4A89F3"
                strokeWidth={2}
              />
            </LineChart>
          </ResponsiveContainer>
        )}
      </CardContent>
    </Card>
  );
};

export default PerformanceChart;
