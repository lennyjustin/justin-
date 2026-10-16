# FleetCare EA

FleetCare EA is a small PHP/MySQL fleet maintenance MVP for local XAMPP use. It includes account registration and sign-in, a responsive dashboard, vehicle records, scheduled and completed maintenance, fuel entries, document expiry tracking, and cost summaries.

Visitors to `index.php` see a public home page explaining the fleet-management features, with sign-in and account-registration links. Signed-in users continue directly to their fleet dashboard. The home page uses the supplied FleetCare EA fleet image in its hero background.

## Requirements

- XAMPP with Apache, MySQL/MariaDB, and PHP 8.1 or newer
- PHP extensions `pdo_mysql` and `mbstring` enabled
- A modern browser with JavaScript enabled

The website must be opened through Apache. Opening `index.php` as a local file or using VS Code Live Server will not run PHP.

## Run with XAMPP on Windows

1. Copy this project folder to `C:\xampp\htdocs\FleetCareEA\`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`.
4. Import this project's `schema.sql` file. It creates the `fleetcare_ea` database and tables.
5. Open `http://localhost/FleetCareEA/register.php` and create the first account.
6. Sign in at `http://localhost/FleetCareEA/login.php`, add a vehicle, then enter service, fuel, and document records.

For a different local database setup, set these environment variables for Apache before starting it:

- `FLEETCARE_DB_HOST` (default `127.0.0.1`)
- `FLEETCARE_DB_PORT` (default `3306`)
- `FLEETCARE_DB_NAME` (default `fleetcare_ea`)
- `FLEETCARE_DB_USER` (default `root`)
- `FLEETCARE_DB_PASSWORD` (default empty, as on a fresh XAMPP installation)

The database defaults are only intended for local development. Set a dedicated database user and a strong password before exposing a deployment to a network. Never use the default XAMPP root account in production.

## Deploy to Render

The `render.yaml` and `Dockerfile` files configure a PHP 8.3 Apache web service. The application requires MySQL/MariaDB; Render's PostgreSQL database is not compatible with its schema and queries, so provide a separately hosted MySQL-compatible database that accepts connections from Render.

1. Push this project folder to a Git repository. In the Render Dashboard, choose **New > Blueprint** and connect that repository.
2. Set the blueprint's **Root Directory** to `Vehicle Maintenance Tracker` if the project folder is nested in the repository. If this folder is the repository root, leave it blank.
3. Add a database using a MySQL/MariaDB host. Enter its hostname, database name, username, and password as the `FLEETCARE_DB_HOST`, `FLEETCARE_DB_NAME`, `FLEETCARE_DB_USER`, and `FLEETCARE_DB_PASSWORD` environment values for the web service. Set `FLEETCARE_DB_PORT` too if the database does not use port `3306`. Keep credentials in Render environment settings; do not commit them.
4. Import `schema.sql` into that database using the provider's database console or client. The script creates the `fleetcare_ea` database, so if the provider creates a database for you, select it and import the table definitions without the initial `CREATE DATABASE` and `USE fleetcare_ea` statements.
5. Deploy the service and open its `onrender.com` URL. Create the first FleetCare account there.

The Render service uses ephemeral local storage; the app's records are stored in MySQL. Use one web instance unless shared PHP session storage is configured.

## Deploy to Vercel

Vercel's Container Runtime (currently beta) can run this PHP/Apache app using `Dockerfile.vercel`. The project still requires a separately hosted MySQL-compatible database; Vercel Postgres is not compatible with the MySQL schema and queries.

1. Push the contents of this project folder to a Git repository and import it into Vercel, setting the project root to this folder if it is nested in the repository. Alternatively, deploy this folder with the Vercel CLI from an authenticated local terminal.
2. Add the `FLEETCARE_DB_HOST`, `FLEETCARE_DB_PORT`, `FLEETCARE_DB_NAME`, `FLEETCARE_DB_USER`, and `FLEETCARE_DB_PASSWORD` environment variables in Vercel's project settings. Use the public TCP endpoint from your MySQL provider and ensure it permits connections from Vercel.
3. Import the tables from `schema.sql` into the provider-created database. If you cannot create databases on that server, omit the initial `CREATE DATABASE` and `USE fleetcare_ea` statements when importing.
4. Deploy and open the assigned `vercel.app` URL.

Vercel containers may scale across instances and have ephemeral local storage. FleetCare currently uses PHP's default file-based sessions, so this deployment may not retain sign-in reliably across different instances. For dependable production use, configure shared session storage or use a host designed for persistent PHP applications. Do not store fleet records on the container filesystem.

## Data and security notes

- Passwords are stored using PHP's `password_hash`; successful sign-in rotates the session ID.
- Data queries are scoped to the signed-in user and use PDO prepared statements.
- State-changing API requests require the session CSRF token.
- Vehicle registration numbers are unique within each account.
- Fuel totals are calculated from litres multiplied by price per litre. Odometer values on fuel entries update the vehicle's current odometer and are checked against its previous reading.
- The dashboard flags active vehicles within 1,000 km of their next service odometer and highlights scheduled maintenance and document expiries. Document reminders show 30-, 14-, and 7-day thresholds and overdue status. These are in-app reminders only; this MVP does not send email or SMS notifications.
- Fleet reports compare monthly fuel and completed-maintenance costs per vehicle. Distance is estimated from differences between fuel-entry odometer readings within the month; cost per kilometre is not shown when there is no recorded distance, and it excludes expenses not entered in FleetCare.
- Workspace data is stored in the MySQL database; the browser does not use local storage as the source of truth.

## Current MVP limits

This is a local-development starting point, not a production-ready hosted service. It does not include email verification, password reset, role management, automated notifications, M-Pesa, subscription enforcement, GPS, document file uploads, or advanced exports. The CSV export currently contains the vehicle list.

PHP/MySQL runtime validation can be done in XAMPP or against the configured production database.
