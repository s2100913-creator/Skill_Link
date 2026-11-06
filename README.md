   # SkillLink API

   PHP REST API with a PostgreSQL database (Aiven).

   ## Setup
   1. Copy `lib/config.example.php` to `lib/config.php` and fill in the database values.
      On Vercel, set the environment variables DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD.
   2. Run `database.sql` to create the tables.

   ## Endpoints
   POST /api/authentication/login
   POST /api/authentication/register
   POST /api/authentication/logout
   GET/PATCH/DELETE /api/users, /api/users/{id}
   GET/POST/PATCH/DELETE /api/categories, /api/skills, /api/classes,
   /api/class-users, /api/class-attendances