<?php

declare(strict_types=1);

use Chetkov\PHPCleanArchitecture\Infrastructure\Event\EventManager;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report\ComponentReportRenderingEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report\ReportBuildingEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report\ReportRenderingEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report\UnitOfCodeReportRenderedEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Render\TwigToTemplateRendererInterfaceAdapter;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Analysis\AnalysisEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Analysis\ComponentAnalysisEventListener;
use Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Analysis\FileAnalyzedEventListener;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CompositeDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\CodeParsingDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCalledStaticallyParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCreatedThroughNewParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesFromInstanceofConstructionParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\MethodAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ParamAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\PropertyAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ReturnAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ThrowsAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\VarAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\ReflectionDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\ReportRenderingService;
use Chetkov\PHPCleanArchitecture\Service\Report\ReportRenderingServiceInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return [
    // Директория в которую будут складываться файлы отчета
    'reports_dir' => (string) getenv('PHPCA_REPORTS_DIR') ?: __DIR__ . '/phpca-reports',

    // Учет vendor пакетов (каждый подключенный пакет, за исключением перечисленных в excluded, будет представлен компонентом)
    'vendor_based_components' => [
        'enabled' => true,
        'vendor_path' => __DIR__ . '/vendor',
        'excluded' => [
//            '/excluded/vendor/package/dir',
        ],
    ],

    // Общие для всех компонентов ограничения
    'restrictions' => [
        // Включение/отключение обнаружения нарушений принципа ацикличности зависимостей.
         'check_acyclic_dependencies_principle' => true,

        // Включение/отключение обнаружения нарушений принципа устойчивых зависимостей.
         'check_stable_dependencies_principle' => true,

        // Максимально допустимое расстояние до главной диагонали.
        // Элемент может отсутствовать или быть null, в таком случае ограничения не будут применены.
//         'max_allowable_distance' => 0.1,
    ],

    // Описание компонентов и их ограничений
    'components' => [
        'model' => [
            // Требуется-ли анализировать содержимое компонента, или он описан исключительно для возможности
            // сопоставления зависимости других компонентов от элементов текущего?
            // Значение по умолчанию true (в случае отсутствия его в конфиге).
            'is_analyze_enabled' => true,
            'name' => 'model',
            'roots' => [
                [
                    'path' => __DIR__ . '/src/Model',
                    'namespace' => 'Chetkov\PHPCleanArchitecture\Model',
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
//                '/path/to/First/Component/dir1',
//                '/path/to/First/Component/dir2',
            ],
            'restrictions' => [
                // Имеет приоритет над общей настройкой restrictions->max_allowable_distance
                // 'max_allowable_distance' => 0.1,

                // Список РАЗРЕШЕННЫХ исходящих зависимостей. Заполняется именами других компонентов.
                // Может отсутствовать, быть [] или null, в таком случае никакие ограничения накладываться не будут.
                // Не должен использоваться совместно с forbidden_dependencies!
                 'allowed_dependencies' => ['model'],

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
                //     __DIR__ . '/directory/with/public/elements',
                // ],

                // Список приватных элементов компонента. Если отсутствует или пустой, все элементы считаются публичными.
                // Не должен использоваться совместно с public_elements!
                // 'private_elements' => [
                //     First\Component\FirstClass::class,
                //     First\Component\SecondClass::class,
                //     __DIR__ . '/directory/with/private/elements',
                // ],
            ],
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
                'allowed_dependencies' => ['service', 'model'],
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
            return new CompositeDependenciesFinder(...[
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
        'report_rendering_service' => static function (EventManagerInterface $eventManager): ReportRenderingServiceInterface {
            $templatesLoader = new FilesystemLoader(ReportRenderingService::templatesPath());
            $twigRenderer = new Environment($templatesLoader);
            $twigAdapter = new TwigToTemplateRendererInterfaceAdapter($twigRenderer);
            return new ReportRenderingService($eventManager, $twigAdapter);
        },
        //Фабрика, собирающая и настраивающая EventManager
        'event_manager' => static function (): EventManagerInterface {
            return new EventManager([
                new ReportBuildingEventListener(),
                new AnalysisEventListener(),
                new ComponentAnalysisEventListener(),
                new FileAnalyzedEventListener(),
                new ReportBuildingEventListener(),
                new ReportRenderingEventListener(),
                new ComponentReportRenderingEventListener(),
                new UnitOfCodeReportRenderedEventListener(),
            ]);
        }
    ],
];