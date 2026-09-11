# LectureBackend

*Read this in other languages: [Polski](README.pl.md)*

A REST API backend for managing lectures and student enrollments, written in PHP (Symfony) with a MongoDB database.

The app uses PHP 8.3 (enums, readonly classes) and a layered architecture separating domain logic, the data access layer (`src/Persistence`), and the presentation layer (API controllers).

## Authentication

Every `/lectures` endpoint requires a JWT. `POST /auth/login` takes `{"userId": "...", "password": "..."}` and returns `{"token": "..."}`; send it as `Authorization: Bearer <token>`. The caller's identity and role come from the token, never from the request body, and creating a lecture additionally requires the `lecturer` role.

## Features

* A lecturer can create new lectures with a student limit. The lecture id is always server-generated (`{"status": "created", "id": "..."}`) — an existing resource can't be overwritten by supplying your own id.
* A lecturer can remove students from their own lectures; a student can remove their own enrollment. Any other removal request is rejected (403).
* A student can enroll in lectures (in as many different lectures as they like, but only once per lecture, only before it starts, and only if seats are available).
* A student can fetch the list of lectures they're enrolled in (`GET /lectures/mine`), scoped to whoever the token identifies.

## Tests and API docs

* Tests live in `tests/Lecture/LectureTest`, including input validation and enrollment-removal authorization scenarios.
* The API is described by an OpenAPI spec in `.misc/openapi/openapi.yml`.

## Running tests

```bash
make tests
```

Spins up the environment (Docker: app + MongoDB), runs `phpunit` with code coverage (`--coverage-text`), and checks code style (`phpcs`, PSR-12 — configured in `phpcs.xml.dist`).
