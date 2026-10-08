# LectureBackend

[![CI](https://github.com/Tolemak/LectureBackend_BackendDemo/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Tolemak/LectureBackend_BackendDemo/actions/workflows/ci.yml)

REST API do wykładów i zapisów studentów. Symfony + MongoDB, logowanie JWT. Wykładowca tworzy wykład z limitem miejsc, student się zapisuje (raz na wykład, przed jego startem, póki są miejsca) i może sprawdzić swoją listę. Specyfikacja OpenAPI leży w `.misc/openapi/openapi.yml`.

[English version](README.md)

```bash
make bootstrap # composer install w kontenerze
make up        # Docker: aplikacja + MongoDB, API na 127.0.0.1:10990
make tests     # phpunit z pokryciem, phpcs, phpstan
make help
```

Logowanie przez `POST /auth/login` `{"userId": "...", "password": "..."}`, potem nagłówek `Authorization: Bearer <token>`.

`config/services.yaml` definiuje wartości zastępcze dla niewystawionych zmiennych env; nie ma commitowanego `.env`, a prawdziwe wdrożenia dostarczają zmienne środowiskowe.
