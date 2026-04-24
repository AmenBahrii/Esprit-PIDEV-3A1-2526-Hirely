# Hirely Forum AI Service

This is the local Python scoring sidecar used by the Symfony forum moderation engine in `hirely`.

Start it from the Symfony app folder:

```powershell
cd c:\Users\Firas\OneDrive\Desktop\hirely2\hirely\ai_service
pip install -r requirements.txt
uvicorn app:app --host 127.0.0.1 --port 8008
```

Endpoints:
- `GET /health`
- `POST /score`

Main Symfony environment variables:
- `PY_AI_URL=http://127.0.0.1:8008`
- `PERSPECTIVE_API_KEY=...`
- `SAFE_BROWSING_API_KEY=...`
- `SAFE_BROWSING_TIMEOUT_MS=8000`
- `GEMINI_API_KEY=...`
