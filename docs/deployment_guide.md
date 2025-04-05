# Baseball Analytics System - Deployment Guide

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Prerequisites](#prerequisites)
3. [Environment Setup](#environment-setup)
4. [Database Configuration](#database-configuration)
5. [Application Deployment](#application-deployment)
6. [Security Configuration](#security-configuration)
7. [Monitoring Setup](#monitoring-setup)
8. [Troubleshooting](#troubleshooting)

## System Requirements

### Hardware Requirements
- CPU: 4+ cores
- RAM: 16GB minimum
- Storage: 100GB+ SSD
- Network: 1Gbps

### Software Requirements
- Node.js 18.x or higher
- PostgreSQL 14.x or higher
- Redis 6.x or higher
- Docker 24.x or higher
- Docker Compose 2.x

## Prerequisites

1. Install required software:
```bash
# Update package list
apt-get update

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

# Install Docker
curl -fsSL https://get.docker.com | sh

# Install Docker Compose
curl -L "https://github.com/docker/compose/releases/download/v2.24.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
```

2. Configure environment variables:
```bash
# Create environment file
cp .env.example .env

# Edit environment variables
nano .env

# Required variables:
# - DATABASE_URL
# - REDIS_URL
# - JWT_SECRET
# - API_KEY
# - NODE_ENV
```

## Environment Setup

1. Clone the repository:
```bash
git clone https://github.com/your-org/baseball-analytics.git
cd baseball-analytics
```

2. Install dependencies:
```bash
# Install backend dependencies
cd backend
npm install

# Install frontend dependencies
cd ../frontend
npm install
```

3. Build the application:
```bash
# Build backend
cd backend
npm run build

# Build frontend
cd ../frontend
npm run build
```

## Database Configuration

1. Initialize database:
```bash
# Run database migrations
cd backend
npm run db:migrate

# Seed initial data
npm run db:seed
```

2. Configure database backup:
```bash
# Set up automated backups
cd scripts
./setup-db-backup.sh

# Verify backup configuration
./verify-backup.sh
```

## Application Deployment

1. Deploy using Docker:
```bash
# Build Docker images
docker-compose build

# Start services
docker-compose up -d

# Verify deployment
docker-compose ps
```

2. Manual deployment:
```bash
# Start backend server
cd backend
npm run start:prod

# Start frontend server
cd frontend
npm run start:prod
```

## Security Configuration

1. SSL/TLS setup:
```bash
# Generate SSL certificate
./scripts/generate-ssl.sh

# Configure SSL in nginx
cp nginx/ssl.conf /etc/nginx/conf.d/
nginx -t && nginx -s reload
```

2. Configure firewall:
```bash
# Allow required ports
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 5432/tcp
ufw enable
```

## Monitoring Setup

1. Install monitoring tools:
```bash
# Install Prometheus
./scripts/install-prometheus.sh

# Install Grafana
./scripts/install-grafana.sh

# Configure monitoring
./scripts/setup-monitoring.sh
```

2. Configure alerts:
```bash
# Set up alert rules
cp monitoring/alert-rules.yml /etc/prometheus/
systemctl restart prometheus
```

## Troubleshooting

### Common Issues

1. Database Connection Issues
```bash
# Check database status
systemctl status postgresql

# Verify connection
psql -U postgres -h localhost -d baseball_analytics
```

2. Application Startup Issues
```bash
# Check logs
docker-compose logs -f

# Verify ports
netstat -tulpn | grep LISTEN
```

3. Performance Issues
```bash
# Monitor system resources
htop

# Check application metrics
curl http://localhost:9090/metrics
```

### Health Checks

1. Backend health check:
```bash
curl http://localhost:3000/health
```

2. Database health check:
```bash
npm run db:health-check
```

3. Redis health check:
```bash
redis-cli ping
```

### Backup and Recovery

1. Manual backup:
```bash
# Backup database
./scripts/backup-db.sh

# Backup uploads
./scripts/backup-uploads.sh
```

2. Recovery procedure:
```bash
# Restore database
./scripts/restore-db.sh <backup-file>

# Restore uploads
./scripts/restore-uploads.sh <backup-file>
```

## Support

For additional support:
- Email: support@baseball-analytics.com
- Documentation: https://docs.baseball-analytics.com
- Issue Tracker: https://github.com/your-org/baseball-analytics/issues 