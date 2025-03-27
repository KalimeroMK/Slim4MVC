# Slim 4 MVC Starter Kit with Docker

This project is created with the Slim 4 framework uses Docker for configuration and development and flows MVC pattern . It includes support for migrations using Illuminate Database.

## Setting Up the Project

1. Clone the repository to your local machine:
    ```bash
    git clone https://github.com/KalimeroMK/Slim4MVC
    ```

2. Ensure you have Docker and Docker Compose installed on your system. For installation, check [Docker's Documentation](https://docs.docker.com/get-docker/).

3. Start the Docker containers with the command:
    ```bash
    docker-compose up -d
    ```

4. This will start the Docker container for the application and the database. The project will be available at [http://localhost:81](http://localhost:81).

## Creating model and migration 

1. **Creating a New Model:**
   To create a new migration, use the following command:
    ```bash
    php cli.php make-model ModelName
    ```
   This will create a new model file in App\Models\ModelName

2. **Creating a New Migration:**

   ```bash
    php cli.php make-model ModelName -m
    ```
   This will create a new migration file. You can edit it and add new migrations for your database.

3. **Running Migrations:**
   To run database migrations, use the same command:
    ```bash
    php run_migrations.php
    ```

   If the migration has already been run, the system will skip it without an error.

4. **Creating controllers:**
   To create controller you can use:
    ```bash
     php cli.php make:controller ControllerName
    ```
   
5. **List routes:**
   To list all you can use:
    ```bash
     php cli.php list-routes
    ```

### Project Structure

The project has the following structure:

```
│── app/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Models/
│   ├── config.php
│── bootstrap/
│   ├── app.php
│   ├── database.php
│── public/
│   ├── index.php
│── database/
│   ├── migrations
│── routes/
│   ├── web.php
│── .env
│── composer.json
```

## Docker Configuration

### `docker-compose.yml`

The project uses Docker Compose to set up all necessary containers. The configuration includes:

- **app**: Container for the application which is based on PHP and Slim 4.
- **db**: Container for MariaDB.
- **nginx**: Container for Nginx.

### `Dockerfile`

Contains configuration for PHP, Slim 4, and all necessary extensions to run the application in Docker.

### Nginx Configuration

The `nginx.conf` file is used to configure the Nginx server which forwards PHP requests to PHP-FPM.

## Development Steps

1. **Configuring Nginx:**
   If you want to configure Nginx, you can edit `docker/nginx/default.conf` to set up the desired server.

2. **Configuring Docker:**
   If needed, you can modify the Docker configuration in `docker-compose.yml` to add new services or change existing ones.

## Frequently Asked Questions

1. **How do I shut down Docker?**
   To shut down Docker, run the following command:
    ```bash
    docker-compose down
    ```

2. **What if the database is not working?**
   Check if the database configuration is correct in the `.env` file. If using Docker, ensure the database container is up and running.

## License

This project is licensed under the MIT license. See `LICENSE` for more information.