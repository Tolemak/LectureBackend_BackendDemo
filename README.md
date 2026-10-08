# LectureBackend

[![CI](https://github.com/Tolemak/LectureBackend_BackendDemo/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Tolemak/LectureBackend_BackendDemo/actions/workflows/ci.yml)

REST API for lectures and student enrollments. Symfony + MongoDB, JWT auth. Lecturers create lectures with a seat limit, students enroll (once per lecture, before it starts, while seats last) and can check their own list. The OpenAPI spec is in `.misc/openapi/openapi.yml`.

Status: finished, no further development planned.

[Polska wersja](README.pl.md)

## Run

```bash
make bootstrap # composer install in the container
make up        # Docker: app + MongoDB, API on 127.0.0.1:10990
make help
```

Log in with `POST /auth/login` `{"userId": "...", "password": "..."}`, then send `Authorization: Bearer <token>`.

`config/services.yaml` defines fallback values for env vars that are not set; real deployments provide actual environment variables.

## Tests

```bash
make tests     # phpunit with coverage, phpcs, phpstan
```

The tests are integration tests and need MongoDB (provided by `docker compose`). The JWT key pair is generated with `php bin/console lexik:jwt:generate-keypair --skip-if-exists`.

## CI

GitHub Actions runs the shared PHP workflow (composer audit, phpcs, PHPStan, PHPUnit with a coverage gate, MongoDB service) and checks that the Docker image builds.
