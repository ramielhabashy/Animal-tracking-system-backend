#!/bin/bash
set -e

BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_$TIMESTAMP"

mkdir -p "$BACKUP_DIR"

echo "💾 Starting backup process..."

# Database backup
echo "🗄️  Backing up MySQL database..."
docker-compose exec -T mysql mysqldump \
    --user=root \
    --password="$MYSQL_ROOT_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    "$MYSQL_DATABASE" > "$BACKUP_FILE.sql"

# Compress database backup
gzip "$BACKUP_FILE.sql"
echo "✅ Database backup saved to $BACKUP_FILE.sql.gz"

# Backup storage files (uploads, etc.)
echo "📁 Backing up storage files..."
docker-compose exec -T app tar -czf - /var/www/html/storage/app > "$BACKUP_FILE-storage.tar.gz"
echo "✅ Storage backup saved to $BACKUP_FILE-storage.tar.gz"

# Backup environment file
echo "🔐 Backing up environment configuration..."
cp backend/.env "$BACKUP_DIR/env_$TIMESTAMP.bak"
echo "✅ Environment backup saved to $BACKUP_DIR/env_$TIMESTAMP.bak"

# Cleanup old backups (keep last 7 days)
echo "🧹 Cleaning up old backups..."
find "$BACKUP_DIR" -name "*.gz" -mtime +7 -delete
find "$BACKUP_DIR" -name "*.bak" -mtime +7 -delete

echo "✅ Backup complete!"
echo "📊 Backup files:"
ls -lh "$BACKUP_DIR" | tail -n 10
