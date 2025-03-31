# Slim 4 MVC Starter Kit with Eloquent ORM

This project is created with the Slim 4 framework, uses Docker for configuration and development, and follows the MVC pattern. It includes support for migrations using Illuminate Database.

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
    php slim make-model ModelName
    ```
   This will create a new model file in `app/Models/ModelName.php`.

2. **Creating a New Migration:**

   ```bash
    php slim make-model ModelName -m
    ```
   This will create a new migration file. You can edit it and add new migrations for your database.

3. **Running Migrations:**
   To run database migrations, use the same command:
    ```bash
    php run_migrations.php
    ```

   If the migration has already been run, the system will skip it without an error.

4. **Creating controllers:**
   To create a controller, you can use:
    ```bash
     php slim make:controller ControllerName
    ```

5. **List routes:**
   To list all routes, you can use:
    ```bash
     php slim list-routes
    ```

### Project Structure

The project has the following structure:

```
│── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   ├── Models/
│   ├── View/
│   │   ├── Blade.php
│   │   ├── BladeFactory.php
│   │   ├── BladeAssetsHelper.php
│   │   ├── BladeViewHelper.php
│   ├── Support/
│   │   ├── Helpers.php
│   ├── config.php
│── bootstrap/
│   ├── app.php
│   ├── database.php
│── public/
│   ├── index.php
│── database/
│   ├── migrations/
│── routes/
│   ├── web.php
│── resources/
│   ├── views/
│── storage/
│   ├── cache/
│── .env
│── composer.json
```

## Blade Templating

This project uses Blade templating engine similar to Laravel. To render a Blade view, you can use the `view` function in your controllers.

Example:
```php
public function index(Request $request, Response $response): Response
{
    $data = [
        'title' => 'Welcome',
        'content' => 'Welcome to Slim 4 with Blade!',
    ];

    return view($response, 'welcome', $data);
}
```

## CSRF Protection

To handle CSRF protection, this project uses the Slim CSRF package. The CSRF middleware is automatically added to your routes.

Example route with CSRF middleware:
```php
$app->post('/your-endpoint', 'YourController:yourMethod')->add('csrf');
```

## Session Management

This project uses a session helper class for session management. You can register the session helper globally or instantiate it in your routes.

### Registering Session Helper Globally

```php
$container = new \DI\Container();

// Register globally to app
$container->set('session', function () {
  return new \SlimSession\Helper();
});
\Slim\Factory\AppFactory::setContainer($container);
```

### Using Session Helper in Routes

```php
$app->get('/', function ($req, $res) {
  // or $this->get('session') if registered
  $session = new \SlimSession\Helper();

  // Check if variable exists
  $exists = $session->exists('my_key');
  $exists = isset($session->my_key);
  $exists = isset($session['my_key']);

  // Get variable value
  $my_value = $session->get('my_key', 'default');
  $my_value = $session->my_key;
  $my_value = $session['my_key'];

  // Set variable value
  $this->get('session')->set('my_key', 'my_value');
  $session->my_key = 'my_value';
  $session['my_key'] = 'my_value';

  // Merge value recursively
  $this->get('session')->merge('my_key', ['first' => 'value']);
  $session->merge('my_key', ['second' => ['a' => 'A']]);
  $letter_a = $session['my_key']['second']['a']; // "A"

  // Delete variable
  $session->delete('my_key');
  unset($session->my_key);
  unset($session['my_key']);

  // Destroy session
  $session::destroy();

  // Get session id
  $id = $this->session::id();

  return $res;
});
```
### Validation

```php
public function register(Request $request, Response $response)
{
    $data = $request->getParsedBody();

    $rules = [
        'name' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
        'password_confirmation' => 'required|same:password',
    ];

    $validation = $this->validator->make($data, $rules);

    if ($validation->fails()) {
        return $response->withJson(['errors' => $validation->errors()->all()], 400);
    }

    $user = new User();
    $user->email = $data['email'];
    $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
    $user->save();

    return $response->withJson(['status' => 'success']);
}
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
