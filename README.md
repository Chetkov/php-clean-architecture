To continue in English go to [README.en](README-EN.md)
# PHP Clean Architecture

Инструмент для автоматизации контроля над качеством архитектуры приложений написанных на PHP, а также упрощения анализа 
и визуализации некоторых метрик.

Идея его создания была навеяна книгой "Чистая Архитектура" (Роберта Мартина). 
Если еще не читал, можешь ознакомиться с её ключевыми идеями, на которых базируется инструмент https://habr.com/ru/post/504590/

## Установка
```shell script
composer require v.chetkov/php-clean-architecture --dev
```

## Конфигурация
Далее копируем образец конфига в корень проекта
```shell script
cp vendor/v.chetkov/php-clean-architecture/example.phpca-config.php phpca-config.php
```

Все детали конфигурации подробно описаны в образце конфига https://github.com/Chetkov/php-clean-architecture/blob/master/example.phpca-config.php,
а также в статьях https://habr.com/ru/post/504590/ и https://habr.com/ru/post/686236/

## Использование

1. Формирование отчета для анализа.
```shell script
vendor/bin/phpca-build-reports {?path/to/phpca-config.php}
```
Отчет визуализирует текущее состояние проекта, наглядно отображает взаимосвязи между компонентами, их силу, удалённость 
компонентов от главной последовательности, а также подсвечивает обнаруженные на основе конфига нежелательные зависимости 
и прочие архитектурные проблемы.
![image](https://user-images.githubusercontent.com/12594577/134708940-f53dc72e-8664-4e57-a3a7-4f6bb4ec965c.png)
![image](https://user-images.githubusercontent.com/12594577/134709361-fbe654bd-70f4-460c-a107-fb3956f064b0.png)

2. Check для CI.
```shell script
vendor/bin/phpca-check {?path/to/phpca-config.php}
```
В случае нарушения кодом ограничений, заданных конфигом, информирует об обнаруженных проблемах и завершает выполнение с ошибкой. 
Рекомендуется добавить запуск этой команды в CI (это гарантирует соответствие кода, попавшего в сборку, настроенным ограничениям)

3. Разрешенное состояние.
```shell script
vendor/bin/phpca-allow-current-state {?path/to/phpca-config.php}
```
Команда сохранит текущее состояние проекта, взаимосвязи между существующими классами, в отдельный файл. При последующих 
запусках phpca-check, проблемы относящиеся к сохраненному состоянию будут проигнорированы.

Это дает возможность легко подключать php-clean-architecture не только к новым проектам, но и к уже существующим, и уже 
имеющим проблемы, устранение которых требует времени.

4. Отчёт/Check по списку файлов

Если вы хотите осуществить проверку на наличие проблем или построить граф зависимостей и провести анализ не по всему проекту,
а по некоторой его части (к примеру по списку изменённых файлов), вы можете установить значение переменной окружения *PHPCA_ALLOWED_PATHS*
Пример использования:
```shell
export PHPCA_ALLOWED_PATHS=`git diff master --name-only` PHPCA_REPORTS_DIR='phpca-report'; vendor/bin/phpca-build-reports {?path/to/phpca-config.php}
```
