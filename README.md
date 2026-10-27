# RideTrack — Smart Bus Travel and Tracking System

RideTrack is a PHP and MySQL web application for planning bus trips, booking tickets, tracking buses, and accessing travel and road-safety services. The project includes passenger-facing features and separate portals for transport operators, staff, drivers, administrators, and police users.

## Features

- **Local and long-distance travel:** Search for bus routes by origin, destination, travel type, and date.
- **Trip planning and bookings:** View trips, choose seats where supported, manage bookings, and access e-tickets.
- **Live bus tracking:** View bus locations and trip status using the project's tracking pages and map integrations.
- **Travel card and fares:** Manage travel-card-related features and fare information.
- **Student portal:** Student verification and student-fare support.
- **Road reports:** Submit and review reports about road conditions and incidents.
- **Emergency assistance:** SOS features and emergency-contact information.
- **Commuter groups:** Join route-related groups and share posts.
- **Role-based portals:** Passenger, driver, operator, staff, administrator, and police workflows.
- **Language support:** English and Bangla interface options.

> Features that depend on external map services, payment integrations, GPS updates, or server configuration may require additional setup before they work fully.

## Technology Stack

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Maps:** Leaflet/OpenStreetMap integrations are present; some pages may also use Google Maps.
- **Local development:** XAMPP, WAMP, MAMP, or another PHP/MySQL environment can be used.

Check the project files and your local configuration for exact runtime versions and any external API requirements.

## Requirements

- PHP with PDO MySQL support
- MySQL or MariaDB
- Apache or another PHP-compatible web server
- A modern web browser
- Optional: map-service API credentials for features that require them

## Setup (XAMPP example)

1. Install and start **Apache** and **MySQL** from XAMPP.
2. Extract the project folder named `ridetrack` into the web server's document root, for example:

   ```text
   C:\xampp\htdocs\ridetrack
   ```

3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Create/import the database by running the project's `database.sql` file. The script creates the `ridetrack` database and initial tables and sample records.
5. Open `config.php` and confirm the database connection settings match your local environment. The current project configuration uses a local MySQL connection with database `ridetrack`, username `root`, and an empty password; change these values if your environment differs.
6. If a feature reports a missing table or column, inspect the SQL migration files included in the project and apply the relevant migration to your local database. Back up the database before applying updates, and do not run every SQL file blindly because some files may be alternatives or older migrations.
7. Visit:

   ```text
   http://localhost/ridetrack/
   ```

## Database and configuration notes

- Main database setup file: `database.sql`
- Additional schema updates: `update*.sql`, `*_local.sql`, and SQL files in `sql/` and `extra_sql/`
- Database connection and shared application functions: `config.php`
- Do not commit real passwords, payment credentials, private API keys, or production database details to a public repository.
- Configure external API keys through a safe local configuration method before using any feature that requires them. Never publish secret keys in screenshots or documentation.

## Main Pages and Modules

| File | Purpose |
|---|---|
| `index.php` | Home page and trip-search entry point |
| `search.php` | Search buses and trips |
| `plan.php` | Plan a trip |
| `book.php` | Booking workflow |
| `my_bookings.php` | View a passenger's bookings |
| `ticket.php`, `verify_ticket.php` | Ticket display and verification-related workflows |
| `track.php`, `track_google.php` | Bus tracking pages |
| `admin.php`, `admin_routes.php` | Administrative and route management |
| `operator.php` | Operator portal |
| `driver.php` | Driver portal |
| `staff.php` | Staff portal |
| `police.php`, `police_control.php` | Police and control workflows |
| `student.php`, `student_check.php` | Student portal and verification-related workflows |
| `complaints.php`, `reports.php` | Road reports and related records |
| `sos.php`, `sos_app.php`, `sos_send.php` | Emergency assistance workflows |
| `database.sql` | Initial database schema and demo data |

The table above is a guide to the files in the project; individual pages may depend on shared functions, database migrations, and application configuration.

## Demo Accounts

The initial `database.sql` file inserts demo accounts for several roles. If you use these accounts, do so only in a local development database and change or remove demo credentials before deployment. Do not use the seeded demo passwords in production.

For safety, this README does not reproduce account passwords. Review the seed data locally if you need the demo login details.

## Testing and Troubleshooting

- Confirm Apache and MySQL are running.
- Confirm the `ridetrack` database exists and the database credentials in `config.php` are correct.
- If a page reports a missing table or column, check whether the matching SQL migration has been applied.
- Check the PHP/Apache error log for PHP errors and the browser console for JavaScript or map-loading errors.
- Map and GPS features may need internet access, correct API configuration, and valid location data.
- Payment callbacks and other third-party integrations should be tested only in their intended sandbox or local test environment.
- The presence of a feature in the source code does not mean it has been fully tested. Record test results only after running the relevant checks.

## Security Notes

- Use unique, strong credentials for any deployed environment.
- Replace development database settings before deployment.
- Keep API keys and payment secrets out of source control.
- Use HTTPS in production and restrict access to administrative portals.
- Do not expose real personal information or uploaded private documents in a public deployment.
- Review file-upload handling, access controls, session security, and database permissions before deploying publicly.

## Project Structure (overview)

```text
ridetrack/
├── index.php              # Home page
├── config.php             # Database connection and shared functions
├── database.sql           # Initial database schema and seed data
├── update*.sql             # Database update scripts
├── sql/                   # Additional SQL updates
├── extra_sql/             # Additional SQL scripts
├── *.php                  # Application pages and modules
├── style.css, app.css     # Stylesheets
├── app.js                 # Frontend JavaScript
├── uploads/               # Uploaded files (treat private files carefully)
└── _archive/              # Archived/older files
```

## Current Status

This README is based on the supplied source-code archive. It describes the apparent application features and a typical local setup; it does **not** certify that every feature, migration, payment integration, map service, or test has been successfully executed.

## Author / Course Information

- **Project:** RideTrack — Smart Bus Travel and Tracking System
- **Course:** [Add course name/code]
- **Team / Author:** [Add team ID and member names]
- **Institution:** [Add institution name]

---

For development, testing, or submission, update the placeholders above and keep the setup instructions aligned with the exact database migrations and configuration used by your team.
