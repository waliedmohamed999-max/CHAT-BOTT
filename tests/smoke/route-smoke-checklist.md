# Smoke Test Checklist

- GET /login returns 200
- POST /api/auth/login authenticates a demo or database user
- GET /marketing-center returns 200 after login
- GET /platform/dashboard returns 200 for platform users
- GET /inbox returns 200 for agents
- GET /api/development-execution returns scan results
- npm run build completes successfully
