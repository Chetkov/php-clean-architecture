<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report;

use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Report\Extractor\UnitOfCodePage\DependencyUnitOfCodeExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\Extractor\UnitOfCodePage\UnitsOfCodeGraphExtractor;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class UnitOfCodePageRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report
 */
class UnitOfCodePageRenderingService
{
    use UidGenerator;

    /** @var Environment */
    private $twig;

    /** @var ObjectsGraphBuilder */
    private $unitsOfCodeGraphBuilder;

    /** @var DependencyUnitOfCodeExtractor */
    private $dependencyUnitOfCodeExtractor;

    /** @var UnitsOfCodeGraphExtractor */
    private $unitsOfCodeGraphExtractor;

    public function __construct()
    {
        $templatesLoader = new FilesystemLoader(__DIR__ . '/Template/');
        $this->twig = new Environment($templatesLoader);
        $this->unitsOfCodeGraphBuilder = new ObjectsGraphBuilder();
        $this->dependencyUnitOfCodeExtractor = new DependencyUnitOfCodeExtractor();
        $this->unitsOfCodeGraphExtractor = new UnitsOfCodeGraphExtractor();
    }

    /**
     * @param string $reportsPath
     * @param UnitOfCode $unitOfCode
     * @param Module ...$processedModules
     */
    public function render(string $reportsPath, UnitOfCode $unitOfCode, Module ...$processedModules): void
    {
        $this->unitsOfCodeGraphBuilder->reset();

        $extractedInputDependencies = [];
        foreach ($unitOfCode->inputDependencies() as $inputDependency) {
            $this->unitsOfCodeGraphBuilder->addEdge($inputDependency, $unitOfCode);
            $extractedInputDependencies[] = $this->dependencyUnitOfCodeExtractor->extract($unitOfCode, $inputDependency, $processedModules);
        }

        $extractedOutputDependencies = [];
        foreach ($unitOfCode->outputDependencies() as $outputDependency) {
            $this->unitsOfCodeGraphBuilder->addEdge($unitOfCode, $outputDependency);
            $extractedOutputDependencies[] = $this->dependencyUnitOfCodeExtractor->extract($unitOfCode, $outputDependency, $processedModules, false);
        }

        switch (true) {
            case $unitOfCode->isInterface():
                $type = 'Интерфейс';
                break;
            case $unitOfCode->isClass():
                $type = 'Класс';
                break;
            case $unitOfCode->isTrait():
                $type = 'Трэйт';
                break;
            case $unitOfCode->isPrimitive():
                $type = 'Примитив';
                break;
            default:
                $type = 'Неопределен';
        }

        file_put_contents($reportsPath . '/' . $this->generateUid($unitOfCode->name()) . '.html', $this->twig->render('unit-of-code-info.twig', [
            'name' => $unitOfCode->name(),
            'module' => [
                'uid' => $this->generateUid($unitOfCode->module()->name()),
                'name' => $unitOfCode->module()->name(),
            ],
            'type' => $type,
            'is_public' => $unitOfCode->isAccessibleFromOutside() ? 'Да' : 'Нет',
            'is_abstract' => $unitOfCode->isAbstract() ? 'Да' : 'Нет',
            'variability_rate' => $unitOfCode->calculateVariabilityRate(),
            'primitiveness_rate' => $unitOfCode->calculatePrimitivenessRate(),
            'input_dependencies' => $extractedInputDependencies,
            'output_dependencies' => $extractedOutputDependencies,
            'units_of_code_graph' => $this->unitsOfCodeGraphExtractor->extract($this->unitsOfCodeGraphBuilder),
        ]));
    }
}
