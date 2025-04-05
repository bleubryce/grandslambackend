#!/bin/bash

# Pre-deployment check script for Baseball Analytics System

set -e
set -u

DEPLOY_ENV=$1
source ./scripts/load-env.sh

# Logging function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Check required environment variables
check_env_vars() {
    log "Checking environment variables..."
    local required_vars=(
        "NODE_ENV"
        "DATABASE_URL"
        "REDIS_URL"
        "JWT_SECRET"
        "AWS_ACCESS_KEY_ID"
        "AWS_SECRET_ACCESS_KEY"
        "SMTP_USER"
        "SMTP_PASSWORD"
    )

    for var in "${required_vars[@]}"; do
        if [[ -z "${!var:-}" ]]; then
            log "ERROR: Required environment variable $var is not set"
            return 1
        fi
    done
    log "Environment variables check passed"
}

# Check disk space
check_disk_space() {
    log "Checking disk space..."
    local min_space=10485760  # 10GB in KB
    local available_space=$(df -k /var/lib/docker | tail -1 | awk '{print $4}')
    
    if [[ $available_space -lt $min_space ]]; then
        log "ERROR: Insufficient disk space. Required: 10GB, Available: $(($available_space/1024/1024))GB"
        return 1
    fi
    log "Disk space check passed"
}

# Check Docker service
check_docker() {
    log "Checking Docker service..."
    if ! docker info >/dev/null 2>&1; then
        log "ERROR: Docker service is not running"
        return 1
    fi
    log "Docker service check passed"
}

# Check Docker Compose
check_docker_compose() {
    log "Checking Docker Compose..."
    if ! docker-compose version >/dev/null 2>&1; then
        log "ERROR: Docker Compose is not installed"
        return 1
    fi
    log "Docker Compose check passed"
}

# Check database connection
check_database() {
    log "Checking database connection..."
    if ! pg_isready -d "${DATABASE_URL}" >/dev/null 2>&1; then
        log "ERROR: Cannot connect to database"
        return 1
    fi
    log "Database connection check passed"
}

# Check Redis connection
check_redis() {
    log "Checking Redis connection..."
    if ! redis-cli -u "${REDIS_URL}" ping >/dev/null 2>&1; then
        log "ERROR: Cannot connect to Redis"
        return 1
    fi
    log "Redis connection check passed"
}

# Check SSL certificates
check_ssl() {
    log "Checking SSL certificates..."
    local cert_path="/etc/ssl/certs/baseball-analytics.crt"
    local key_path="/etc/ssl/private/baseball-analytics.key"
    
    if [[ ! -f $cert_path ]] || [[ ! -f $key_path ]]; then
        log "ERROR: SSL certificate or key not found"
        return 1
    }
    
    # Check certificate expiration
    local expiry_date=$(openssl x509 -enddate -noout -in "$cert_path" | cut -d= -f2)
    local expiry_epoch=$(date -d "$expiry_date" +%s)
    local current_epoch=$(date +%s)
    local days_until_expiry=$(( ($expiry_epoch - $current_epoch) / 86400 ))
    
    if [[ $days_until_expiry -lt 30 ]]; then
        log "WARNING: SSL certificate will expire in $days_until_expiry days"
    fi
    log "SSL certificate check passed"
}

# Check required ports
check_ports() {
    log "Checking required ports..."
    local required_ports=(80 443 3000 5432 6379)
    
    for port in "${required_ports[@]}"; do
        if netstat -tuln | grep -q ":$port "; then
            log "WARNING: Port $port is already in use"
        fi
    done
    log "Port check completed"
}

# Check backup status
check_backup_status() {
    log "Checking backup status..."
    local backup_file="/backups/latest.dump"
    local max_age=86400  # 24 hours in seconds
    
    if [[ -f $backup_file ]]; then
        local file_age=$(($(date +%s) - $(stat -c %Y "$backup_file")))
        if [[ $file_age -gt $max_age ]]; then
            log "WARNING: Latest backup is older than 24 hours"
        fi
    else
        log "WARNING: No recent backup found"
    fi
    log "Backup status check completed"
}

# Check system resources
check_resources() {
    log "Checking system resources..."
    
    # Check CPU load
    local cpu_load=$(uptime | awk '{print $10}' | cut -d',' -f1)
    if (( $(echo "$cpu_load > 0.8" | bc -l) )); then
        log "WARNING: High CPU load: $cpu_load"
    fi
    
    # Check memory usage
    local memory_free=$(free | grep Mem | awk '{print $4/$2 * 100.0}')
    if (( $(echo "$memory_free < 20" | bc -l) )); then
        log "WARNING: Low memory available: $memory_free%"
    fi
    
    log "Resource check completed"
}

# Main function
main() {
    log "Starting pre-deployment checks for $DEPLOY_ENV environment"
    
    check_env_vars || exit 1
    check_disk_space || exit 1
    check_docker || exit 1
    check_docker_compose || exit 1
    check_database || exit 1
    check_redis || exit 1
    check_ssl || exit 1
    check_ports
    check_backup_status
    check_resources
    
    log "All pre-deployment checks completed successfully"
}

# Execute main function
main 