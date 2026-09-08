# LectureBackend

Celem tego zadania było napisanie aplikacji umożliwiającej uczniom zapisywanie się na wykłady.

Jest to nowoczesna aplikacja backendowa napisana w PHP z wykorzystaniem frameworka Symfony. System udostępnia REST API do zarządzania wykładami oraz zapisami studentów. Całość została zaprojektowana z naciskiem na czytelność kodu, testowalność oraz zgodność z dobrymi praktykami architektury aplikacji webowych.

Aplikacja korzysta z bazy danych MongoDB (klient w `src/Persistence`) do przechowywania danych o wykładach, użytkownikach i zapisach. Wykorzystano PHP 8.2 oraz nowoczesne cechy języka, takie jak typy wyliczeniowe (enum), klasy readonly i kolekcje. Całość oparta jest o architekturę warstwową, z wyraźnym rozdzieleniem logiki domenowej, warstwy dostępu do danych oraz warstwy prezentacji (kontrolery API).

## Zrealizowane funkcjonalności (User stories)
* Wykładowca może zakładać nowe wykłady z limitem miejsc. Identyfikator wykładu jest zawsze generowany po stronie serwera i zwracany w odpowiedzi (`{"status": "created", "id": "..."}`) — nie da się nadpisać istniejącego zasobu, podając własne id.
* Wykładowca może usuwać studentów ze swoich wykładów; student może usunąć własny zapis. Każde inne żądanie usunięcia jest odrzucane (403).
* Uczeń może zapisywać się na wykłady (na wiele różnych wykładów, ale nie więcej niż raz na ten sam, tylko przed rozpoczęciem i jeśli są wolne miejsca).
* Uczeń może pobrać listę wykładów, na które jest zapisany.

## Definition of done
* Kod jest pokryty testami. Przypadki testowe znajdują się w `tests/Lecture/LectureTest`, w tym scenariusze walidacji danych wejściowych i autoryzacji usuwania zapisów.
* API zostało opisane w specyfikacji OpenAPI (`.misc/openapi/openapi.yml`).

## Uruchamianie testów
```bash
make tests
```
Uruchamia środowisko (Docker: aplikacja + MongoDB), wykonuje `phpunit` z pokryciem kodu (`--coverage-text`) oraz sprawdza styl kodu (`phpcs`, PSR-12 — konfiguracja w `phpcs.xml.dist`). Uruchom to polecenie lokalnie, aby zobaczyć aktualny wynik pokrycia — celowo nie trzymamy tu zamrożonej liczby z przeszłości, żeby README nie rozjeżdżało się z rzeczywistym stanem kodu.

## Uwagi
- Obraz bazy danych w `docker-compose.yml` jest teraz oficjalnym obrazem `mongo:5.0` (wcześniej przypięty tag `bitnami/mongodb:5.0.23` przestał być dostępny na Docker Hub).
- Do projektu doinstalowano kilka dodatkowych narzędzi i pakietów Symfony, aby ułatwić testowanie i rozwój, m.in. `symfony/routing`, `symfony/console`.
