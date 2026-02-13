#!/usr/bin/env bash
set -euo pipefail

src_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
dest_dir="/var/www/html"

sudo rsync -a --delete --exclude ".git" --exclude "node_modules" "$src_dir/" "$dest_dir/"

sudo chown -R www-data:www-data "$dest_dir"
sudo find "$dest_dir" -type d -exec chmod 755 {} +
sudo find "$dest_dir" -type f -exec chmod 644 {} +

echo "Deployed to $dest_dir"
