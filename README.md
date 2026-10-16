# Construction Estimator

A Kenya-first construction planning estimator built with PHP, MySQL, HTML, CSS and vanilla JavaScript. Its home page explains the estimator's purpose, the information users enter, what the cost breakdown includes, how to use the result, and the limitations of the planning assumptions. The first version supports preliminary estimates, user accounts, saved estimates and private material-price overrides.

## Requirements

- PHP 8.1 or later with PDO MySQL enabled
- MySQL 8.0+ or a compatible MariaDB version
- Apache, Nginx, or PHP's local development server

## Local setup (XAMPP on Windows)

1. Copy this folder into `C:\xampp\htdocs\jengaestimate`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin and import `database/schema.sql`. It creates the `jengaestimate` database and inserts clearly illustrative sample rates.
4. Review the database connection values at the top of `config.php`. Defaults are for a typical local XAMPP setup (`root` with no password); set `JENGA_DB_HOST`, `JENGA_DB_NAME`, `JENGA_DB_USER` and `JENGA_DB_PASS` environment variables for other installations.
5. Visit `http://localhost/jengaestimate/`, create an account and run an estimate.

To use PHP's built-in development server, run `php -S localhost:8000` from this folder and open `http://localhost:8000/`. Make sure MySQL is running and the database is imported first.

## Deploy to Render

The `render.yaml` Blueprint provisions a Docker-based PHP web service and a Render PostgreSQL database in Singapore. The service starts Apache on Render's port `10000` and applies the idempotent PostgreSQL schema and illustrative sample rates at startup.

1. Push this project to a GitHub or GitLab repository.
2. In Render, choose **New > Blueprint**, connect the repository, and apply `render.yaml`.
3. Review the web-service and database plans and any charges before confirming. The Blueprint uses paid plans for persistent hosting and database storage.
4. Wait for the first deploy to become healthy, then open the `onrender.com` URL shown for the web service.

The Render deployment uses its own empty PostgreSQL database; it does not copy users, saved estimates, or custom rates from a local MySQL installation. Export and migrate local data separately if it needs to be retained.

## Deploy to Vercel

Vercel can run this PHP application using its Container Runtime and the root-level `Dockerfile.vercel`. The container requires a PostgreSQL database URL; Vercel does not provide a built-in PostgreSQL database. Connect a PostgreSQL provider through the Vercel Marketplace or configure an existing PostgreSQL `DATABASE_URL` in the Vercel project's environment variables before deployment.

1. In Vercel, choose **Add New > Project** and import the GitHub repository containing this project.
2. Select the `construction-estimator` branch if importing the existing repository, and use the `JengaEstimate` folder as the Root Directory when the repository contains more than this project.
3. Connect a PostgreSQL provider and make its `DATABASE_URL` available to the project. Do not commit database credentials.
4. Deploy. Vercel builds `Dockerfile.vercel`; the container applies `database/schema.postgresql.sql` at startup and serves the PHP application through Apache.

The Vercel PostgreSQL database starts empty. Local MySQL users, saved estimates and custom rates are not migrated automatically. When `DATABASE_URL` is set, PHP sessions are stored in PostgreSQL so sign-in can survive container restarts and scale-out.

## Features

- Responsive landing page, estimator, pricing, account, project dashboard and rate-management pages.
- CSRF-protected forms, password hashing, PDO prepared statements and user-specific price overrides.
- Server-side estimate validation and calculation; saved estimates are recalculated on the server rather than trusting submitted totals.
- Saved estimates open as itemized summaries with project-specific illustrations and can be printed or saved as PDFs.
- Homepage project concepts use customer-supplied images and clearly labeled indicative budget ranges.

## Estimation assumptions

The calculator applies simplified per-square-metre quantity factors to adjusted floor area. Project type changes a rough scope factor; finish level changes paint allowance and labour percentage; transport and contingency are configurable. The seeded prices are placeholders and are not current, verified market prices. This is not a room-by-room take-off engine or professional bill of quantities. Confirm design, quantities, rates, soil/site conditions and local building requirements with qualified construction professionals and suppliers before making purchasing or financial decisions.

## Before a public launch

Use HTTPS, set strong database credentials, disable detailed PHP errors for visitors while logging them privately, configure backups and email/password recovery, add abuse controls, and have a qualified quantity surveyor or other construction professional review the calculation assumptions. Pricing pages are proposals only; subscriptions and payment processing are not implemented.
