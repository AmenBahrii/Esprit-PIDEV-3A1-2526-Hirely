#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")"
echo "[Hirely AI] Setting up Python AI service in $(pwd)"

if [ -z "${SSL_CERT_FILE:-}" ] && [ -f "/etc/ssl/certs/ca-certificates.crt" ]; then
    export SSL_CERT_FILE="/etc/ssl/certs/ca-certificates.crt"
    echo "[Hirely AI] Using TLS certificate bundle: $SSL_CERT_FILE"
elif [ -z "${SSL_CERT_FILE:-}" ] && [ -f "/opt/homebrew/etc/ca-certificates/cert.pem" ]; then
    export SSL_CERT_FILE="/opt/homebrew/etc/ca-certificates/cert.pem"
    echo "[Hirely AI] Using TLS certificate bundle: $SSL_CERT_FILE"
fi

if command -v python3 >/dev/null 2>&1; then
    PYTHON_CMD=python3
elif command -v python >/dev/null 2>&1; then
    PYTHON_CMD=python
else
    echo "[Hirely AI] ERROR: Python was not found. Install Python 3.10+ and retry."
    exit 1
fi

if [ ! -d ".venv" ]; then
    echo "[Hirely AI] Creating virtual environment..."
    "$PYTHON_CMD" -m venv .venv
fi

# shellcheck disable=SC1091
. ./.venv/bin/activate

echo "[Hirely AI] Upgrading pip..."
python -m pip install --use-feature=truststore --upgrade pip

echo "[Hirely AI] Installing dependencies from requirements.txt..."
python -m pip install --use-feature=truststore -r requirements.txt

echo "[Hirely AI] Verifying imports..."
python -c "import fastapi, pydantic, sentence_transformers; print('Python AI dependencies OK')"

echo "[Hirely AI] Setup complete."
echo "[Hirely AI] Start the service with:"
echo "    . ./.venv/bin/activate"
echo "    uvicorn app:app --host 127.0.0.1 --port 8008"
