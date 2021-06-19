<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage\DependencyUnitOfCodeExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage\UnitsOfCodeGraphExtractor;
use Chetkov\PHPCleanArchitecture\Service\Report\TemplateRendererInterface;

/**
 * Class UnitOfCodePageRenderingService
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class UnitOfCodePageRenderingService
{
    use UidGenerator;

    /** @var TemplateRendererInterface */
    private $templateRenderer;

    /** @var ObjectsGraphBuilder */
    private $unitsOfCodeGraphBuilder;

    /** @var DependencyUnitOfCodeExtractor */
    private $dependencyUnitOfCodeExtractor;

    /** @var UnitsOfCodeGraphExtractor */
    private $unitsOfCodeGraphExtractor;

    /**
     * @param TemplateRendererInterface $templateRenderer
     */
    public function __construct(TemplateRendererInterface $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
        $this->unitsOfCodeGraphBuilder = new ObjectsGraphBuilder();
        $this->dependencyUnitOfCodeExtractor = new DependencyUnitOfCodeExtractor();
        $this->unitsOfCodeGraphExtractor = new UnitsOfCodeGraphExtractor();
    }

    /**
     * @param string $reportsPath
     * @param UnitOfCode $unitOfCode
     * @param Component ...$processedComponents
     */
    public function render(string $reportsPath, UnitOfCode $unitOfCode, Component ...$processedComponents): void
    {
        $this->unitsOfCodeGraphBuilder->reset();

        $extractedInputDependencies = [];
        foreach ($unitOfCode->inputDependencies() as $inputDependency) {
            $this->unitsOfCodeGraphBuilder->addEdge($inputDependency, $unitOfCode);
            $extractedInputDependencies[] = $this->dependencyUnitOfCodeExtractor->extract($unitOfCode, $inputDependency, $processedComponents);
        }

        $extractedOutputDependencies = [];
        foreach ($unitOfCode->outputDependencies() as $outputDependency) {
            $this->unitsOfCodeGraphBuilder->addEdge($unitOfCode, $outputDependency);
            $extractedOutputDependencies[] = $this->dependencyUnitOfCodeExtractor->extract($unitOfCode, $outputDependency, $processedComponents, false);
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

        $reportContent = $this->templateRenderer->render('unit-of-code-info.twig', [
            'name' => $unitOfCode->name(),
            'component' => [
                'uid' => $this->generateUid($unitOfCode->component()->name()),
                'name' => $unitOfCode->component()->name(),
            ],
            'type' => $type,
            'is_public' => $unitOfCode->isAccessibleFromOutside() ? 'Да' : 'Нет',
            'is_abstract' => $unitOfCode->isAbstract() ? 'Да' : 'Нет',
            'instability_rate' => $unitOfCode->calculateInstabilityRate(),
            'primitiveness_rate' => $unitOfCode->calculatePrimitivenessRate(),
            'input_dependencies' => $extractedInputDependencies,
            'output_dependencies' => $extractedOutputDependencies,
            'units_of_code_graph' => $this->unitsOfCodeGraphExtractor->extract($this->unitsOfCodeGraphBuilder),
        ]);

        file_put_contents($reportsPath . '/' . $this->generateUid($unitOfCode->name()) . '.html', $reportContent);
    }
}
