#!/bin/bash

# Setup cron job for automatic SSL certificate renewal
# Run this script once to configure automated renewals

set -e

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
RENEW_SCRIPT="$PROJECT_DIR/scripts/renew-ssl.sh"
LOG_FILE="/var/log/letsencrypt-renew.log"

echo "Setting up cron job for SSL certificate auto-renewal..."
echo "Project directory: $PROJECT_DIR"
echo "Renewal script: $RENEW_SCRIPT"
echo "Log file: $LOG_FILE"
echo ""

# Check if renewal script exists and is executable
if [ ! -f "$RENEW_SCRIPT" ]; then
    echo "Error: Renewal script not found at $RENEW_SCRIPT"
    exit 1
fi

if [ ! -x "$RENEW_SCRIPT" ]; then
    echo "Making renewal script executable..."
    chmod +x "$RENEW_SCRIPT"
fi

# Create log directory if it doesn't exist
LOG_DIR=$(dirname "$LOG_FILE")
if [ ! -d "$LOG_DIR" ]; then
    echo "Creating log directory: $LOG_DIR"
    sudo mkdir -p "$LOG_DIR"
fi

# Ensure log file exists and is writable
sudo touch "$LOG_FILE"
sudo chmod 644 "$LOG_FILE"

# Create cron job entry
CRON_JOB="0 3 * * * $RENEW_SCRIPT >> $LOG_FILE 2>&1"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "$RENEW_SCRIPT"; then
    echo "Cron job already exists. Updating..."
    (crontab -l 2>/dev/null | grep -v "$RENEW_SCRIPT"; echo "$CRON_JOB") | crontab -
else
    echo "Adding new cron job..."
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
fi

echo ""
echo "✓ Cron job configured successfully!"
echo ""
echo "Schedule: Daily at 3:00 AM"
echo "Command: $RENEW_SCRIPT"
echo "Logs: $LOG_FILE"
echo ""
echo "Current crontab:"
crontab -l | grep "$RENEW_SCRIPT"
echo ""
echo "To view renewal logs: tail -f $LOG_FILE"
echo "To remove cron job: crontab -e (then delete the line)"
