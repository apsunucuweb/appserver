#!/bin/bash
echo "Running backup script..."
tar -czf /usr/local/appserver/backups/backup_$(date +%Y%m%d).tar.gz /home/*