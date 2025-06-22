# FSES Backend

This project is a Laravel-based backend system that provides a REST API for the FSES system. A moduler architecture (CSR) has been maintained in this app.


## Table of Contents
- [FSES Backend](#fses-backend)
  - [Table of Contents](#table-of-contents)
  - [Clone the Repository](#clone-the-repository)
  - [Setup with Docker](#setup-with-docker)
  - [Build and Run Docker Containers](#build-and-run-docker-containers)
  - [Access the Container](#access-the-container)
  - [Run Migrations and Seeders](#run-migrations-and-seeders)
  - [Start the queue](#start-the-queue)

## Clone the Repository

Clone the repository from GitHub:

```sh
git clone https://github.com/SatuPadu/fses_backend.git
cd fses_backend
```

## Setup with Docker

## Build and Run Docker Containers

Setup your .env file and then build the Docker image and start the container using the following command:

```sh
docker compose up --build
```

This will build the Docker image and start a container exposing the application on port 8080.

## Access the Container

To interact with the application inside the Docker container, use:

```sh
docker exec -it fses_backend bash
```

This will give you access to the container’s shell.

## Run Migrations and Seeders

Inside the container, run the following commands to install composer, migrate and seed the database:

```sh
composer install
php artisan migrate --seed
```
## Start the queue

Inside the container, run the following commands to start the queue service. It will allow to send emails and import files:

```sh
php artisan queue:work
```

<!-- ## Run Tests

To run the tests, use:

```sh
php artisan test
```

This will execute all the test cases defined in the application. -->

<!-- ## API Documentation

The API documentation is generated using Swagger. 

Being inside the container run the following command:

```bash
php artisan l5-swagger:generate
```

Now you can access it via the following URL once the application is running:

```sh
http://localhost:8080/api/documentation
``` -->

Notes
	•	Ensure the .env file is correctly configured for database and other settings before running the application.
	•	Modify the exposed ports in the docker run command if 8080 is already in use on your system.

Feel free to reach out if you encounter any issues while setting up the project. 😊