# Baseball Data Analysis System

A comprehensive system for analyzing baseball statistics and making predictions using machine learning models.

## Features

- User authentication and authorization
- Real-time data analysis
- Machine learning model integration
- Interactive dashboard
- Role-based access control
- Rate limiting and security features

## Tech Stack

### Backend
- Node.js
- TypeScript
- Express.js
- PostgreSQL
- Redis
- JWT Authentication

### Frontend
- React
- TypeScript
- Vite
- Material-UI
- Chart.js
- TensorFlow.js

## Prerequisites

- Node.js (v18 or higher)
- PostgreSQL (v14 or higher)
- Redis (v6 or higher)
- npm or yarn

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd baseball-data-analysis-system
```

2. Install backend dependencies:
```bash
npm install
```

3. Install frontend dependencies:
```bash
cd src/UI
npm install
```

4. Create environment files:
```bash
cp .env.example .env
cd src/UI
cp .env.example .env
```

5. Set up the database:
```bash
# Start PostgreSQL service
# Create database and user according to .env configuration
```

6. Start Redis server:
```bash
# Start Redis service according to your OS
```

## Development

1. Start the backend server:
```bash
npm run dev
```

2. Start the frontend development server:
```bash
cd src/UI
npm run dev
```

The application will be available at:
- Frontend: http://localhost:3000
- Backend API: http://localhost:3001

## Project Structure

```
.
├── src/
│   ├── Analysis/       # Analysis and ML model logic
│   ├── Database/       # Database configuration and models
│   ├── Logging/        # Logging configuration
│   ├── Monitoring/     # System monitoring
│   ├── Security/       # Authentication and authorization
│   ├── UI/            # Frontend application
│   └── index.ts       # Main application entry
├── tests/             # Test files
├── .env.example       # Example environment variables
├── .gitignore        # Git ignore rules
├── package.json      # Project dependencies
├── tsconfig.json    # TypeScript configuration
└── README.md        # Project documentation
```

## Environment Variables

### Backend (.env)
```
PORT=3001
NODE_ENV=development
DB_HOST=localhost
DB_PORT=5432
DB_NAME=baseball_analytics
DB_USER=postgres
DB_PASSWORD=postgres
REDIS_HOST=localhost
REDIS_PORT=6379
JWT_SECRET=your-secret-key
```

### Frontend (src/UI/.env)
```
VITE_BACKEND_API_URL=http://localhost:3001
VITE_MODEL_ENABLED=true
VITE_MODEL_VERSION=1.0
VITE_ENABLE_ANALYTICS=true
VITE_ENABLE_AUTH=true
```

## API Documentation

The API documentation is available at `/api/docs` when running in development mode.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

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