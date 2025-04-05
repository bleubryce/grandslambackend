
import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Calendar as CalendarIcon, Download, Filter, Trash2, Search } from "lucide-react";
import { format } from "date-fns";
import { Calendar } from "@/components/ui/calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";

// Mock prediction history data
const mockPredictions = [
  {
    id: 1,
    date: new Date(2023, 5, 15),
    teams: "Dodgers vs Giants",
    predictionType: "Game Winner",
    result: "Dodgers (W)",
    accuracy: "Correct (95%)",
    userId: "user123",
  },
  {
    id: 2,
    date: new Date(2023, 5, 17),
    teams: "Yankees vs Red Sox",
    predictionType: "Total Runs",
    result: "Over 8.5",
    accuracy: "Incorrect (68%)",
    userId: "user123",
  },
  {
    id: 3,
    date: new Date(2023, 5, 20),
    teams: "Cubs vs Cardinals",
    predictionType: "Game Winner",
    result: "Cardinals (W)",
    accuracy: "Correct (82%)",
    userId: "user456",
  },
  {
    id: 4,
    date: new Date(2023, 5, 22),
    teams: "Braves vs Mets",
    predictionType: "Runs Scored",
    result: "Braves (5)",
    accuracy: "Correct (77%)",
    userId: "user123",
  },
  {
    id: 5,
    date: new Date(2023, 5, 25),
    teams: "Astros vs Rangers",
    predictionType: "Game Winner",
    result: "Astros (W)",
    accuracy: "Correct (88%)",
    userId: "user456",
  },
];

const History = () => {
  const [date, setDate] = useState<Date | undefined>(undefined);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedType, setSelectedType] = useState<string>("");
  
  // Filter predictions based on date, search term, and type
  const filteredPredictions = mockPredictions.filter((prediction) => {
    // Date filter
    const dateMatch = !date || format(prediction.date, "yyyy-MM-dd") === format(date, "yyyy-MM-dd");
    
    // Search term filter
    const searchMatch = !searchTerm || 
      prediction.teams.toLowerCase().includes(searchTerm.toLowerCase()) ||
      prediction.result.toLowerCase().includes(searchTerm.toLowerCase());
    
    // Type filter
    const typeMatch = !selectedType || prediction.predictionType === selectedType;
    
    return dateMatch && searchMatch && typeMatch;
  });
  
  return (
    <div className="container mx-auto py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold tracking-tight">Prediction History</h1>
        <p className="text-muted-foreground">
          View and analyze past prediction results
        </p>
      </div>

      <Card className="mb-6">
        <CardHeader>
          <CardTitle>Prediction History</CardTitle>
          <CardDescription>
            View and filter your past game predictions and analysis
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex flex-col space-y-4 md:flex-row md:items-center md:space-y-0 md:space-x-4">
            <div className="relative flex-1">
              <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                type="search"
                placeholder="Search predictions..."
                className="pl-8"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
            
            <Popover>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  className="justify-start text-left font-normal md:w-[240px]"
                >
                  <CalendarIcon className="mr-2 h-4 w-4" />
                  {date ? format(date, "PPP") : "Filter by date"}
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                  mode="single"
                  selected={date}
                  onSelect={setDate}
                  initialFocus
                />
              </PopoverContent>
            </Popover>
            
            <Select value={selectedType} onValueChange={setSelectedType}>
              <SelectTrigger className="md:w-[180px]">
                <SelectValue placeholder="Prediction type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Types</SelectItem>
                <SelectItem value="Game Winner">Game Winner</SelectItem>
                <SelectItem value="Total Runs">Total Runs</SelectItem>
                <SelectItem value="Runs Scored">Runs Scored</SelectItem>
              </SelectContent>
            </Select>
            
            <Button variant="outline" className="gap-1" onClick={() => {
              setDate(undefined);
              setSearchTerm("");
              setSelectedType("");
            }}>
              <Filter className="h-4 w-4" /> Clear
            </Button>
            
            <Button variant="outline" className="gap-1 md:ml-auto">
              <Download className="h-4 w-4" /> Export
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="pt-6">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Teams</TableHead>
                <TableHead>Prediction Type</TableHead>
                <TableHead>Result</TableHead>
                <TableHead>Accuracy</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredPredictions.length > 0 ? (
                filteredPredictions.map((prediction) => (
                  <TableRow key={prediction.id}>
                    <TableCell>{format(prediction.date, "MMM d, yyyy")}</TableCell>
                    <TableCell className="font-medium">{prediction.teams}</TableCell>
                    <TableCell>{prediction.predictionType}</TableCell>
                    <TableCell>{prediction.result}</TableCell>
                    <TableCell>
                      <span className={cn(
                        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
                        prediction.accuracy.includes("Correct") 
                          ? "bg-green-50 text-green-700" 
                          : "bg-red-50 text-red-700"
                      )}>
                        {prediction.accuracy}
                      </span>
                    </TableCell>
                    <TableCell className="text-right">
                      <Button variant="ghost" size="icon">
                        <Trash2 className="h-4 w-4 text-muted-foreground" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={6} className="h-24 text-center">
                    No predictions found matching your filters
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
          
          <div className="mt-4 flex items-center justify-end space-x-2">
            <Button variant="outline" size="sm" disabled>
              Previous
            </Button>
            <Button variant="outline" size="sm" disabled>
              Next
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default History;
