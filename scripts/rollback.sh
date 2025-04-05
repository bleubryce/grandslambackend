#!/bin/bash

# Rollback script for Baseball Analytics System

set -e
set -u

DEPLOY_ENV=$1
VERSION_TO_ROLLBACK=${2:-""}
source ./scripts/load-env.sh

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
DEPLOY_DIR="/var/www/baseball-analytics"
LOG_FILE="/var/log/baseball-analytics/rollback_${TIMESTAMP}.log"

# Logging function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Error handling
handle_error() {
    log "ERROR: Rollback failed at line $1"
    notify_team "Rollback failed during $2. Check logs at $LOG_FILE"
    exit 1
}

trap 'handle_error ${LINENO} ${FUNCNAME:-main}' ERR

# Notify team function
notify_team() {
    local message=$1
    
    # Send email notification
    echo "$message" | mail -s "Baseball Analytics Rollback Alert - ${DEPLOY_ENV}" "$ALERT_EMAIL"
    
    # Send Slack notification if configured
    if [ -n "${SLACK_WEBHOOK_URL:-}" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"$message\"}" \
            "$SLACK_WEBHOOK_URL"
    fi
}

# Get available versions
get_available_versions() {
    log "Fetching available versions..."
    docker image ls "baseball-analytics-app" --format "{{.Tag}}" | sort -r
}

# Restore database from backup
restore_database() {
    local backup_file="$BACKUP_DIR/latest.dump"
    log "Restoring database from $backup_file"
    
    # Stop application to prevent writes during restore
    docker-compose -f docker-compose.prod.yml stop app
    
    # Restore database
    PGPASSWORD=$DB_PASSWORD pg_restore \
        -h $DB_HOST \
        -U $DB_USER \
        -d $DB_NAME \
        -c \
        -v \
        "$backup_file"
    
    log "Database restore completed"
}

# Rollback application version
rollback_application() {
    local version=$1
    log "Rolling back application to version $version"
    
    # Update docker-compose file with rollback version
    sed -i "s/baseball-analytics-app:.*/baseball-analytics-app:${version}/" docker-compose.prod.yml
    
    # Pull the specific version
    docker-compose -f docker-compose.prod.yml pull app
    
    # Restart services
    docker-compose -f docker-compose.prod.yml up -d
    
    log "Application rollback completed"
}

# Verify rollback
verify_rollback() {
    log "Verifying rollback..."
    
    # Wait for services to be ready
    sleep 10
    
    # Check application health
    curl -f "https://${APP_DOMAIN}/health" || {
        log "ERROR: Application health check failed"
        return 1
    }
    
    # Check database connectivity
    docker-compose -f docker-compose.prod.yml exec -T app \
        node -e "require('./dist/db').testConnection()" || {
        log "ERROR: Database connectivity check failed"
        return 1
    }
    
    log "Rollback verification completed successfully"
    return 0
}

# Main rollback process
main() {
    log "Starting rollback process for $DEPLOY_ENV environment"
    
    # If no version specified, list available versions and exit
    if [ -z "$VERSION_TO_ROLLBACK" ]; then
        log "No version specified. Available versions:"
        get_available_versions
        exit 0
    }
    
    # Verify version exists
    if ! docker image inspect "baseball-analytics-app:${VERSION_TO_ROLLBACK}" >/dev/null 2>&1; then
        log "ERROR: Version ${VERSION_TO_ROLLBACK} not found"
        exit 1
    }
    
    # Execute rollback steps
    restore_database
    rollback_application "$VERSION_TO_ROLLBACK"
    
    # Verify rollback
    if verify_rollback; then
        log "Rollback completed successfully to version ${VERSION_TO_ROLLBACK}"
        notify_team "Rollback to version ${VERSION_TO_ROLLBACK} completed successfully in ${DEPLOY_ENV}"
    else
        log "ERROR: Rollback verification failed"
        notify_team "Rollback verification failed in ${DEPLOY_ENV}. Manual intervention required."
        exit 1
    fi
}

# Execute main function
main 