
# Build stage
FROM node:18-alpine AS builder

WORKDIR /usr/src/app

# Install OpenSSL
RUN apk add --no-cache openssl openssl-dev

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Development stage
FROM node:18-alpine AS development

WORKDIR /usr/src/app

# Install OpenSSL
RUN apk add --no-cache openssl openssl-dev

# Copy package files
COPY package*.json ./

# Install dependencies including development ones
RUN npm install

# Copy source code
COPY . .

# Expose development port
EXPOSE 8080

# Start development server
CMD ["npm", "run", "dev"]

# Production stage
FROM node:18-alpine AS production

WORKDIR /usr/src/app

# Install OpenSSL
RUN apk add --no-cache openssl openssl-dev

# Copy package files
COPY package*.json ./

# Install production dependencies only
RUN npm ci --only=production

# Copy built files from builder
COPY --from=builder /usr/src/app/dist ./dist

# Create uploads directory
RUN mkdir -p uploads && chown -R node:node uploads

# Create logs directory
RUN mkdir -p logs && chown -R node:node logs

# Switch to non-root user
USER node

# Expose production port
EXPOSE 8080

# Start production server
CMD ["npm", "run", "preview"]
