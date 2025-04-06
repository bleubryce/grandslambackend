#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Starting Baseball Analytics System Test Suite${NC}"
echo "----------------------------------------"

# Function to check if a command was successful
check_status() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ $1 successful${NC}"
    else
        echo -e "${RED}✗ $1 failed${NC}"
        exit 1
    fi
}

# 1. Environment Check
echo -e "\n${YELLOW}1. Checking Environment${NC}"
node -v
check_status "Node.js check"
npm -v
check_status "NPM check"

# 2. Install Dependencies
echo -e "\n${YELLOW}2. Installing Dependencies${NC}"
npm install
check_status "Dependencies installation"

# 3. Run Unit Tests
echo -e "\n${YELLOW}3. Running Unit Tests${NC}"
npm run test:unit
check_status "Unit tests"

# 4. Run Integration Tests
echo -e "\n${YELLOW}4. Running Integration Tests${NC}"
npm run test:integration
check_status "Integration tests"

# 5. Run E2E Tests
echo -e "\n${YELLOW}5. Running E2E Tests${NC}"
npm run test:e2e
check_status "E2E tests"

# 6. Test Data Processing Pipeline
echo -e "\n${YELLOW}6. Testing Data Processing Pipeline${NC}"
npm run test:data-processing
check_status "Data processing pipeline"

# 7. Test Analysis Engine
echo -e "\n${YELLOW}7. Testing Analysis Engine${NC}"
npm run test:analysis
check_status "Analysis engine"

# 8. Test Machine Learning Models
echo -e "\n${YELLOW}8. Testing ML Models${NC}"
npm run test:ml
check_status "Machine learning models"

# 9. Test API Endpoints
echo -e "\n${YELLOW}9. Testing API Endpoints${NC}"
npm run test:api
check_status "API endpoints"

# 10. Test Security Features
echo -e "\n${YELLOW}10. Testing Security Features${NC}"
npm run test:security
check_status "Security features"

# 11. Test Monitoring System
echo -e "\n${YELLOW}11. Testing Monitoring System${NC}"
npm run test:monitoring
check_status "Monitoring system"

# 12. Performance Tests
echo -e "\n${YELLOW}12. Running Performance Tests${NC}"
npm run test:performance
check_status "Performance tests"

# 13. Start System and Run Health Check
echo -e "\n${YELLOW}13. Starting System and Running Health Check${NC}"
npm run start &
SYSTEM_PID=$!

# Wait for system to start
echo "Waiting for system to start..."
sleep 10

# Run health check
curl -s http://localhost:3000/api/health
check_status "System health check"

# Kill the system process
kill $SYSTEM_PID

echo -e "\n${GREEN}All tests completed successfully!${NC}"
echo "----------------------------------------"

# Print system metrics
echo -e "\n${YELLOW}System Metrics:${NC}"
echo "- Test Coverage: $(npm run test:coverage | grep 'All files' | awk '{print $4}')"
echo "- Total Tests: $(find . -name '*.test.*' -type f | wc -l)"
echo "- API Endpoints: $(find . -name '*.controller.*' -type f | wc -l)"
echo "- Components: $(find . -name '*.component.*' -type f | wc -l)"

# Print final status
echo -e "\n${GREEN}System is ready for production deployment!${NC}" 