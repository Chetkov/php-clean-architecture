To continue in English go to [README-EN.md](README-EN.md)

# PHP Clean Architecture

Инструмент для автоматизации контроля над качеством архитектуры приложений, написанных на PHP, а также для анализа
и визуализации архитектурных метрик.

Идея его создания была навеяна книгой Роберта Мартина "Чистая Архитектура".
Если еще не читал, можешь ознакомиться с ключевыми идеями, на которых базируется инструмент:
https://habr.com/ru/post/504590/

## Быстрый старт

```shell script
composer require v.chetkov/php-clean-architecture --dev
cp vendor/v.chetkov/php-clean-architecture/example.phpca-config.php phpca-config.php
vendor/bin/phpca-check phpca-config.php
vendor/bin/phpca-build-reports phpca-config.php
```

## Совместимость

Пакет можно подключать к проектам на PHP 7.4 и выше.

Сам анализатор умеет читать код с синтаксисом PHP 8.5 через AST. Это значит, что проверяемый проект может использовать
современный PHP-синтаксис, а сам инструмент при этом остается совместимым с более старым runtime, начиная с PHP 7.4.

## Конфигурация

Скопируйте образец конфига в корень проекта:

```shell script
cp vendor/v.chetkov/php-clean-architecture/example.phpca-config.php phpca-config.php
```

Все детали конфигурации подробно описаны в образце конфига:
https://github.com/Chetkov/php-clean-architecture/blob/master/example.phpca-config.php

### Основные секции конфига

- `reports_dir` - директория, куда `phpca-build-reports` сохранит HTML-отчет. В образцовом конфиге значение можно
  переопределить через `PHPCA_REPORTS_DIR`.
- `components` - список архитектурных компонентов проекта.
- `roots` - директории или файлы компонента: `path` задает путь, `namespace` задает соответствующий PHP namespace.
- `excluded` - пути внутри компонента, которые нужно исключить из анализа этого компонента.
- `restrictions.allowed_dependencies` - компоненты, от которых текущему компоненту разрешено зависеть.
- `restrictions.forbidden_dependencies` - компоненты, от которых текущему компоненту явно запрещено зависеть. Не стоит
  использовать одновременно с `allowed_dependencies`.
- `restrictions.public_elements` - публичный API компонента для других компонентов.
- `restrictions.private_elements` - приватный API компонента для других компонентов.
- `restrictions.max_allowable_distance` - максимально допустимое расстояние компонента от Main Sequence.
- `restrictions.check_acyclic_dependencies_principle` - проверка Acyclic Dependencies Principle.
- `restrictions.check_stable_dependencies_principle` - проверка Stable Dependencies Principle.
- `vendor_based_components` - автоматическое создание компонентов для Composer-зависимостей из `vendor`.
- `exclusions.allowed_state` - сохраненное разрешенное состояние для постепенного внедрения правил.
- `factories` - технические фабрики анализатора, рендера отчета и event manager. В обычном проекте их чаще всего не нужно
  менять.

### Публичный и приватный API компонента

`public_elements` и `private_elements` описывают API компонента для других компонентов, а не запрет на использование
элемента внутри его собственного компонента.

Например, `ComponentA\Service` может зависеть от `ComponentA\Internal\Model`. Это внутренняя зависимость компонента, она
может отображаться в dependency graph как internal, но не считается private API violation.

А вот `ComponentB\Service` не должен зависеть от `ComponentA\Internal\Model`, если этот элемент не входит в публичный API
`ComponentA` или явно указан в `private_elements`.

### Вложенные отчеты

Для больших систем один общий граф часто становится слишком шумным. В таких случаях архитектуру можно описывать
рекурсивно: верхний уровень показывает крупные компоненты системы, а внутри каждого компонента можно описать слои,
подкомпоненты или следующий уровень декомпозиции.

Компонент верхнего уровня может содержать `sub`-конфиг:

```php
'components' => [
    'AgentWorkspace' => [
        'roots' => [...],
        'sub' => require __DIR__ . '/phpca-configs/layers/AgentWorkspace.php',
    ],
],
```

Формат `sub` такой же, как у обычного `phpca-config.php`. Такой файл можно запускать отдельно, а можно подключить из
родительского конфига через `require`.

При запуске из родительского конфига:

- `phpca-check` последовательно проверяет корневой конфиг и все вложенные `sub`-конфиги;
- `phpca-build-reports` создает один SPA-отчет, в котором можно переходить между уровнями анализа;
- локальный `reports_dir` вложенного конфига игнорируется;
- путь вложенного отчета строится от корневого `reports_dir` по иерархии компонентов.

Наследование задается явно:

```php
'inherit' => ['factories', 'vendor_based_components', 'exclusions'],
```

Если `inherit` не указан, вложенный конфиг ничего не наследует. `components` не наследуются никогда.

Такой подход позволяет держать несколько удобных уровней архитектурного контроля:

- система целиком: зависимости между bounded contexts или крупными модулями;
- компонент: зависимости между слоями `Domain`, `Application`, `Infrastructure`, `Presentation`;
- подкомпонент: более тонкая внутренняя декомпозиция, если она нужна проекту.

Также полезно прочитать статьи:

- https://habr.com/ru/post/504590/
- https://habr.com/ru/post/686236/

