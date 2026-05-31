<?php

declare(strict_types=1);

use Chetkov\PHPCleanArchitecture\Infrastructure\Event\EventManager;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report\ComponentReportRenderingEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report\ReportBuildingEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report\ReportRenderingEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report\UnitOfCodeReportRenderedEventListener;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Analysis\AnalysisEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Analysis\ComponentAnalysisEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Analysis\FileAnalyzedEventListener;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\Ast\AstDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\SpaReport\ReportRenderingService;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;

return [
    // Директория, в которую будут складываться файлы отчета.
    // Значение можно переопределить переменной окружения PHPCA_REPORTS_DIR.
    'reports_dir' => (string) getenv('PHPCA_REPORTS_DIR') ?: __DIR__ . '/phpca-reports',

    // История архитектурных снимков для timeline-режима отчета.
    // Если включить history.enabled, phpca-build-reports будет сохранять компактный snapshot метрик и графов.
    // Директорию истории лучше держать вне reports_dir: reports_dir пересоздается при каждой генерации отчета.
    // phpca-check тоже может сохранять snapshot, если включить collect_on_check или запустить команду с --record-history.
    // 'history' => [
    //     'enabled' => true,
    //     'dir' => __DIR__ . '/var/phpca-history',
    //     'collect_on_check' => false,
    // ],

    // Область сканирования для debug-команды поиска файлов вне компонентов.
    'debug_scan_paths' => [
        __DIR__ . '/src',
        __DIR__ . '/bin',
    ],

    // Автоматически описывает Composer-пакеты из vendor как внешние компоненты.
    // Такие компоненты не анализируются изнутри, но зависимости вашего кода от них попадают в отчет и проверки.
    'vendor_based_components' => [
        'enabled' => true,
        'vendor_path' => __DIR__ . '/vendor',
        'excluded' => [
            // __DIR__ . '/vendor/vendor/package-to-ignore',
        ],
    ],

    // Общие для всех компонентов ограничения.
    // Если ключ отсутствует, check_acyclic_dependencies_principle и check_stable_dependencies_principle считаются true.
    'restrictions' => [
        // Проверять нарушения ADP: Acyclic Dependencies Principle.
        'check_acyclic_dependencies_principle' => true,

        // Проверять нарушения SDP: Stable Dependencies Principle.
        'check_stable_dependencies_principle' => true,

        // Максимально допустимое расстояние до главной диагонали.
        // Если ключ отсутствует или равен null, это ограничение не применяется.
        // 'max_allowable_distance' => 0.1,
    ],

    // Описание компонентов и их ограничений.
    'components' => [
        'model' => [
            // Анализировать содержимое компонента или только использовать его как внешний компонент,
            // с которым можно сопоставить зависимости других компонентов.
            // Если ключ отсутствует, значение считается true.
            'is_analyze_enabled' => true,
            'name' => 'model',
            'roots' => [
                [
                    // Корневой путь компонента. Можно указать директорию или отдельный файл.
                    // Анализатор читает PHP-файлы через AST, поэтому PSR-4 совпадение имени класса и пути не обязательно.
                    'path' => __DIR__ . '/src/Model',

                    // Namespace используется для fallback-имен executable scripts и файлов без объявленных символов,
                    // а также помогает сопоставлять зависимости, когда у найденного элемента нет известного пути.
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Model',

                    // Если root содержит старую часть кода, которую планируется постепенно переносить,
                    // пометьте его как legacy. Отчет покажет долю legacy/modern строк кода.
                    // Если ключ отсутствует, root считается modern.
                    // 'legacy' => true,
                ],
                // Если код одного компонента лежит в нескольких местах, добавьте несколько root-ов.
                //
                // [
                //     'path' => '/path/to/component/first',
                //     'namespace' => 'Component\First',
                //     'legacy' => true,
                // ],
            ],
            // Директории или файлы, которые будут пропущены при анализе этого компонента.
            'excluded' => [
                // '/path/to/First/Component/dir1',
                // '/path/to/First/Component/dir2',
            ],
            'restrictions' => [
                // Имеет приоритет над общей настройкой restrictions.max_allowable_distance.
                // 'max_allowable_distance' => 0.1,

                // Список РАЗРЕШЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Если ключ отсутствует, равен [] или null, список разрешенных зависимостей не ограничивается.
                // Не используйте одновременно с forbidden_dependencies.
                'allowed_dependencies' => ['model'],

                // Список ЗАПРЕЩЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Если ключ отсутствует, равен [] или null, список запрещенных зависимостей не ограничивается.
                // Не используйте одновременно с allowed_dependencies.
                // 'forbidden_dependencies' => ['ThirdComponent'],

                // Публичный API компонента для ДРУГИХ компонентов: FQCN, директории или файлы.
                // Если список отсутствует или пустой, все элементы компонента считаются публичными.
                // Если список не пустой, не перечисленные элементы считаются приватным API для внешних компонентов.
                // Внутри owning component такие элементы остаются доступными и не создают private API violation.
                // Не используйте одновременно с private_elements.
                // 'public_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
                //     __DIR__ . '/directory/with/public/elements',
                // ],

                // Приватный API компонента для ДРУГИХ компонентов: FQCN, директории или файлы.
                // Если список отсутствует или пустой, все элементы компонента считаются публичными.
                // Внутри owning component такие элементы остаются доступными и не создают private API violation.
                // Не используйте одновременно с public_elements.
                // 'private_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
                //     __DIR__ . '/directory/with/private/elements',
                // ],
            ],
            // Вложенный конфиг для внутренней архитектуры компонента: слоев, подкомпонентов или любого следующего уровня.
            // Формат sub такой же, как у обычного phpca-config.php. Файл можно запускать отдельно.
            // При запуске из родительского конфига reports_dir вложенного конфига игнорируется:
            // отчет будет создан в reports_dir корня + нормализованный путь по иерархии компонентов.
            //
            // 'sub' => [
            //     // Наследование всегда явное. Если inherit отсутствует, sub ничего не наследует.
            //     // components не наследуются никогда.
            //     'inherit' => ['factories', 'vendor_based_components', 'exclusions'],
            //     'components' => [
            //         'Domain' => [
            //             'name' => 'Domain',
            //             'roots' => [
            //                 [
            //                     'path' => __DIR__ . '/src/Model/Domain',
            //                     'namespace' => 'Chetkov\PHPCleanArchitecture\Model\Domain',
            //                 ],
            //             ],
            //         ],
            //     ],
            // ],
        ],
        'service' => [
            'name' => 'service',
            'roots' => [
                [
                    'path' => __DIR__ . '/src/Service',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Service',
                ],
            ],
            'restrictions' => [
                'allowed_dependencies' => ['service', 'model', 'nikic/php-parser'],
            ],
        ],
        'infrastructure' => [
            'name' => 'infrastructure',
            'roots' => [
                [
                    'path' => __DIR__ . '/src/Infrastructure',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Infrastructure',
                ],
            ],
            'restrictions' => [
                'allowed_dependencies' => ['infrastructure', 'service', 'model'],
            ],
        ],
        'entry-points' => [
            'name' => 'entry-points',
            'roots' => [
                [
                    'path' => __DIR__ . '/src',
                    'namespace' => 'Chetkov\PHPCleanArchitecture',
                ],
                [
                    'path' => __DIR__ . '/bin',
                    'namespace' => '',
                ],
            ],
            'excluded' => [
                __DIR__ . '/src/Model',
                __DIR__ . '/src/Service',
                __DIR__ . '/src/Infrastructure',
            ],
            'restrictions' => [
                'allowed_dependencies' => ['entry-points', 'service', 'model', 'infrastructure'],
            ],
        ],
    ],

    // Исключения.
    'exclusions' => [
        // Разрешенное текущее состояние для постепенного внедрения инструмента в существующий проект.
        // Файл генерируется командой:
        // vendor/bin/phpca-allow-current-state phpca-config.php
        //
        // Если enabled=true, но файл storage еще не существует, allowed state просто не применяется.
        // В suite-режиме команда проходит корневой конфиг и все вложенные sub-конфиги.
        // Если sub наследует exclusions, его состояние будет сохранено в отдельный файл рядом с корневым storage:
        // например phpca-allowed-state/catalog/domain.php.
        // Если sub задает свой exclusions.allowed_state.storage явно, используется явно заданный путь.
        'allowed_state' => [
            'enabled' => false,
            'storage' => __DIR__ . '/phpca-allowed-state.php',
        ],
    ],

    // Технические фабрики. В обычном проекте этот блок, как правило, не нужно менять.
    'factories' => [
        // Фабрика, собирающая DependenciesFinder.
        'dependencies_finder' => static function (): DependenciesFinderInterface {
            return new AstDependenciesFinder();
        },
        // Фабрика, собирающая сервис рендеринга отчетов.
        'report_rendering_service' => static function (EventManagerInterface $eventManager): ReportRenderingServiceInterface {
            return new ReportRenderingService($eventManager);
        },
        // Фабрика, собирающая и настраивающая EventManager.
        'event_manager' => static function (): EventManagerInterface {
            return new EventManager([
                new ReportBuildingEventListener(),
                new AnalysisEventListener(),
                new ComponentAnalysisEventListener(),
                new FileAnalyzedEventListener(),
                new ReportRenderingEventListener(),
                new ComponentReportRenderingEventListener(),
                new UnitOfCodeReportRenderedEventListener(),
            ]);
        }
    ],
];
