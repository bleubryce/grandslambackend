#!/bin/bash

# Backup script for Baseball Analytics System

set -e
set -u

DEPLOY_ENV=$1
source ./scripts/load-env.sh

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
S3_BUCKET="${BACKUP_S3_BUCKET:-baseball-analytics-backups-prod}"
RETENTION_DAYS=30
BACKUP_FILE="$BACKUP_DIR/backup_$TIMESTAMP.sql.gz"

# Logging function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Database backup function
backup_database() {
    log "Creating database backup: $BACKUP_FILE"
    
    PGPASSWORD=$DB_PASSWORD pg_dump \
        -h $DB_HOST \
        -U $DB_USER \
        -d $DB_NAME \
        -F c \
        -b \
        -v \
        -f "$BACKUP_FILE"
    
    # Compress backup
    gzip "$BACKUP_FILE"
    
    log "Database backup completed: ${BACKUP_FILE}.gz"
    return 0
}

# File backup function
backup_files() {
    local backup_file="$BACKUP_DIR/files_${TIMESTAMP}.tar.gz"
    log "Creating file backup: $backup_file"
    
    # Backup uploaded files
    tar -czf "$backup_file" \
        -C /var/www/baseball-analytics/uploads \
        .
    
    log "File backup completed: $backup_file"
    return 0
}

# Upload to S3
upload_to_s3() {
    local file=$1
    log "Uploading $file to S3"
    
    aws s3 cp "$file" "s3://${S3_BUCKET}/${DEPLOY_ENV}/$(basename "$file")" \
        --storage-class STANDARD_IA
    
    log "S3 upload completed for $file"
    return 0
}

# Cleanup old backups
cleanup_old_backups() {
    log "Cleaning up old backups..."
    
    # Local cleanup
    find "$BACKUP_DIR" -type f -mtime +$RETENTION_DAYS -delete
    
    # S3 cleanup
    aws s3 ls "s3://${S3_BUCKET}/${DEPLOY_ENV}/" | while read -r line; do
        createDate=$(echo "$line" | awk {'print $1" "$2'})
        createDate=$(date -d "$createDate" +%s)
        olderThan=$(date -d "-$RETENTION_DAYS days" +%s)
        if [[ $createDate -lt $olderThan ]]; then
            fileName=$(echo "$line" | awk {'print $4'})
            if [[ $fileName != "" ]]; then
                aws s3 rm "s3://${S3_BUCKET}/${DEPLOY_ENV}/$fileName"
            fi
        fi
    done
    
    log "Cleanup completed"
    return 0
}

# Verify backup integrity
verify_backup() {
    local backup_file=$1
    log "Verifying backup integrity: $backup_file"
    
    if [[ $backup_file == *.dump.gz ]]; then
        # Verify database backup
        gzip -t "$backup_file"
        if [[ $? -ne 0 ]]; then
            log "ERROR: Database backup verification failed"
            return 1
        fi
    elif [[ $backup_file == *.tar.gz ]]; then
        # Verify file backup
        tar -tzf "$backup_file" >/dev/null
        if [[ $? -ne 0 ]]; then
            log "ERROR: File backup verification failed"
            return 1
        fi
    fi
    
    log "Backup verification completed: $backup_file"
    return 0
}

# Main backup process
main() {
    log "Starting backup process for $DEPLOY_ENV environment"
    
    # Create database backup
    backup_database
    verify_backup "$BACKUP_FILE"
    upload_to_s3 "$BACKUP_FILE"
    
    # Create file backup
    backup_files
    verify_backup "${BACKUP_DIR}/files_${TIMESTAMP}.tar.gz"
    upload_to_s3 "${BACKUP_DIR}/files_${TIMESTAMP}.tar.gz"
    
    # Cleanup old backups
    cleanup_old_backups
    
    log "Backup process completed successfully"
    return 0
}

# Execute main function
main 