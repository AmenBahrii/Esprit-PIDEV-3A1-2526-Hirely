@echo off
setlocal

cd /d "%~dp0"
echo [Hirely AI] Setting up Python AI service in %CD%

if not defined SSL_CERT_FILE if exist "C:\xampp\php\extras\ssl\cacert.pem" (
    set "SSL_CERT_FILE=C:\xampp\php\extras\ssl\cacert.pem"
    echo [Hirely AI] Using TLS certificate bundle: C:\xampp\php\extras\ssl\cacert.pem
)

where py >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    set "PYTHON_CMD=py -3"
) else (
    where python >nul 2>nul
    if %ERRORLEVEL% NEQ 0 (
        echo [Hirely AI] ERROR: Python was not found. Install Python 3.10+ and retry.
        exit /b 1
    )
    set "PYTHON_CMD=python"
)

if not exist ".venv" (
    echo [Hirely AI] Creating virtual environment...
    %PYTHON_CMD% -m venv .venv
    if %ERRORLEVEL% NEQ 0 (
        echo [Hirely AI] ERROR: Failed to create virtual environment.
        exit /b 1
    )
)

call ".venv\Scripts\activate.bat"
if %ERRORLEVEL% NEQ 0 (
    echo [Hirely AI] ERROR: Failed to activate virtual environment.
    exit /b 1
)

echo [Hirely AI] Upgrading pip...
python -m pip install --use-feature=truststore --upgrade pip
if %ERRORLEVEL% NEQ 0 (
    echo [Hirely AI] ERROR: pip upgrade failed.
    exit /b 1
)

echo [Hirely AI] Installing dependencies from requirements.txt...
python -m pip install --use-feature=truststore -r requirements.txt
if %ERRORLEVEL% NEQ 0 (
    echo [Hirely AI] ERROR: Dependency installation failed.
    exit /b 1
)

echo [Hirely AI] Verifying imports...
python -c "import fastapi, pydantic, sentence_transformers; print('Python AI dependencies OK')"
if %ERRORLEVEL% NEQ 0 (
    echo [Hirely AI] ERROR: Import verification failed.
    exit /b 1
)

echo [Hirely AI] Setup complete.
echo [Hirely AI] Start the service with:
echo     .venv\Scripts\activate
echo     uvicorn app:app --host 127.0.0.1 --port 8008
exit /b 0
