
import axios from 'axios';

// MLB Stats API base URL
const MLB_API_BASE_URL = 'https://statsapi.mlb.com/api/v1';

// Create axios instance for MLB API
export const mlbApi = axios.create({
  baseURL: MLB_API_BASE_URL,
  timeout: 10000,
});
