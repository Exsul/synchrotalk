#!/bin/bash
# Initialise repository for development or deploy on vps
composer update
ln -s vendor/phoxy/phoxy/ phoxy
ln -s ../vendor/phoxy/snippets ejs/snippets
touch secret.yaml
mkdir networks

echo "Do not forget:
- fill secret.yaml
- seed PostgreSQL database
- CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"
- download latest plugin.connector
- download all required plugins
"
