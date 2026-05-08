# AI Service

This is the AI scoring service used by the Hirely Symfony forum website.

## Setup

Do not install Python libraries during normal Symfony runtime. Prepare the AI service once from `web/ai_service`.

Windows:

```bat
setup_python_ai.bat
```

Linux/macOS:

```bash
sh setup_python_ai.sh
```

The setup script creates a local `.venv`, upgrades pip, installs `requirements.txt`, and verifies imports.

## Start

```bash
.venv/Scripts/activate   # Windows PowerShell/cmd: .venv\Scripts\activate
uvicorn app:app --host 127.0.0.1 --port 8008
```

Available endpoints:
- `POST /score` -> relevance/category/quality/duplicateSimilarity and reasons used by Java moderation policy
- `GET /health` -> service health + active model

## Fallback Behavior

If `sentence-transformers` or the model is unavailable, `/health` reports the missing dependency and `/score` returns HTTP 503 with a clear setup message. The Symfony forum catches that failure and marks the content `PENDING` for manual moderation instead of crashing.

Website / desktop environment variables:

- `PERSPECTIVE_API_KEY=<your_key>`
- `PY_AI_URL=http://127.0.0.1:8008`
