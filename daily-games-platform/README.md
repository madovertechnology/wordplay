# Daily Games Platform

A Laravel-based web platform that hosts multiple simple daily games with a shared user system. The platform features games like Daily Word Scramble, with more games to be added in the future.

## Features

- Multiple daily games with shared user authentication
- Daily, monthly, and all-time leaderboards
- Streak tracking for each game
- Gamification with ranks and badges
- Guest play with cookie-based data storage
- Central dashboard to access all games

## Requirements

- PHP 8.2+
- Composer
- PostgreSQL 15+
- Node.js & NPM

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd daily-games-platform
   ```

2. Install PHP dependencies:
   ```
   composer install
   ```

3. Install JavaScript dependencies:
   ```
   npm install
   ```

4. Create a copy of the `.env.example` file:
   ```
   cp .env.example .env
   ```

5. Generate an application key:
   ```
   php artisan key:generate
   ```

6. Configure your database in the `.env` file:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=daily_games_platform
   DB_USERNAME=postgres
   DB_PASSWORD=postgres
   ```

7. Run database migrations:
   ```
   php artisan migrate
   ```

8. Build frontend assets:
   ```
   npm run build
   ```

9. Start the development server:
   ```
   php artisan serve
   ```

## Deployment

The application is configured for deployment on Laravel Cloud with PostgreSQL. The deployment configuration is defined in the `laravel-cloud.yml` file.

## Development

- Run development server: `php artisan serve`
- Watch for frontend changes: `npm run dev`
- Run tests: `php artisan test`

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).