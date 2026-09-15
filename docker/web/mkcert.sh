#!/usr/bin/env bash
#
# Generate locally-trusted TLS certificates for *.aplika.test using mkcert.
#
# Prerequisites:
#   - mkcert installed: apt install mkcert libnss3-tools
#   - Local CA installed: mkcert -install
#
# Usage:
#   ./docker/web/mkcert.sh
#   make certs
#

set -euo pipefail

CERTS_DIR="$(cd "$(dirname "$0")" && pwd)/certs"
DOMAINS="aplika.test *.aplika.test"

# Check if mkcert is installed
if ! command -v mkcert &>/dev/null; then
    echo "❌ mkcert is not installed."
    echo ""
    echo "Install it with:"
    echo "  sudo apt install mkcert libnss3-tools"
    echo ""
    echo "Then install the local CA:"
    echo "  mkcert -install"
    exit 1
fi

# Check if local CA is installed (mkcert creates a root cert in its CA dir)
CAROOT=$(mkcert -CAROOT)
if [ ! -f "$CAROOT/rootCA.pem" ]; then
    echo "❌ mkcert local CA is not installed."
    echo ""
    echo "Run:"
    echo "  mkcert -install"
    exit 1
fi

# Check if certs already exist
if [ -f "$CERTS_DIR/_wildcard.aplika.test.pem" ] && [ -f "$CERTS_DIR/_wildcard.aplika.test-key.pem" ]; then
    echo "✅ Certificates already exist at $CERTS_DIR"
    echo ""
    echo "To regenerate, delete the certs directory first:"
    echo "  rm -rf docker/web/certs"
    exit 0
fi

# Create certs directory
mkdir -p "$CERTS_DIR"

# Generate certificates
echo "🔐 Generating certificates for: $DOMAINS"
mkcert -cert-file "$CERTS_DIR/_wildcard.aplika.test.pem" \
       -key-file "$CERTS_DIR/_wildcard.aplika.test-key.pem" \
       $DOMAINS

echo ""
echo "✅ Certificates generated at $CERTS_DIR"
echo ""
echo "Files created:"
echo "  - _wildcard.aplika.test.pem"
echo "  - _wildcard.aplika.test-key.pem"
echo ""
echo "Restart the web service to use HTTPS:"
echo "  make restart"
