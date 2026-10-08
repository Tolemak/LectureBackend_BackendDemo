# LectureBackend

REST API do wykładów i zapisów studentów. Symfony + MongoDB, logowanie JWT. Wykładowca tworzy wykład z limitem miejsc, student się zapisuje (raz na wykład, przed jego startem, póki są miejsca) i może sprawdzić swoją listę. Specyfikacja OpenAPI leży w `.misc/openapi/openapi.yml`.

Status: projekt zakończony, dalszy rozwój nie jest planowany.

[English version](README.md)

## Uruchomienie

```bash
make bootstrap # composer install w kontenerze
make up        # Docker: aplikacja + MongoDB, API na 127.0.0.1:10990
make help
```

Logowanie przez `POST /auth/login` `{"userId": "...", "password": "..."}`, potem nagłówek `Authorization: Bearer <token>`.

`config/services.yaml` definiuje wartości zastępcze dla niewystawionych zmiennych env; prawdziwe wdrożenia dostarczają zmienne środowiskowe.

## Testy

```bash
make tests     # phpunit z pokryciem, phpcs, phpstan
```

Testy są integracyjne i wymagają MongoDB (dostarcza je `docker compose`). Para kluczy JWT powstaje poleceniem `php bin/console lexik:jwt:generate-keypair --skip-if-exists`.

## CI

GitHub Actions uruchamia wspólny workflow PHP (composer audit, phpcs, PHPStan, PHPUnit z progiem pokrycia, usługa MongoDB) i sprawdza, czy obraz Docker się buduje.
