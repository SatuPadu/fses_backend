# News Aggregator Test

This project is a Laravel-based news aggregator application that provides a REST API for managing articles, sources, and user preferences.

## Table of Contents
- [Clone the Repository](#clone-the-repository)
- [Setup with Docker](#setup-with-docker)
- [Build and Run Docker Containers](#build-and-run-docker-containers)
- [Access the Container](#access-the-container)
- [Run Migrations and Seeders](#run-migrations-and-seeders)
- [Run Scheduler and Queue Worker](#run-scheduler-and-queue-worker)
    - [Run Scheduler](#run-scheduler)
    - [Run Queue Worker](#run-queue-worker)
- [Run Tests](#run-tests)
- [API Documentation](#api-documentation)

## Clone the Repository

Clone the repository from GitHub:

```sh
git clone https://github.com/Khairulbashar010/news-aggregator-test.git
cd news-aggregator-test
```

## Setup with Docker

## Build and Run Docker Containers

Setup your .env file and then build the Docker image and start the container using the following command:

```sh
docker compose up
```

This will build the Docker image and start a container exposing the application on port 8080.

## Access the Container

To interact with the application inside the Docker container, use:

```sh
docker exec -it news-aggregator-container bash
```

This will give you access to the container’s shell.

## Run Migrations and Seeders

Inside the container, run the following commands to migrate and seed the database:

```sh
php artisan migrate
php artisan db:seed
```

## Run Scheduler and Queue Worker

### Run Scheduler

The Laravel scheduler can be executed using the following command inside the container:

```sh
php artisan schedule:run
```

To run it continuously, consider setting up a cron job.

### Run Queue Worker

Run the queue worker to process background jobs:

```sh
php artisan queue:work
```

## Run Tests

To run the tests, use:

```sh
php artisan test
```

This will execute all the test cases defined in the application.

## API Documentation

The API documentation is generated using Swagger. 

Being inside the container run the following command:

```bash
php artisan l5-swagger:generate
```

Now you can access it via the following URL once the application is running:

```sh
http://localhost:8080/api/documentation
```

Notes
	•	Ensure the .env file is correctly configured for database and other settings before running the application.
	•	Modify the exposed ports in the docker run command if 8080 is already in use on your system.

Feel free to reach out if you encounter any issues while setting up the project. 😊