# Apps Recruitment Task

Celem tego zadania było napisanie aplikacji umożliwiającej uczniom zapisywanie się na wykłady.

Jest to nowoczesna aplikacja backendowa napisana w PHP z wykorzystaniem frameworka Symfony. System udostępnia REST API do zarządzania wykładami oraz zapisami studentów. Całość została zaprojektowana z naciskiem na czytelność kodu, testowalność oraz zgodność z dobrymi praktykami architektury aplikacji webowych.

Aplikacja korzysta z bazy danych MongoDB (klient w `src/Persistence`) do przechowywania danych o wykładach, użytkownikach i zapisach. Wykorzystano PHP 8.2 oraz nowoczesne cechy języka, takie jak typy wyliczeniowe (enum), klasy readonly i kolekcje. Całość oparta jest o architekturę warstwową, z wyraźnym rozdzieleniem logiki domenowej, warstwy dostępu do danych oraz warstwy prezentacji (kontrolery API).

## Zrealizowane funkcjonalności (User stories)
* Wykładowca może zakładać nowe wykłady z limitem miejsc.
* Wykładowca może usuwać uczniów ze swoich wykładów.
* Uczeń może zapisywać się na wykłady (na wiele różnych wykładów, ale nie więcej niż raz na ten sam, tylko przed rozpoczęciem i jeśli są wolne miejsca).
* Uczeń może pobrać listę wykładów, na które jest zapisany.

## Definition of done
* Kod jest pokryty testami na poziomie powyżej 80%. Przypadki testowe znajdują się w `tests/Lecture/LectureTest`.
* API zostało opisane w specyfikacji OpenAPI (`.misc/openapi/openapi.yml`).

## Pokrycie kodu (stan na 2025-06-03)

```
 Summary:
  Classes: 88.89% (8/9)
  Methods: 95.12% (39/41)
  Lines:   98.90% (180/182)
```

Najważniejsze klasy domenowe i serwisy mają 100% pokrycia metod i linii, w tym:
- `LectureController`
- `Lecture`
- `LectureCollection`
- `LectureEnrollment`
- `DatabaseClient`
- `LectureService`
- `User`
- `StringId`

## Uruchamianie testów

Na początku zbuduj aplikację i zainstaluj zależności:
```
make bootstrap
```
Następnie testy możesz uruchomić poniższym poleceniem:
```
make tests
```

## Uwagi

Podczas pracy nad projektem napotkałem kilka problemów środowiskowych, które mogą utrudniać uruchomienie aplikacji:

- **Plik `docker-compose.yml`** – obecnie oficjalnie wspierane jest polecenie `docker compose` (bez myślnika), a nie `docker-compose`. Warto zaktualizować dokumentację i polecenia.
- **`docker compose version`** – niektóre wersje Dockera mogą zwracać błędy przy budowaniu, szczególnie jeśli używasz starszego polecenia lub nieaktualnej wersji Compose.
- **MongoDB** – w pliku do budowania kontenera nie została podana konkretna wersja obrazu MongoDB, co może skutkować pobraniem nowej wersji (np. 2.x)
- **Symfony** – do projektu zostało doinstalowanych kilka dodatkowych narzędzi i paczek Symfony, aby ułatwić testowanie i rozwój, m.in.:
  - `symfony/routing`
  - `symfony/console`

Warto zwrócić uwagę na powyższe kwestie podczas dalszego rozwoju lub uruchamiania projektu na nowym środowisku.
