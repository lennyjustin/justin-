# Construction Estimator

A Kenya-first construction planning estimator. The web interface is React and JSX, the API and PostgreSQL persistence run on Node.js/Express, and a small Python HTTP service validates and calculates estimate totals. It supports preliminary estimates, accounts, saved estimates, private material-rate overrides, and print-friendly summaries.

## Requirements

- Node.js 22 or later and npm
- Python 3.12 or later (calculator uses only the Python standard library)
- PostgreSQL 14 or later

## Run locally with Docker Compose

1. Install Docker Desktop and start it.
2. From this folder, run `docker compose up --build`.
3. Open `http://localhost:3000`.

Compose starts PostgreSQL, the Python calculator, and the Node/React app. The database schema and illustrative sample rates are applied by the Node service at startup. The Compose database password and session secret are for local development only.

## Run services without Docker

1. Start a PostgreSQL database and set `DATABASE_URL` to its connection URL.
2. In one terminal, run `python calculator/calculator.py` (the service listens on port 8001).
3. In a second terminal, set a private `SESSION_SECRET` of at least 32 characters, run `npm install` and `npm run build`, then `npm start` (the API listens on port 3000).
4. In a third terminal, run `npm run dev` and open `http://localhost:5173`. Vite proxies `/api` requests to the Node API.

Use the same database URL and session secret for all Node instances. Set `ESTIMATE_SERVICE_URL` if the Python calculator does not listen on `http://127.0.0.1:8001`. The Node API creates its tables and seeds sample rates on startup.

Run `npm test` and `python -m unittest discover -s calculator -p "test_*.py" -v` to run the client and calculator test suites.

## Deploy to Render

The `render.yaml` Blueprint provisions a Node/React Docker web service, a private Python calculator service, and a PostgreSQL database in Singapore. Review the service and database plans and charges before confirming. Back up an existing database before applying infrastructure changes. Existing PostgreSQL data remains only if Render retains and reuses that database; data in a local MySQL installation is not imported.

Push this folder to a Git repository, then create a Render Blueprint from that repository and apply `render.yaml`. The Node service generates its session secret and uses Render's private network to call the Python service.

## Project layout

- `client/`: React pages, navigation, API client, and browser entry point.
- `server/`: Express API, authentication, sessions, and PostgreSQL persistence.
- `calculator/`: Python calculation service and unit tests.
- `assets/`: existing CSS, JavaScript, and supplied project-concept images.
- `database/schema.postgresql.sql`: idempotent PostgreSQL tables and sample material rates.

## Estimate assumptions

The calculator applies simplified per-square-metre factors to adjusted floor area. Project type changes the scope factor; finish level changes paint and labour allowances; transport and contingency are configurable. Seeded prices are illustrative, not verified market prices. This is not a room-by-room take-off or a professional bill of quantities. Confirm designs, quantities, rates, site conditions, and local building requirements with qualified professionals and suppliers before making purchasing or financial decisions.

## Production checklist

Set strong database credentials and a private session secret, use HTTPS, configure backups and private error logging, and have a qualified construction professional review the assumptions. Password recovery, subscriptions, and payments are not implemented.
