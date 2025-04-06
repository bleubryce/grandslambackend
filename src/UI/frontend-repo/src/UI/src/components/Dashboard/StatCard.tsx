
import { cn } from "@/lib/utils";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { LucideIcon } from "lucide-react";
import { Skeleton } from "@/components/ui/skeleton";
import { ReactNode } from "react";

interface StatCardProps {
  title: string;
  value: string | number;
  icon: LucideIcon;
  description?: string;
  trend?: {
    value: number;
    isPositive: boolean;
    label?: string;
  };
  className?: string;
  isLoading?: boolean;
  footer?: ReactNode;
}

const StatCard = ({
  title,
  value,
  icon: Icon,
  description,
  trend,
  className,
  isLoading = false,
  footer,
}: StatCardProps) => {
  // Format the value if it's a number
  const formattedValue = typeof value === 'number' 
    ? (value % 1 === 0 ? value.toString() : value.toFixed(3))
    : value;

  return (
    <Card className={cn("overflow-hidden", className)}>
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <>
            <Skeleton className="h-8 w-16 mb-1" />
            {description && <Skeleton className="h-3 w-24 mt-1" />}
            {trend && <Skeleton className="h-3 w-20 mt-2" />}
            {footer && <Skeleton className="h-4 w-full mt-3" />}
          </>
        ) : (
          <>
            <div className="text-2xl font-bold">{formattedValue}</div>
            {description && (
              <p className="text-xs text-muted-foreground">{description}</p>
            )}
            {trend && (
              <div className="flex items-center mt-1">
                <span
                  className={cn(
                    "text-xs font-medium flex items-center",
                    trend.isPositive ? "text-green-600" : "text-red-600"
                  )}
                >
                  {trend.isPositive ? "↑" : "↓"} {Math.abs(trend.value)}%
                </span>
                <span className="text-xs text-muted-foreground ml-1">
                  {trend.label || "vs last season"}
                </span>
              </div>
            )}
            {footer && <div className="mt-3">{footer}</div>}
          </>
        )}
      </CardContent>
    </Card>
  );
};

export default StatCard;
