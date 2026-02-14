#!/bin/bash
# =============================================================================
# Common Portal Platform — Migration Sync Script
# =============================================================================
# Synchronizes migration files across all three projects so every project
# has the complete unified set of migrations. ADDITIVE ONLY — never deletes.
#
# Usage:
#   ./scripts/sync-migrations.sh
#
# What it does:
#   1. Scans migration directories in all 3 projects
#   2. Copies any missing migration files to the projects that lack them
#   3. Reports what was added
#   4. Optionally runs `php artisan migrate` on live containers
#
# What it does NOT do:
#   - Delete any files
#   - Drop any tables
#   - Modify any existing migration files
#   - Run seeders
# =============================================================================

set -e

# Project paths
COMMON="/root/CascadeProjects/common-portal-platform/src/database/migrations"
XRAMP="/root/CascadeProjects/xramp.io/src/database/migrations"
DDNOW="/root/CascadeProjects/directdebit.now/src/database/migrations"

PROJECTS=("$COMMON" "$XRAMP" "$DDNOW")
PROJECT_NAMES=("common-portal-platform" "xramp.io" "directdebit.now")

echo "============================================="
echo "  Migration Sync — Additive Only"
echo "============================================="
echo ""

# Step 1: Build the unified set of all migrations
UNIFIED_DIR=$(mktemp -d)
for proj in "${PROJECTS[@]}"; do
    if [ -d "$proj" ]; then
        cp -n "$proj"/*.php "$UNIFIED_DIR/" 2>/dev/null || true
    fi
done

TOTAL=$(ls "$UNIFIED_DIR"/*.php 2>/dev/null | wc -l)
echo "Total unified migrations: $TOTAL"
echo ""

# Step 2: Copy missing migrations to each project (additive only)
CHANGES=0
for i in "${!PROJECTS[@]}"; do
    proj="${PROJECTS[$i]}"
    name="${PROJECT_NAMES[$i]}"
    
    if [ ! -d "$proj" ]; then
        echo "⚠️  $name: migration directory not found at $proj — skipping"
        continue
    fi
    
    ADDED=0
    for migration in "$UNIFIED_DIR"/*.php; do
        fname=$(basename "$migration")
        if [ ! -f "$proj/$fname" ]; then
            cp "$migration" "$proj/$fname"
            echo "  ✅ $name: added $fname"
            ADDED=$((ADDED + 1))
            CHANGES=$((CHANGES + 1))
        fi
    done
    
    CURRENT=$(ls "$proj"/*.php 2>/dev/null | wc -l)
    if [ $ADDED -eq 0 ]; then
        echo "  ✅ $name: already up to date ($CURRENT migrations)"
    else
        echo "  📦 $name: added $ADDED migrations (now $CURRENT total)"
    fi
    echo ""
done

# Cleanup
rm -rf "$UNIFIED_DIR"

# Step 3: Verify all match
C_COUNT=$(ls "$COMMON"/*.php 2>/dev/null | wc -l)
X_COUNT=$(ls "$XRAMP"/*.php 2>/dev/null | wc -l)
D_COUNT=$(ls "$DDNOW"/*.php 2>/dev/null | wc -l)

echo "============================================="
echo "  Verification"
echo "============================================="
echo "  common-portal-platform: $C_COUNT migrations"
echo "  xramp.io:               $X_COUNT migrations"
echo "  directdebit.now:        $D_COUNT migrations"

if [ "$C_COUNT" = "$X_COUNT" ] && [ "$X_COUNT" = "$D_COUNT" ]; then
    echo ""
    echo "  ✅ ALL PROJECTS SYNCHRONIZED ($C_COUNT migrations each)"
else
    echo ""
    echo "  ⚠️  MISMATCH DETECTED — review manually"
fi
echo ""

# Step 4: Optionally run migrations on live containers
if [ $CHANGES -gt 0 ]; then
    echo "============================================="
    echo "  Run migrations on live databases?"
    echo "============================================="
    echo "  This runs 'php artisan migrate' (additive only, no data loss)."
    echo ""
    read -p "  Run migrations? [y/N]: " RUN_MIGRATE
    
    if [ "$RUN_MIGRATE" = "y" ] || [ "$RUN_MIGRATE" = "Y" ]; then
        echo ""
        
        # xramp.io (platform-app container)
        if docker ps --format '{{.Names}}' | grep -q "platform-app"; then
            echo "  Running migrate on xramp.io (platform-app)..."
            docker exec platform-app php artisan migrate --force
            echo "  ✅ xramp.io migrated"
        else
            echo "  ⚠️  platform-app container not running — skip xramp.io"
        fi
        
        echo ""
        
        # directdebit.now (directdebit-app container)
        if docker ps --format '{{.Names}}' | grep -q "directdebit-app"; then
            echo "  Running migrate on directdebit.now (directdebit-app)..."
            docker exec directdebit-app php artisan migrate --force
            echo "  ✅ directdebit.now migrated"
        else
            echo "  ⚠️  directdebit-app container not running — skip directdebit.now"
        fi
        
        echo ""
        echo "  ✅ All live databases updated."
    else
        echo "  Skipped. Run manually later:"
        echo "    docker exec platform-app php artisan migrate --force"
        echo "    docker exec directdebit-app php artisan migrate --force"
    fi
fi

echo ""
echo "Done."
