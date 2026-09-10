# LectureBackend

Backend REST API do zarządzania wykładami i zapisami studentów, napisany w PHP (Symfony) z bazą MongoDB.

Aplikacja korzysta z PHP 8.3 (enum, klasy readonly) oraz architektury warstwowej z rozdzieleniem logiki domenowej, warstwy dostępu do danych (`src/Persistence`) i warstwy prezentacji (kontrolery API).

## Uwierzytelnianie

Każdy endpoint `/lectures` wymaga tokenu JWT. `POST /auth/login` przyjmuje `{"userId": "...", "password": "..."}` i zwraca `{"token": "..."}`; token wysyłasz w nagłówku `Authorization: Bearer <token>`. Tożsamość i rola pochodzą z tokenu, nigdy z ciała żądania, a założenie wykładu wymaga dodatkowo roli `lecturer`.

## Funkcjonalności

* Wykładowca może zakładać nowe wykłady z limitem miejsc. Identyfikator wykładu jest zawsze generowany po stronie serwera (`{"status": "created", "id": "..."}`) — nie da się nadpisać istniejącego zasobu, podając własne id.
* Wykładowca może usuwać studentów ze swoich wykładów; student może usunąć własny zapis. Każde inne żądanie usunięcia jest odrzucane (403).
* Uczeń może zapisywać się na wykłady (na wiele różnych wykładów, ale nie więcej niż raz na ten sam, tylko przed rozpoczęciem i jeśli są wolne miejsca).
* Uczeń może pobrać listę wykładów, na które jest zapisany (`GET /lectures/mine`), zawężoną do użytkownika z tokenu.

## Testy i dokumentacja API

* Testy znajdują się w `tests/Lecture/LectureTest`, w tym scenariusze walidacji danych wejściowych i autoryzacji usuwania zapisów.
* API opisane jest specyfikacją OpenAPI w `.misc/openapi/openapi.yml`.

## Uruchamianie testów

```bash
make tests
```

Uruchamia środowisko (Docker: aplikacja + MongoDB), wykonuje `phpunit` z pokryciem kodu (`--coverage-text`) oraz sprawdza styl kodu (`phpcs`, PSR-12 — konfiguracja w `phpcs.xml.dist`).
