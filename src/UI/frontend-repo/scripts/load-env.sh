#!/bin/bash

# Environment variables loading script for Baseball Analytics System

set -e
set -u

# Default to production if no environment specified
DEPLOY_ENV=${1:-production}

# Configuration
CONFIG_DIR="config"
ENV_FILE="${CONFIG_DIR}/${DEPLOY_ENV}.env"
REQUIRED_VARS=(
    "NODE_ENV"
    "PORT"
    "HOST"
    "DATABASE_URL"
    "DB_HOST"
    "DB_PORT"
    "DB_USER"
    "DB_PASSWORD"
    "DB_NAME"
    "REDIS_URL"
    "JWT_SECRET"
    "AWS_ACCESS_KEY_ID"
    "AWS_SECRET_ACCESS_KEY"
    "SMTP_USER"
    "SMTP_PASSWORD"
    "APP_DOMAIN"
    "ALERT_EMAIL"
)

# Check if environment file exists
if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: Environment file not found: $ENV_FILE"
    exit 1
fi

# Load environment variables
set -a
source "$ENV_FILE"
set +a

# Verify required variables
missing_vars=()
for var in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!var:-}" ]; then
        missing_vars+=("$var")
    fi
done

if [ ${#missing_vars[@]} -ne 0 ]; then
    echo "ERROR: Missing required environment variables:"
    printf '%s\n' "${missing_vars[@]}"
    exit 1
fi

# Export additional computed variables
export BACKUP_S3_BUCKET="baseball-analytics-backups-${DEPLOY_ENV}"
export LOG_DIR="/var/log/baseball-analytics"
export DEPLOY_TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create required directories
mkdir -p "$LOG_DIR"

echo "Environment variables loaded successfully for ${DEPLOY_ENV} environment" 