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

Текущая major-версия поддерживает PHP 8.3, 8.4 и 8.5.

## Конфигурация

Скопируйте образец конфига в корень проекта:

```shell script
cp vendor/v.chetkov/php-clean-architecture/example.phpca-config.php phpca-config.php
```

Все детали конфигурации подробно описаны в образце конфига:
https://github.com/Chetkov/php-clean-architecture/blob/master/example.phpca-config.php

### Публичный и приватный API компонента

`public_elements` и `private_elements` описывают API компонента для других компонентов, а не запрет на использование
элемента внутри его собственного компонента.

Например, `ComponentA\Service` может зависеть от `ComponentA\Internal\Model`. Это внутренняя зависимость компонента, она
может отображаться в dependency graph как internal, но не считается private API violation.

А вот `ComponentB\Service` не должен зависеть от `ComponentA\Internal\Model`, если этот элемент не входит в публичный API
`ComponentA` или явно указан в `private_elements`.

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

## Использование

1. Формирование отчета для анализа.

```shell script
vendor/bin/phpca-build-reports phpca-config.php
```

Команда создает статический HTML-отчет с графом компонентов, поиском по юнитам, фильтрами зависимостей, списком нарушений
и архитектурными метриками. Отчет можно открыть локально в браузере без запуска сервера.

Путь к конфигу можно не передавать, если используется стандартный `phpca-config.php` в текущей директории.

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

Это дает возможность подключать php-clean-architecture не только к новым проектам, но и к уже существующим проектам,
где архитектурные проблемы нужно устранять постепенно.

4. Отчет/Check по списку файлов

Если нужно проверить не весь проект, а только его часть, например список измененных файлов, можно передать ограничение через
переменную окружения `PHPCA_ALLOWED_PATHS`.

Пример:

```shell
PHPCA_ALLOWED_PATHS="$(git diff master --name-only)" PHPCA_REPORTS_DIR="phpca-report" vendor/bin/phpca-build-reports phpca-config.php
```
