#!/bin/bash

# Initialize Let's Encrypt SSL certificates for xramp.io
# This script sets up SSL certificates using certbot and Let's Encrypt

set -e

domains=(xramp.io *.xramp.io)
rsa_key_size=4096
data_path="./certbot"
email="" # Add your email address here for renewal notifications
staging=0 # Set to 1 for testing to avoid rate limits

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Let's Encrypt SSL Setup for xramp.io${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""

# Check if email is set
if [ -z "$email" ]; then
  echo -e "${YELLOW}Warning: Email is not set in the script.${NC}"
  read -p "Enter email address for Let's Encrypt notifications: " email
fi

# Create certbot directories
if [ -d "$data_path" ]; then
  read -p "Existing certbot data found. Remove and continue? (y/N) " decision
  if [ "$decision" != "Y" ] && [ "$decision" != "y" ]; then
    exit
  fi
fi

echo -e "${GREEN}Creating certbot directories...${NC}"
mkdir -p "$data_path/conf"
mkdir -p "$data_path/www"

# Download recommended TLS parameters
echo -e "${GREEN}Downloading recommended TLS parameters...${NC}"
if [ ! -e "$data_path/conf/options-ssl-nginx.conf" ] || [ ! -e "$data_path/conf/ssl-dhparams.pem" ]; then
  echo "Downloading options-ssl-nginx.conf..."
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > "$data_path/conf/options-ssl-nginx.conf"
  echo "Downloading ssl-dhparams.pem..."
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem > "$data_path/conf/ssl-dhparams.pem"
fi

# Create dummy certificate for initial nginx startup
echo -e "${GREEN}Creating dummy certificate for ${domains[0]}...${NC}"
path="/etc/letsencrypt/live/${domains[0]}"
mkdir -p "$data_path/conf/live/${domains[0]}"
docker compose -f docker-compose.ssl.yml run --rm --entrypoint "\
  openssl req -x509 -nodes -newkey rsa:$rsa_key_size -days 1\
    -keyout '$path/privkey.pem' \
    -out '$path/fullchain.pem' \
    -subj '/CN=localhost'" certbot

echo -e "${GREEN}Starting nginx...${NC}"
docker compose -f docker-compose.ssl.yml up --force-recreate -d app

echo -e "${GREEN}Deleting dummy certificate...${NC}"
docker compose -f docker-compose.ssl.yml run --rm --entrypoint "\
  rm -Rf /etc/letsencrypt/live/${domains[0]} && \
  rm -Rf /etc/letsencrypt/archive/${domains[0]} && \
  rm -Rf /etc/letsencrypt/renewal/${domains[0]}.conf" certbot

# Request Let's Encrypt certificate
echo -e "${GREEN}Requesting Let's Encrypt certificate for ${domains[*]}...${NC}"

# Join domains for certbot
domain_args=""
for domain in "${domains[@]}"; do
  domain_args="$domain_args -d $domain"
done

# Select appropriate email arg
case "$email" in
  "") email_arg="--register-unsafely-without-email" ;;
  *) email_arg="--email $email" ;;
esac

# Enable staging mode if set
if [ $staging != "0" ]; then
  staging_arg="--staging"
  echo -e "${YELLOW}Running in staging mode (test certificates)${NC}"
else
  staging_arg=""
fi

# Request certificate
docker compose -f docker-compose.ssl.yml run --rm --entrypoint "\
  certbot certonly --dns-digitalocean \
    --dns-digitalocean-credentials /etc/letsencrypt/digitalocean.ini \
    $staging_arg \
    $email_arg \
    $domain_args \
    --rsa-key-size $rsa_key_size \
    --agree-tos \
    --force-renewal" certbot

echo -e "${GREEN}Reloading nginx...${NC}"
docker compose -f docker-compose.ssl.yml exec app nginx -s reload

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  SSL Certificate Setup Complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "Certificates are installed and nginx has been reloaded."
echo -e "Auto-renewal is configured via certbot container."
