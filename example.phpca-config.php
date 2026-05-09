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

    // Автоматически описывает Composer-пакеты из vendor как внешние компоненты.
    // Такие компоненты не анализируются изнутри, но зависимости вашего кода от них попадают в отчет и проверки.
    // Для первого запуска безопаснее оставить false и включить после настройки своих компонентов.
    'vendor_based_components' => [
        'enabled' => false,
        'vendor_path' => __DIR__ . '/vendor',
        'excluded' => [
            // __DIR__ . '/vendor/vendor/package-to-ignore',
        ],
    ],

    // Общие для всех компонентов ограничения.
    // Если ключ отсутствует, check_acyclic_dependencies_principle и check_stable_dependencies_principle считаются true.
    'restrictions' => [
        // Проверять нарушения ADP: Acyclic Dependencies Principle.
        // 'check_acyclic_dependencies_principle' => true,

        // Проверять нарушения SDP: Stable Dependencies Principle.
        // 'check_stable_dependencies_principle' => true,

        // Максимально допустимое расстояние до главной диагонали.
        // Если ключ отсутствует или равен null, это ограничение не применяется.
        // 'max_allowable_distance' => 0.1,
    ],

    // Описание компонентов и их ограничений.
    'components' => [
        [
            // Анализировать содержимое компонента или только использовать его как внешний компонент,
            // с которым можно сопоставить зависимости других компонентов.
            // Если ключ отсутствует, значение считается true.
            'is_analyze_enabled' => true,
            'name' => 'FirstComponent',
            'roots' => [
                [
                    // Корневой путь компонента. Можно указать директорию или отдельный файл.
                    // Анализатор читает PHP-файлы через AST, поэтому PSR-4 совпадение имени класса и пути не обязательно.
                    'path' => '/path/to/First/Component',

                    // Namespace используется для fallback-имен executable scripts и файлов без объявленных символов,
                    // а также помогает сопоставлять зависимости, когда у найденного элемента нет известного пути.
                    'namespace' => 'First\Component',
                ],
                // Если код одного компонента лежит в нескольких местах, добавьте несколько root-ов.
                //
                // [
                //     'path' => '/path/to/component/first',
                //     'namespace' => 'Component\First',
                // ],
            ],
            // Директории или файлы, которые будут пропущены при анализе этого компонента.
            'excluded' => [
                '/path/to/First/Component/dir1',
                '/path/to/First/Component/dir2',
            ],
            'restrictions' => [
                // Имеет приоритет над общей настройкой restrictions.max_allowable_distance.
                // 'max_allowable_distance' => 0.1,

                // Список РАЗРЕШЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Если ключ отсутствует, равен [] или null, список разрешенных зависимостей не ограничивается.
                // Не используйте одновременно с forbidden_dependencies.
                // 'allowed_dependencies' => ['SecondComponent'],

                // Список ЗАПРЕЩЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Если ключ отсутствует, равен [] или null, список запрещенных зависимостей не ограничивается.
                // Не используйте одновременно с allowed_dependencies.
                // 'forbidden_dependencies' => ['ThirdComponent'],

                // Список публичных элементов компонента: FQCN, директории или файлы.
                // Если отсутствует или пустой, все элементы считаются публичными.
                // Если не пустой, не перечисленные в списке элементы будут считаться приватными.
                // Не используйте одновременно с private_elements.
                // 'public_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
                //     __DIR__ . '/directory/with/public/elements',
                // ],

                // Список приватных элементов компонента: FQCN, директории или файлы.
                // Если отсутствует или пустой, все элементы считаются публичными.
                // Не используйте одновременно с public_elements.
                // 'private_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
                //     __DIR__ . '/directory/with/private/elements',
                // ],
            ],
        ],
        [
            'name' => 'SecondComponent',
            'roots' => [
                [
                    'path' => '/path/to/Component/Second',
                    'namespace' => 'Component\Second',
                ],
            ],
        ],
        [
            'name' => 'ThirdComponent',
            'roots' => [
                [
                    'path' => '/path/to/Component/Third',
                    'namespace' => 'Component\Third',
                ],
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
