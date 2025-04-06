FROM node:18-alpine

WORKDIR /usr/src/app

# Copy package files
COPY src/UI/package*.json ./

# Install dependencies
RUN npm install

# Install all required development dependencies
RUN npm install -D \
    @tanstack/react-query \
    @types/jest \
    @types/node \
    @types/history \
    axios-mock-adapter \
    file-saver \
    @types/file-saver \
    xlsx \
    @types/xlsx \
    @testing-library/jest-dom \
    @testing-library/react \
    @testing-library/user-event

# Copy source code
COPY src/UI/ .

EXPOSE 3000

# Start the Vite development server with host flag for Docker
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"] 