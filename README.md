# Grand Slam Baseball Analytics System

This is a monorepo containing both the frontend and backend applications for the Grand Slam Baseball Analytics System.

## Project Structure

```
/
├── package.json          # Backend package.json
├── src/
│   ├── UI/              # Frontend application
│   │   ├── package.json # Frontend package.json
│   │   └── ...         # Frontend source files
│   └── ...             # Backend source files
```

## Getting Started

### Backend

1. Install dependencies:
```bash
npm install
```

2. Start the development server:
```bash
npm run dev
```

### Frontend

1. Navigate to the frontend directory:
```bash
cd src/UI
```

2. Install dependencies:
```bash
npm install
```

3. Start the development server:
```bash
npm run dev
```

4. Build for development:
```bash
npm run build:dev
```

5. Build for production:
```bash
npm run build
```

## Scripts

### Backend Scripts
- `npm run dev` - Start the backend development server
- `npm run build` - Build the backend
- `npm test` - Run all tests

### Frontend Scripts
- `npm run dev` - Start the frontend development server
- `npm run build:dev` - Build frontend for development
- `npm run build` - Build frontend for production
- `npm run preview` - Preview the production build

## Authentication

The system includes a complete authentication system with:
- User registration with email verification
- JWT-based authentication
- Role-based access control
- Password reset functionality
- Remember me feature
- Protected routes

## Database

The system uses PostgreSQL for data storage. Make sure to set up your database connection in the backend's environment variables.

## Features

- Team Management
- Player Analysis
- Game Statistics
- Performance Metrics
- Report Generation
- Real-time Analytics
- Data Visualization
- Historical Data Analysis

## Prerequisites

- Node.js (v18 or higher)
- npm (v9 or higher)
- PostgreSQL (v14 or higher)
- Redis (v6 or higher)
- Docker & Docker Compose
- AWS Account (for backups)

## Quick Start

1. Clone the repository:
```bash
git clone https://github.com/your-username/baseball-analytics.git
cd baseball-analytics
```

2. Install dependencies:
```bash
# Install backend dependencies
npm install

# Install frontend dependencies
cd src/UI
npm install
```

3. Set up environment variables:
```bash
# Backend (.env)
PORT=3000
DB_HOST=localhost
DB_PORT=5432
DB_NAME=baseball_analytics
DB_USER=postgres
DB_PASSWORD=postgres
CORS_ORIGIN=http://localhost:3000
MODEL_ENABLED=true
MODEL_VERSION=1.0
JWT_SECRET=your-jwt-secret

# Frontend (src/UI/.env)
VITE_BACKEND_API_URL=http://localhost:3000
VITE_APP_TITLE=Grand Slam Analytics
VITE_MODEL_ENABLED=true
VITE_MODEL_VERSION=1.0
```

4. Start the development servers:
```bash
# Start backend (from root directory)
npm run dev

# Start frontend (from src/UI directory)
npm run dev
```

5. Access the application:
- Frontend: http://localhost:3000
- Backend API: http://localhost:3000/api
- API Documentation: http://localhost:3000/api-docs

## Development

### Backend Development
- API routes are in `src/index.ts`
- Database models are in `src/Database/`
- Analysis engine is in `src/Analysis/`

### Frontend Development
- React components are in `src/UI/src/components/`
- API services are in `src/UI/src/services/`
- Types are in `src/UI/src/types/`

## Testing

```bash
# Run backend tests
npm test

# Run frontend tests
cd src/UI
npm test
```

## Deployment

1. Build the application:
```bash
# Build backend
npm run build

# Build frontend
cd src/UI
npm run build
```

2. Set up production environment variables
3. Deploy using Docker:
```bash
docker-compose up -d
```

## API Documentation

See `FRONTEND_INTEGRATION.md` for detailed API documentation.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Security

For security issues, please email security@yourdomain.com

## Support

For support, email support@yourdomain.com

## Authors

- Your Name - Initial work - [YourGitHub](https://github.com/yourusername)

## Acknowledgments

- Baseball statistics providers
- Open source community
- Contributors

## Testing Guidelines

### Setup and Configuration

1. **Test Environment**
   - Jest with TypeScript support
   - React Testing Library for component testing
   - MSW for API mocking
   - User Event for simulating user interactions

2. **Running Tests**
   ```bash
   # Run all tests
   npm test

   # Watch mode
   npm run test:watch

   # Coverage report
   npm run test:coverage

   # CI mode with coverage
   npm run test:ci
   ```

3. **File Structure**
   ```
   src/
   ├── components/
   │   └── ComponentName/
   │       ├── ComponentName.tsx
   │       └── __tests__/
   │           └── ComponentName.test.tsx
   └── tests/
       ├── setup/
       │   ├── jest.setup.ts
       │   └── jest.env.ts
       └── templates/
           └── ComponentTest.template.tsx
   ```

### Writing Tests

1. **Component Tests**
   - Use the provided template in `src/tests/templates/ComponentTest.template.tsx`
   - Follow Testing Library best practices
   - Include accessibility tests
   - Test error states and loading states
   - Use data-testid attributes sparingly

2. **Test Structure**
   ```typescript
   describe('ComponentName', () => {
     // Setup and mock data
     beforeEach(() => {
       // Common setup
     });

     it('should [expected behavior]', () => {
       // Test case
     });

     describe('Feature/Behavior Group', () => {
       it('should [expected behavior]', () => {
         // Test case
       });
     });
   });
   ```

3. **Best Practices**
   - Use semantic queries (getByRole, getByLabelText)
   - Prefer user-event over fireEvent
   - Test component behavior, not implementation
   - Write accessible components and test for accessibility
   - Mock external dependencies consistently

4. **Coverage Requirements**
   - Minimum 80% coverage for:
     - Statements
     - Branches
     - Functions
     - Lines

### Pre-commit Hooks

The project uses Husky and lint-staged to ensure code quality:
- ESLint runs on staged files
- Prettier formats staged files
- Tests must pass before commit

### Continuous Integration

Tests run automatically on:
- Pull requests
- Merges to main branch
- Release branches 