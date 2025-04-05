
import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Terminal, Database, Server } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

const DockerInfo = () => {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Docker Setup</CardTitle>
        <CardDescription>
          Docker configuration for the Baseball Analytics System
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Tabs defaultValue="commands">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="commands">Commands</TabsTrigger>
            <TabsTrigger value="services">Services</TabsTrigger>
            <TabsTrigger value="env">Environment</TabsTrigger>
          </TabsList>
          
          <TabsContent value="commands">
            <Alert>
              <Terminal className="h-4 w-4" />
              <AlertTitle>Docker Commands</AlertTitle>
              <AlertDescription className="mt-2">
                <div className="space-y-2">
                  <p className="font-semibold">Start development environment:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">docker-compose up -d</pre>
                  
                  <p className="font-semibold">Build production image:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">docker build -t baseball-analytics:latest --target production .</pre>
                  
                  <p className="font-semibold">Run production container:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">docker run -p 8080:8080 baseball-analytics:latest</pre>
                  
                  <p className="font-semibold">View logs:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">docker-compose logs -f</pre>
                </div>
              </AlertDescription>
            </Alert>
          </TabsContent>
          
          <TabsContent value="services">
            <Alert>
              <Database className="h-4 w-4" />
              <AlertTitle>Running Services</AlertTitle>
              <AlertDescription className="mt-2">
                <div className="space-y-2">
                  <p className="font-semibold">Frontend:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">Running on http://localhost:8080</pre>
                  
                  <p className="font-semibold">PostgreSQL Database:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">Running on localhost:5432</pre>
                  <pre className="bg-muted p-2 rounded-md text-xs">Database: baseball_analytics</pre>
                  <pre className="bg-muted p-2 rounded-md text-xs">User: postgres</pre>
                  
                  <p className="font-semibold">Redis:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">Running on localhost:6379</pre>
                </div>
              </AlertDescription>
            </Alert>
          </TabsContent>
          
          <TabsContent value="env">
            <Alert>
              <Server className="h-4 w-4" />
              <AlertTitle>Environment Configuration</AlertTitle>
              <AlertDescription className="mt-2">
                <div className="space-y-2">
                  <p className="font-semibold">API URL:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">http://localhost:3000/api/v1</pre>
                  
                  <p className="font-semibold">Database Connection:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">postgres://postgres:postgres@localhost:5432/baseball_analytics</pre>
                  
                  <p className="font-semibold">Redis Connection:</p>
                  <pre className="bg-muted p-2 rounded-md text-xs">redis://localhost:6379</pre>
                </div>
              </AlertDescription>
            </Alert>
          </TabsContent>
        </Tabs>
      </CardContent>
    </Card>
  );
};

export default DockerInfo;
