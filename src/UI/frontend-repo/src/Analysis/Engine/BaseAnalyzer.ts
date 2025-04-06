import { PoolClient } from 'pg';

export abstract class BaseAnalyzer {
  protected dbConnection: PoolClient;

  constructor(dbConnection: PoolClient) {
    this.dbConnection = dbConnection;
  }

  protected async executeQuery(query: string, params?: any[]): Promise<any[]> {
    try {
      const result = await this.dbConnection.query(query, params);
      return result.rows;
    } catch (error) {
      console.error('Error executing query:', error);
      throw error;
    }
  }

  abstract analyze(id: number): Promise<any>;
} 