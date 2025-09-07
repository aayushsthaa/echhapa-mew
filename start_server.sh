#!/bin/bash
export PGHOST="${PGHOST}"
export PGDATABASE="${PGDATABASE}"
export PGUSER="${PGUSER}"
export PGPASSWORD="${PGPASSWORD}"
export PGPORT="${PGPORT}"

echo "Starting PHP server with database: $PGDATABASE"
php -S 0.0.0.0:5000