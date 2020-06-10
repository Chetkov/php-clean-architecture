<?php

use Chetkov\ConsoleLogger\ConsoleLoggerFactory;
use Chetkov\ConsoleLogger\LoggerConfig;
use Chetkov\ConsoleLogger\StyledLogger\LoggerStyle;
use Chetkov\ConsoleLogger\StyledLogger\StyledLoggerDecorator;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\AggregationDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\CodeParsingDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ClassesCalledStaticallyParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ClassesCreatedThroughNewParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ClassesFromInstanceofConstructionParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\TypesFromThrowAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\TypesFromVarAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\DependenciesFinderInterface;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\ReflectionDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\ReportRenderingService;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;
use Psr\Log\LoggerInterface;

return [
    // Директория в которую будут складываться файлы отчета
    'reports_dir' => __DIR__,

    // Анализ с учетом пакетов подключенных через composer
    'vendor_based_modules' => [
        'enabled' => true,
        'vendor_path' => '/path/to/vendor',
        'excluded' => [
            '/excluded/vendor/package/dir',
        ],
    ],

    // Общие для всех модулей ограничения
    'restrictions' => [
        // Включение/отключение обнаружения нарушений принципа ацикличности зависимостей.
        // 'check_acyclic_dependencies_principle' => true,

        // Включение/отключение обнаружения нарушений принципа устойчивых зависимостей.
        // 'check_stable_dependencies_principle' => true,

        // Максимально допустимое расстояние до главной диагонали.
        // Элемент может отсутствовать или быть null, в таком случае ограничения не будут применены.
        // 'max_allowable_distance' => 0.1,
    ],

    // Описание модулей и их ограничений
    'modules' => [
        [
            // Требуется-ли анализировать содержимое компонента, или он описан исключительно для возможности
            // сопоставления зависимости других компонентов от элементов текущего?
            // Значение по умолчанию true (в случае отсутствия его в конфиге).
            'is_analyze_enabled' => true,
            'name' => 'FirstModule',
            'roots' => [
                [
                    'path' => '/path/to/First/Module',
                    'namespace' => 'First\Module',
                ],
                // Иногда, особенно в старых проектах, код логически относимый к одному модулю, разбросан по разным частям
                // системы. В таком случае можно указать в конфиге несколько корневых директорий и, т.о. отнести их содержимое
                // какому-то одному модулю.
                //
                // [
                //     'path' => '/path/to/module/first',
                //     'namespace' => 'Module\First',
                // ],
            ],
            //Директории или файлы, которые будут пропущены в процессе анализа
            'excluded' => [
                '/path/to/First/Module/dir1',
                '/path/to/First/Module/dir2',
            ],
            'restrictions' => [
                // Имеет приоритет над общей настройкой restrictions->max_allowable_distance
                // 'max_allowable_distance' => 0.1,

                // Список РАЗРЕШЕННЫХ исходящих зависимостей. Заполняется именами других модулей.
                // Может отсутствовать, быть [] или null, в таком случае никакие ограничения накладываться не будут.
                // Не должен содержать значений, перечисленных в элементе forbidden_dependencies!
                // 'allowed_dependencies' => ['SecondModule'],

                // Список ЗАПРЕЩЕННЫХ исходящих зависимостей. Заполняется именами других модулей.
                // Может отсутствовать, быть [] или null, в таком случае никакие ограничения накладываться не будут.
                // Не должен содержать значений, перечисленных в элементе allowed_dependencies!
                // 'forbidden_dependencies' => ['ThirdModule'],

                // Список публичных элементов модуля. Если отсутствует или пустой, все элементы считаются публичными.
                // Если не пустой, не перечисленные в списке элементы будут считаться приватными.
                // Не должен содержать элементов, перечисленных в private_elements!
                // 'public_elements' => [
                //     First\Module\FirstClass::class,
                //     First\Module\SecondClass::class,
                // ],

                // Список приватных элементов модуля. Если отсутствует или пустой, все элементы считаются публичными.
                // Не должен содержать элементов, перечисленных в public_elements!
                // 'private_elements' => [
                //     First\Module\FirstClass::class,
                //     First\Module\SecondClass::class,
                // ],
            ],
        ],
        [
            'name' => 'SecondModule',
            'roots' => [
                [
                    'path' => '/path/to/Module/Second',
                    'namespace' => 'Module\Second',
                ],
            ],
        ],
        [
            'name' => 'ThirdModule',
            'roots' => [
                [
                    'path' => '/path/to/Module/Third',
                    'namespace' => 'Module\Third',
                ],
            ],
        ],
    ],
    'factories' => [
        //Фабрика, собирающая DependenciesFinder
        'dependencies_finder' => function (): DependenciesFinderInterface {
            return new AggregationDependenciesFinder(...[
                new ReflectionDependenciesFinder(),
                new CodeParsingDependenciesFinder(...[
                    new ClassesCreatedThroughNewParsingStrategy(),
                    new ClassesCalledStaticallyParsingStrategy(),
                    new ClassesFromInstanceofConstructionParsingStrategy(),
                    new TypesFromVarAnnotationsParsingStrategy(),
                    new TypesFromThrowAnnotationsParsingStrategy(),
                ]),
            ]);
        },
        //Фабрика, собирающая сервис рендеринга отчетов
        'report_rendering_service' => function (): ReportRenderingServiceInterface {
            return new ReportRenderingService();
        },
        //Фабрика, собирающая Logger
        'logger' => function (): LoggerInterface {
            $loggerConfig = new LoggerConfig();
            $loggerConfig
                ->setIsShowDateTime(true)
                ->setIsShowLevel(false)
                ->setIsShowData(false)
                ->setDateTimeFormat('H:i:s')
                ->setFieldDelimiter(' :: ');
            return new StyledLoggerDecorator(
                ConsoleLoggerFactory::create($loggerConfig),
                new LoggerStyle()
            );
        }
    ],
];