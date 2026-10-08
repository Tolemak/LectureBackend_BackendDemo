# LectureBackend

[![CI](https://github.com/Tolemak/LectureBackend_BackendDemo/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Tolemak/LectureBackend_BackendDemo/actions/workflows/ci.yml)

REST API for lectures and student enrollments. Symfony + MongoDB, JWT auth. Lecturers create lectures with a seat limit, students enroll (once per lecture, before it starts, while seats last) and can check their own list. The OpenAPI spec is in `.misc/openapi/openapi.yml`.

[Polska wersja](README.pl.md)

```bash
make bootstrap # composer install in the container
make up        # Docker: app + MongoDB, API on 127.0.0.1:10990
make tests     # phpunit with coverage, phpcs, phpstan
make help
```

Log in with `POST /auth/login` `{"userId": "...", "password": "..."}`, then send `Authorization: Bearer <token>`.

`config/services.yaml` defines fallback values for env vars that are not set; no `.env` is committed and real deployments provide actual environment variables.
