<?php

use Chetkov\PHPCleanArchitecture\Model\Event\EventManager;
use Chetkov\PHPCleanArchitecture\Model\Event\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\Listener\AnalysisEventListener;
use Chetkov\PHPCleanArchitecture\Model\Event\Listener\ComponentAnalysisEventListener;
use Chetkov\PHPCleanArchitecture\Model\Event\Listener\FileAnalyzedEventListener;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\AggregationDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\CodeParsingDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ClassesCalledStaticallyParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ClassesCreatedThroughNewParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ClassesFromInstanceofConstructionParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\MethodAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ParamAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\PropertyAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ReturnAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\ThrowsAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\CodeParsing\Strategy\VarAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\DependenciesFinderInterface;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\ReflectionDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\ReportRenderingService;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;
use Psr\Log\LoggerInterface;

return [
    // Директория в которую будут складываться файлы отчета
    'reports_dir' => __DIR__ . '/phpca-reports',

    // Учет vendor пакетов (каждый подключенный пакет, за исключением перечисленных в excluded, будет представлен компонентом)
    'vendor_based_components' => [
        'enabled' => true,
        'vendor_path' => '/path/to/vendor',
        'excluded' => [
            '/excluded/vendor/package/dir',
        ],
    ],

    // Общие для всех компонентов ограничения
    'restrictions' => [
        // Включение/отключение обнаружения нарушений принципа ацикличности зависимостей.
        // 'check_acyclic_dependencies_principle' => true,

        // Включение/отключение обнаружения нарушений принципа устойчивых зависимостей.
        // 'check_stable_dependencies_principle' => true,

        // Максимально допустимое расстояние до главной диагонали.
        // Элемент может отсутствовать или быть null, в таком случае ограничения не будут применены.
        // 'max_allowable_distance' => 0.1,
    ],

    // Описание компонентов и их ограничений
    'components' => [
        [
            // Требуется-ли анализировать содержимое компонента, или он описан исключительно для возможности
            // сопоставления зависимости других компонентов от элементов текущего?
            // Значение по умолчанию true (в случае отсутствия его в конфиге).
            'is_analyze_enabled' => true,
            'name' => 'FirstComponent',
            'roots' => [
                [
                    'path' => '/path/to/First/Component',
                    'namespace' => 'First\Component',
                ],
                // Иногда, особенно в старых проектах, код логически относящийся к одному компоненту, разбросан по разным частям
                // системы. В таком случае можно указать в конфиге несколько корневых директорий и, т.о. отнести их содержимое
                // какому-то одному компоненту.
                //
                // [
                //     'path' => '/path/to/component/first',
                //     'namespace' => 'Component\First',
                // ],
            ],
            //Директории или файлы, которые будут пропущены в процессе анализа
            'excluded' => [
                '/path/to/First/Component/dir1',
                '/path/to/First/Component/dir2',
            ],
            'restrictions' => [
                // Имеет приоритет над общей настройкой restrictions->max_allowable_distance
                // 'max_allowable_distance' => 0.1,

                // Список РАЗРЕШЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Может отсутствовать, быть [] или null, в таком случае никакие ограничения накладываться не будут.
                // Не должен использоваться совместно с forbidden_dependencies!
                // 'allowed_dependencies' => ['SecondComponent'],

                // Список ЗАПРЕЩЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Может отсутствовать, быть [] или null, в таком случае никакие ограничения накладываться не будут.
                // Не должен использоваться совместно с allowed_dependencies!
                // 'forbidden_dependencies' => ['ThirdComponent'],

                // Список публичных элементов компонента. Если отсутствует или пустой, все элементы считаются публичными.
                // Если не пустой, не перечисленные в списке элементы будут считаться приватными.
                // Не должен использоваться совместно с private_elements!
                // 'public_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
                // ],

                // Список приватных элементов компонента. Если отсутствует или пустой, все элементы считаются публичными.
                // Не должен использоваться совместно с public_elements!
                // 'private_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
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

    // Исключения
    'exclusions' => [
        'allowed_state' => [
            'enabled' => false,
            'storage' => __DIR__ . '/phpca-allowed-state.php',
        ],
    ],

    'factories' => [
        //Фабрика, собирающая DependenciesFinder
        'dependencies_finder' => static function (): DependenciesFinderInterface {
            return new AggregationDependenciesFinder(...[
                new ReflectionDependenciesFinder(),
                new CodeParsingDependenciesFinder(...[
                    new ClassesCreatedThroughNewParsingStrategy(),
                    new ClassesCalledStaticallyParsingStrategy(),
                    new ClassesFromInstanceofConstructionParsingStrategy(),
                    new PropertyAnnotationsParsingStrategy(),
                    new MethodAnnotationsParsingStrategy(),
                    new ParamAnnotationsParsingStrategy(),
                    new ReturnAnnotationsParsingStrategy(),
                    new ThrowsAnnotationsParsingStrategy(),
                    new VarAnnotationsParsingStrategy(),
                ]),
            ]);
        },
        //Фабрика, собирающая сервис рендеринга отчетов
        'report_rendering_service' => static function (): ReportRenderingServiceInterface {
            return new ReportRenderingService();
        },
        //Фабрика, собирающая и настраивающая EventManager
        'event_manager' => static function (): EventManagerInterface {
            return new EventManager([
                new FileAnalyzedEventListener(),
                new ComponentAnalysisEventListener(),
                new AnalysisEventListener(),
            ]);
        }
    ],
];