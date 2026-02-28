# EduTracker API - Docker Setup

This project uses Docker Compose to run a Laravel API with MySQL and phpMyAdmin.

---

## **Prerequisites**

* Docker
* Docker Compose
* Composer (optional if using Docker to create Laravel project)
* VSCode (recommended for development)

---

## **1. Configure environment variables**

Create a `.env` file in the project root with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

> Ensure these match the `docker-compose.yml` MySQL service environment variables.

---

## **2. Build Docker containers**

```bash
docker compose build
```

---

## **3. Start Docker containers**

```bash
docker compose up -d
```

Check containers are running:

```bash
docker compose ps
```

---

## **4. Initialize Laravel project (if not created yet)**

Enter the app container:

```bash
docker compose exec app bash
```

Create Laravel project:

```bash
composer create-project laravel/laravel .
```

Set permissions:

```bash
chown -R www-data:www-data /var/www
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

Generate application key:

```bash
php artisan key:generate
```

Exit container:

```bash
exit
```

---

## **5. Access the services**

* **Laravel app (Nginx):** [http://localhost:8000](http://localhost:8000)
* **phpMyAdmin:** [http://localhost:8080](http://localhost:8080)

Login to phpMyAdmin with:

* Host: `mysql`
* User: `DB_USERNAME` from `.env`
* Password: `DB_PASSWORD` from `.env`

---

## **6. Troubleshooting**

* **504 Gateway Timeout:**
  Usually occurs if MySQL container failed to start. Make sure `MYSQL_ROOT_PASSWORD` is set in `docker-compose.yml`:

```yaml
environment:
  MYSQL_ROOT_PASSWORD: rootsecret
  MYSQL_DATABASE: ${DB_DATABASE}
  MYSQL_USER: ${DB_USERNAME}
  MYSQL_PASSWORD: ${DB_PASSWORD}
```

* **MySQL initialization issues:**
  If MySQL fails after changing `.env` or credentials, remove the old volume and restart:

```bash
docker compose down -v
docker compose up -d
```

* **Laravel file permissions:**
  Ensure `storage` and `bootstrap/cache` are writable by Nginx/PHP:

```bash
chown -R www-data:www-data /var/www
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

---

## **7. Common Docker Commands**

* Stop containers:

```bash
docker compose down
```

* Restart containers:

```bash
docker compose restart
```

* View logs:

```bash
docker compose logs -f
```

---

## **8. VSCode Extensions (Optional for Laravel)**

* PHP Intelephense
* Laravel Artisan
* Laravel Blade Snippets
* PHP Debug
* DotENV
* MySQL (for connecting to DB from VSCode)

---

Now you can run `docker compose up -d` and your Laravel development environment will be ready to use.