## Обнаружение исходников

Анализатор читает PHP-файлы через AST и определяет объявленные `class`, `interface`, `trait` и `enum` из содержимого
файла. Поэтому имя элемента больше не обязано совпадать с путем по PSR-4: компонент может указывать на legacy-директорию
или отдельный файл, а найденный символ будет привязан к компоненту по root path из конфига.

Если в файле нет объявленных символов, например это executable script, используется fallback по `namespace` root-а и
относительному пути. При включенном `vendor_based_components` учитываются Composer `psr-4`, `psr-0`, `classmap`, `files`,
`autoload-dev` и `exclude-from-classmap`.

Синтаксис PHP 8.5 поддерживается на уровне AST-парсинга: pipe operator, `clone()` с изменением свойств, `#[NoDiscard]`,
closures/first-class callables и casts в constant expressions, attributes on constants и final promoted properties.

## CLI-команды

Все команды принимают путь к конфигу первым аргументом. Если путь не передан, используется `phpca-config.php` из текущего
проекта.

```shell
vendor/bin/phpca-check phpca-config.php
vendor/bin/phpca-build-reports phpca-config.php
vendor/bin/phpca-allow-current-state phpca-config.php
```

`PHPCA_ALLOWED_PATHS` ограничивает анализ списком файлов. Это удобно для CI по измененным файлам. Значение передается как
список путей, разделенных переводом строки.

`PHPCA_REPORTS_DIR` можно использовать в образцовом конфиге для переопределения `reports_dir` без изменения файла
конфигурации.

## Использование

1. Формирование отчета для анализа.

```shell script
vendor/bin/phpca-build-reports phpca-config.php
```

Команда создает статический HTML-отчет. Отчет можно открыть локально в браузере без запуска сервера.

Путь к конфигу можно не передавать, если используется стандартный `phpca-config.php` в текущей директории.

### Что есть в отчете

Отчет рассчитан не только на маленькие проекты, но и на большие кодовые базы, где важно быстро перейти от общей картины к
конкретной зависимости.

Основные возможности:

- обзор системы с количеством компонентов, юнитов, зависимостей и активных проблем;
- матрица Robert C. Martin A/I с зонами боли и бесполезности;
- Distance from Main Sequence и метрики компонента с цветовой оценкой качества;
- общий граф компонентов и граф выбранного компонента;
- внешние компоненты и библиотеки на графе как отдельные external nodes;
- фильтр компонентов графа: один выбранный компонент показывает его окружение, несколько выбранных компонентов показывают
  только связи между ними;
- drag, zoom, reset viewport, fullscreen и разные варианты компоновки графа;
- глобальный поиск по компонентам, юнитам, путям и зависимостям;
- вкладки нарушений, зависимостей и юнитов;
- Dependency Explorer с группировкой `компонент -> дерево директорий -> файл -> конкретные unit-зависимости`;
- цветовая индикация `allowed`, `internal`, `allowed state`, `private API` и `blocked`;
- копирование путей файлов и полных имен юнитов из dependency details;
- URL-навигация: выбранный отчет, компонент, вкладка, юнит и поиск восстанавливаются после обновления страницы;
- локализация RU / EN / 中文.

Пример отчета на большом проекте:

![Обзор suite-отчета](docs/images/report-suite-overview.png)

![Карта зависимостей с внешними компонентами](docs/images/report-dependency-graph.png)

![Детали зависимостей компонента](docs/images/report-dependency-details.png)

2. Check для CI.

```shell script
vendor/bin/phpca-check phpca-config.php
```

Если код нарушает ограничения, заданные конфигом, команда выводит найденные проблемы и завершается с ошибкой.
Рекомендуется добавить запуск `phpca-check` в CI, чтобы код, попавший в сборку, соответствовал архитектурным правилам проекта.

3. Разрешенное состояние.

```shell script
vendor/bin/phpca-allow-current-state phpca-config.php
```

Команда сохраняет текущее состояние проекта и взаимосвязи между существующими классами в отдельный файл. При последующих
запусках `phpca-check` проблемы, относящиеся к сохраненному состоянию, будут проигнорированы.

Чтобы `phpca-check` учитывал сохраненное состояние, в конфиге должны быть включены `exclusions.allowed_state.enabled` и
указан путь к `exclusions.allowed_state.storage`.

Для suite-конфига команда проходит корневой конфиг и все вложенные `sub`-конфиги. Если вложенный конфиг наследует
`exclusions`, его состояние сохраняется в отдельный файл, построенный от корневого storage по иерархии компонентов,
например `phpca-allowed-state/catalog/domain.php`. Если во вложенном конфиге явно указан свой
`exclusions.allowed_state.storage`, используется этот путь.

Это дает возможность подключать php-clean-architecture не только к новым проектам, но и к уже существующим проектам,
где архитектурные проблемы нужно устранять постепенно.

4. Отчет/Check по списку файлов

Если нужно проверить не весь проект, а только его часть, например список измененных файлов, можно передать ограничение через
переменную окружения `PHPCA_ALLOWED_PATHS`.

Пример:

```shell
PHPCA_ALLOWED_PATHS="$(git diff master --name-only)" PHPCA_REPORTS_DIR="phpca-report" vendor/bin/phpca-build-reports phpca-config.php
```
