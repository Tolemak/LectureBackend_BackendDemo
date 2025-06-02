# GWO Apps Recruitment Task

Celem tego zadania jest napisanie aplikacji umożliwiającej uczniom zapisywanie się na wykłady.

## User stories
* Jako wykładowca chciałbym móc zakładać nowe wykłady. Każdy wykład ma limit uczniów, którzy mogą się na niego zapisać.
* Jako wykładowca chciałbym móc usuwać uczniów ze swoich wykładów.
* Jako uczeń chciałbym móc zapisywać się na wykłady. Mogę zapisać się na wiele wykładów, ale nie więcej niż raz na ten sam oraz pod warunkiem, że jeszcze się nie rozpoczął i jest wolne miejsce.
* Jako uczeń chciałbym móc pobrać listę wykładów, na które jestem zapisany.

Do tego zadania wykorzystaj istniejące modele oraz bazę danych MongoDB (klienta do bazy znajdziesz w 
`src/Persistence`). Do dyspozycji masz PHP 8.2, postaraj się wykorzystać nowe ficzery tego języka. Pamiętaj również, aby zwrócić uwagę na architekturę aplikacji oraz czystość kodu.

## Definition of done
* kod jest pokryty testami na poziomie min. 80%. Przypadki testowe zostały wymienione w `tests/Lecture/LectureTest`, lecz śmiało możesz napisać więcej testów.
* API jest opisane w specyfikacji OpenAPI, której szablon znajdziesz w `.misc/openapi`.

## Zadanie dodatkowe

Do zapisów na wykłady wykorzystaj mechanizm kolejki. Możesz użyć do tego paczkę `symfony/messenger` (a do testów `zenstruck/messenger-test`).

## Zadanie dodatkowe 2

W dostarczonym tutaj kodzie znajduje się błąd (nie wpływa on na działanie aplikacji, więc w testach nie wyjdzie). Znajdź go i popraw ;)

## Uruchamianie testów

Na początku zbuduj aplikację i zainstaluj zależności:
```markdown
make bootstrap
```
Następnie testy możesz uruchomić poniższym poleceniem:
```markdown
make tests
```
