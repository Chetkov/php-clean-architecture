import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { useVirtualizer } from '@tanstack/react-virtual';
import {
  AlertTriangle,
  ArrowRight,
  Box,
  CheckCircle2,
  ChevronRight,
  CircleDot,
  Copy,
  FileCode2,
  GitBranch,
  Check,
  Layers3,
  Maximize2,
  Minimize2,
  Repeat2,
  RotateCcw,
  Search,
  SlidersHorizontal,
  X,
  ShieldAlert,
  ZoomIn,
  ZoomOut,
} from 'lucide-react';
import './styles.css';

const dependencyStatuses = ['allowed', 'internal', 'allowed-state', 'private', 'blocked'];
const graphWidth = 1120;
const graphHeight = 420;
const graphPadding = 58;
const graphLayouts = ['circle', 'grid', 'layers'];
const defaultGraphViewport = { x: 0, y: 0, scale: 1 };

const fallbackReport = {
  schemaVersion: 1,
  generatedAt: null,
  summary: {
    components: 0,
    units: 0,
    dependencies: 0,
    violations: 0,
    activeViolations: 0,
    legacy: {
      units: 0,
      legacyUnits: 0,
      modernUnits: 0,
      linesOfCode: 0,
      legacyLinesOfCode: 0,
      modernLinesOfCode: 0,
      legacyRate: 0,
      modernRate: 0,
    },
  },
  components: [],
  units: [],
  dependencies: [],
  violations: [],
};

const locales = [
  { code: 'en', label: 'EN' },
  { code: 'ru', label: 'RU' },
  { code: 'zh', label: '中文' },
];

const localeToIntl = {
  en: 'en',
  ru: 'ru',
  zh: 'zh-CN',
};

const dictionaries = {
  en: {
    activeIssues: 'Active issues',
    aiMatrix: 'A/I matrix',
    api: 'API',
    architectureReport: 'Architecture report',
    modernizationProgress: 'Modernization progress',
    modernized: 'Modernized',
    modernCode: 'Modern',
    legacyCode: 'Legacy',
    legacyLines: 'legacy LoC',
    modernLines: 'modern LoC',
    legacyUnits: 'legacy UoC',
    modernUnits: 'modern UoC',
    linesOfCode: 'LoC',
    abstract: 'Abstract',
    abstractness: 'Abstractness',
    all: 'All',
    allowed: 'allowed',
    allowedState: 'allowed state',
    blocked: 'blocked',
    componentDependencyDistribution: 'Component dependency distribution',
    componentGraph: 'Component graph',
    componentMetrics: 'Component metrics',
    components: 'Components',
    componentDependsOn: 'Depends on components',
    componentResults: 'Components',
    clearSearch: 'Clear search',
    clearSelection: 'Clear',
    componentFilterSearch: 'Search components',
    componentFilterSelected: 'selected',
    componentInternals: 'Component internals',
    componentInternalsGraph: 'Internal component overview',
    componentInternalsHint: 'This component has a nested report. The graph below shows its internal components.',
    componentLinksShort: 'comp',
    componentLinksSummary: '{count} component links',
    copy: 'Copy',
    copyFilePath: 'Copy file path',
    copyUnitName: 'Copy unit name',
    copied: 'Copied',
    dependedOnByComponentsSummary: '{count} components depend here',
    dependsOnComponentsSummary: 'Depends on {count} components',
    dependencyOverview: 'Dependency map',
    filtered: 'filtered',
    noUnitSelected: 'Select a unit to inspect its dependencies',
    openSearch: 'Search',
    openNestedReport: 'Open nested report',
    overview: 'Overview',
    parentComponentRelations: 'Component relations',
    showMore: 'Show more',
    zoneOfPain: 'Painful',
    zoneOfUselessness: 'Useless',
    dependencyDirection: 'Direction',
    dependencyFiles: 'files',
    dependencyFilters: 'Dependency filters',
    dependencyGroups: 'Dependency groups',
    dependencyBlockedViolation: '"{from}" must not depend on "{to}".',
    dependencyPrivateViolation: '"{from}" uses non-public "{to}".',
    dependencyRows: 'dependencies',
    dependencyUnitsShort: 'unit',
    dependencyStatus: 'Status',
    directoryTree: 'Directory tree',
    dependencies: 'Dependencies',
    dependencyGraphLabel: 'Component dependency graph',
    dependencyUnavailable: 'Unknown dependency',
    distance: 'Distance',
    distanceRanking: 'Distance from Main Sequence',
    external: 'external',
    flipDependencyDirection: 'Flip dependency direction',
    fromComponents: 'From components',
    generatedReport: 'Generated report',
    globalComponentGraph: 'Global component graph',
    graphLayout: 'Layout',
    graphLayoutCircle: 'Circle',
    graphLayoutGrid: 'Grid',
    graphLayoutLayers: 'Layers',
    graphComponents: 'Graph components',
    graphFullscreenOpen: 'Open graph fullscreen',
    graphFullscreenClose: 'Exit fullscreen',
    graphZoomIn: 'Zoom in',
    graphZoomOut: 'Zoom out',
    graphViewportReset: 'Reset graph view',
    incoming: 'Incoming',
    incomingDependencies: 'Incoming dependencies',
    in: 'In',
    instability: 'Instability',
    internal: 'internal',
    issues: 'issues',
    loadingReport: 'Loading report',
    name: 'Name',
    noComponents: 'No components',
    notApplicable: 'n/a',
    unknown: 'unknown',
    no: 'no',
    noActiveIssues: 'No active architecture issues for the current filters',
    noComponentData: 'No component data',
    noComponentsSelected: 'None selected',
    noDependencies: 'No dependencies',
    noDependencyDistribution: 'No dependency distribution',
    noExternalDependencies: 'No external component dependencies',
    noSelection: 'Overview',
    noMatchingDependencies: 'No dependencies match the current filters',
    noMatchingUnits: 'No units match the current filters',
    out: 'Out',
    outgoing: 'Outgoing',
    outgoingDependencies: 'Outgoing dependencies',
    path: 'Path',
    primitive: 'Primitiveness',
    privateApi: 'private',
    publicApi: 'public',
    readingReport: 'Reading report.json...',
    reportDataUnavailable: 'Report data unavailable',
    reportJsonNotFound: 'report.json was not found near index.html.',
    reportLanguage: 'Report language',
    reportView: 'Report view',
    rootReport: 'System',
    searchPlaceholder: 'Search components, units, paths or dependencies',
    searchResults: 'Search results',
    noSearchResults: 'No matches',
    selectAll: 'Select all',
    selectedUnit: 'Selected unit',
    sourceFirst: 'What -> Depends on',
    targetFirst: 'Depends on <- What',
    toComponents: 'To components',
    type: 'Type',
    unitDependencyGraphLabel: 'Selected unit dependency graph',
    unitResults: 'Units',
    dependencyResults: 'Dependencies',
    violationResults: 'Issues',
    unitDependenciesSummary: '{count} unit dependencies',
    unitsCount: 'units',
    units: 'Units',
    violations: 'Violations',
    yes: 'yes',
  },
  ru: {
    activeIssues: 'Активные проблемы',
    aiMatrix: 'Матрица A/I',
    api: 'API',
    architectureReport: 'Архитектурный отчет',
    modernizationProgress: 'Прогресс модернизации',
    modernized: 'Модернизировано',
    modernCode: 'Новый код',
    legacyCode: 'Легаси',
    legacyLines: 'строк легаси',
    modernLines: 'строк нового кода',
    legacyUnits: 'легаси UoC',
    modernUnits: 'новых UoC',
    linesOfCode: 'строк кода',
    abstract: 'Абстрактный',
    abstractness: 'Абстрактность',
    all: 'Все',
    allowed: 'разрешено',
    allowedState: 'разрешенное состояние',
    blocked: 'запрещено',
    componentDependencyDistribution: 'Распределение зависимостей компонента',
    componentGraph: 'Граф компонентов',
    componentMetrics: 'Метрики компонента',
    components: 'Компоненты',
    componentDependsOn: 'Зависит от компонентов',
    componentResults: 'Компоненты',
    clearSearch: 'Очистить поиск',
    clearSelection: 'Сбросить',
    componentFilterSearch: 'Поиск компонентов',
    componentFilterSelected: 'выбрано',
    componentInternals: 'Внутренности компонента',
    componentInternalsGraph: 'Внутренний обзор компонента',
    componentInternalsHint: 'У компонента есть вложенный отчет. Граф ниже показывает его внутренние компоненты.',
    componentLinksShort: 'комп',
    componentLinksSummary: '{count} связей компонентов',
    copy: 'Копировать',
    copyFilePath: 'Копировать путь файла',
    copyUnitName: 'Копировать имя юнита',
    copied: 'Скопировано',
    dependedOnByComponentsSummary: '{count} компонентов зависят',
    dependsOnComponentsSummary: 'Зависит от {count} компонентов',
    dependencyOverview: 'Карта зависимостей',
    filtered: 'отфильтровано',
    noUnitSelected: 'Выберите юнит, чтобы посмотреть его зависимости',
    openSearch: 'Поиск',
    openNestedReport: 'Открыть вложенный отчет',
    overview: 'Обзор',
    parentComponentRelations: 'Связи компонента',
    showMore: 'Показать еще',
    zoneOfPain: 'Больно',
    zoneOfUselessness: 'Бесполезно',
    dependencyDirection: 'Направление',
    dependencyFiles: 'файлов',
    dependencyFilters: 'Фильтры зависимостей',
    dependencyGroups: 'Группы зависимостей',
    dependencyBlockedViolation: '"{from}" не должен зависеть от "{to}".',
    dependencyPrivateViolation: '"{from}" использует непубличный "{to}".',
    dependencyRows: 'зависимостей',
    dependencyUnitsShort: 'юнит',
    dependencyStatus: 'Статус',
    directoryTree: 'Дерево директорий',
    dependencies: 'Зависимости',
    dependencyGraphLabel: 'Граф зависимостей компонентов',
    dependencyUnavailable: 'Зависимость не найдена',
    distance: 'Расстояние',
    distanceRanking: 'Расстояние до главной последовательности',
    external: 'внешняя',
    flipDependencyDirection: 'Перевернуть направление зависимостей',
    fromComponents: 'Исходные компоненты',
    generatedReport: 'Сформированный отчет',
    globalComponentGraph: 'Общий граф компонентов',
    graphLayout: 'Компоновка',
    graphLayoutCircle: 'Круг',
    graphLayoutGrid: 'Сетка',
    graphLayoutLayers: 'Слои',
    graphComponents: 'Компоненты графа',
    graphFullscreenOpen: 'Открыть граф во весь экран',
    graphFullscreenClose: 'Выйти из полноэкранного режима',
    graphZoomIn: 'Приблизить граф',
    graphZoomOut: 'Отдалить граф',
    graphViewportReset: 'Сбросить положение графа',
    incoming: 'Входящие',
    incomingDependencies: 'Входящие зависимости',
    in: 'Вх.',
    instability: 'Неустойчивость',
    internal: 'внутренняя',
    issues: 'проблем',
    loadingReport: 'Загрузка отчета',
    name: 'Имя',
    noComponents: 'Нет компонентов',
    notApplicable: 'н/д',
    unknown: 'неизвестно',
    no: 'нет',
    noActiveIssues: 'Для текущих фильтров нет активных архитектурных проблем',
    noComponentData: 'Нет данных компонента',
    noComponentsSelected: 'Ничего не выбрано',
    noDependencies: 'Нет зависимостей',
    noDependencyDistribution: 'Нет распределения зависимостей',
    noExternalDependencies: 'Нет внешних зависимостей компонентов',
    noSelection: 'Обзор',
    noMatchingDependencies: 'Нет зависимостей по текущим фильтрам',
    noMatchingUnits: 'Нет юнитов по текущим фильтрам',
    out: 'Исх.',
    outgoing: 'Исходящие',
    outgoingDependencies: 'Исходящие зависимости',
    path: 'Путь',
    primitive: 'Примитивность',
    privateApi: 'приватный',
    publicApi: 'публичный',
    readingReport: 'Читаю report.json...',
    reportDataUnavailable: 'Данные отчета недоступны',
    reportJsonNotFound: 'report.json не найден рядом с index.html.',
    reportLanguage: 'Язык отчета',
    reportView: 'Раздел отчета',
    rootReport: 'Система',
    searchPlaceholder: 'Поиск по компонентам, юнитам, путям или зависимостям',
    searchResults: 'Результаты поиска',
    noSearchResults: 'Ничего не найдено',
    selectAll: 'Выбрать все',
    selectedUnit: 'Выбранный юнит',
    sourceFirst: 'Что -> От чего',
    targetFirst: 'От чего <- Что',
    toComponents: 'Целевые компоненты',
    type: 'Тип',
    unitDependencyGraphLabel: 'Граф зависимостей выбранного юнита',
    unitResults: 'Юниты',
    dependencyResults: 'Зависимости',
    violationResults: 'Проблемы',
    unitDependenciesSummary: '{count} зависимостей юнитов',
    unitsCount: 'юнитов',
    units: 'Юниты',
    violations: 'Нарушения',
    yes: 'да',
  },
  zh: {
    activeIssues: '活跃问题',
    aiMatrix: 'A/I 矩阵',
    api: 'API',
    architectureReport: '架构报告',
    modernizationProgress: '现代化进度',
    modernized: '已现代化',
    modernCode: '现代代码',
    legacyCode: '遗留代码',
    legacyLines: '遗留代码行',
    modernLines: '现代代码行',
    legacyUnits: '遗留 UoC',
    modernUnits: '现代 UoC',
    linesOfCode: '代码行',
    abstract: '抽象',
    abstractness: '抽象度',
    all: '全部',
    allowed: '允许',
    allowedState: '允许状态',
    blocked: '阻止',
    componentDependencyDistribution: '组件依赖分布',
    componentGraph: '组件图',
    componentMetrics: '组件指标',
    components: '组件',
    componentDependsOn: '依赖组件',
    componentResults: '组件',
    clearSearch: '清除搜索',
    clearSelection: '清除',
    componentFilterSearch: '搜索组件',
    componentFilterSelected: '已选',
    componentInternals: '组件内部',
    componentInternalsGraph: '组件内部概览',
    componentInternalsHint: '此组件包含嵌套报告。下面的图展示它的内部组件。',
    componentLinksShort: '组件',
    componentLinksSummary: '{count} 个组件连接',
    copy: '复制',
    copyFilePath: '复制文件路径',
    copyUnitName: '复制单元名称',
    copied: '已复制',
    dependedOnByComponentsSummary: '{count} 个组件依赖这里',
    dependsOnComponentsSummary: '依赖 {count} 个组件',
    dependencyOverview: '依赖地图',
    filtered: '已筛选',
    noUnitSelected: '选择一个单元以查看其依赖',
    openSearch: '搜索',
    openNestedReport: '打开嵌套报告',
    overview: '概览',
    parentComponentRelations: '组件关系',
    showMore: '显示更多',
    zoneOfPain: '痛点',
    zoneOfUselessness: '无用',
    dependencyDirection: '方向',
    dependencyFiles: '文件',
    dependencyFilters: '依赖筛选',
    dependencyGroups: '依赖分组',
    dependencyBlockedViolation: '"{from}" 不应依赖 "{to}"。',
    dependencyPrivateViolation: '"{from}" 使用了非公开的 "{to}"。',
    dependencyRows: '依赖',
    dependencyUnitsShort: '单元',
    dependencyStatus: '状态',
    directoryTree: '目录树',
    dependencies: '依赖',
    dependencyGraphLabel: '组件依赖图',
    dependencyUnavailable: '未知依赖',
    distance: '距离',
    distanceRanking: '到主序列的距离',
    external: '外部',
    flipDependencyDirection: '切换依赖方向',
    fromComponents: '来源组件',
    generatedReport: '已生成报告',
    globalComponentGraph: '全局组件图',
    graphLayout: '布局',
    graphLayoutCircle: '圆形',
    graphLayoutGrid: '网格',
    graphLayoutLayers: '分层',
    graphComponents: '图组件',
    graphFullscreenOpen: '全屏打开图',
    graphFullscreenClose: '退出全屏',
    graphZoomIn: '放大图',
    graphZoomOut: '缩小图',
    graphViewportReset: '重置图视图',
    incoming: '传入',
    incomingDependencies: '传入依赖',
    in: '入',
    instability: '不稳定度',
    internal: '内部',
    issues: '问题',
    loadingReport: '正在加载报告',
    name: '名称',
    noComponents: '没有组件',
    notApplicable: '不适用',
    unknown: '未知',
    no: '否',
    noActiveIssues: '当前筛选没有活跃架构问题',
    noComponentData: '没有组件数据',
    noComponentsSelected: '未选择',
    noDependencies: '没有依赖',
    noDependencyDistribution: '没有依赖分布',
    noExternalDependencies: '没有外部组件依赖',
    noSelection: '概览',
    noMatchingDependencies: '没有匹配当前筛选的依赖',
    noMatchingUnits: '没有匹配当前筛选的单元',
    out: '出',
    outgoing: '传出',
    outgoingDependencies: '传出依赖',
    path: '路径',
    primitive: '原始度',
    privateApi: '私有',
    publicApi: '公开',
    readingReport: '正在读取 report.json...',
    reportDataUnavailable: '报告数据不可用',
    reportJsonNotFound: 'index.html 旁边没有找到 report.json。',
    reportLanguage: '报告语言',
    reportView: '报告视图',
    rootReport: '系统',
    searchPlaceholder: '搜索组件、单元、路径或依赖',
    searchResults: '搜索结果',
    noSearchResults: '没有匹配项',
    selectAll: '全选',
    selectedUnit: '选中的单元',
    sourceFirst: '谁 -> 依赖谁',
    targetFirst: '依赖谁 <- 谁',
    toComponents: '目标组件',
    type: '类型',
    unitDependencyGraphLabel: '选中单元的依赖图',
    unitResults: '单元',
    dependencyResults: '依赖',
    violationResults: '问题',
    unitDependenciesSummary: '{count} 个单元依赖',
    unitsCount: '单元',
    units: '单元',
    violations: '违规',
    yes: '是',
  },
};

function App() {
  const [embeddedReport] = useState(() => readEmbeddedReport());
  const [suite] = useState(() => readEmbeddedSuite(embeddedReport));
  const [activeReportId, setActiveReportId] = useState(() => normalizeSuiteReportId(suite, parseNavigationHash().reportId));
  const activeSuiteNode = useMemo(
    () => suite ? findSuiteNode(suite.tree, activeReportId) ?? suite.tree : null,
    [activeReportId, suite],
  );
  const [initialReport] = useState(() => activeSuiteNode?.report ?? embeddedReport);
  const [report, setReport] = useState(() => initialReport ?? fallbackReport);
  const [loadingState, setLoadingState] = useState(initialReport ? 'ready' : 'loading');
  const [query, setQuery] = useState('');
  const [selectedComponentId, setSelectedComponentId] = useState(null);
  const [selectedUnitId, setSelectedUnitId] = useState(null);
  const [view, setView] = useState('violations');
  const [componentGraphScope, setComponentGraphScope] = useState('all');
  const [dependencyDirection, setDependencyDirection] = useState('source');
  const [dependencyStatus, setDependencyStatus] = useState('all');
  const [sourceComponentIds, setSourceComponentIds] = useState([]);
  const [targetComponentIds, setTargetComponentIds] = useState([]);
  const [graphComponentIds, setGraphComponentIds] = useState(null);
  const [internalGraphComponentIds, setInternalGraphComponentIds] = useState(null);
  const [locale, setLocale] = useState(() => localStorage.getItem('phpca-report-locale') || 'ru');
  const [searchOpen, setSearchOpen] = useState(false);
  const [activeSearchIndex, setActiveSearchIndex] = useState(0);
  const [scrollTarget, setScrollTarget] = useState(null);
  const navigationReady = useRef(false);
  const applyingNavigation = useRef(false);
  const lastNavigationHash = useRef(null);
  const pendingNavigationState = useRef(null);
  const keepManualDependencyFilters = useRef(false);
  const t = useMemo(() => (key) => dictionaries[locale]?.[key] ?? dictionaries.en[key] ?? key, [locale]);

  useEffect(() => {
    localStorage.setItem('phpca-report-locale', locale);
    document.documentElement.lang = localeToIntl[locale];
  }, [locale]);

  useEffect(() => {
    if (activeSuiteNode?.report) {
      setReport(activeSuiteNode.report);
      setSelectedComponentId(null);
      setSelectedUnitId(null);
      setQuery('');
      setView('violations');
      setLoadingState('ready');
      return;
    }

    if (initialReport) {
      setReport(initialReport);
      return;
    }

    fetch('./report.json', { cache: 'no-store' })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Report data request failed with ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        setReport(data);
        setSelectedComponentId(null);
        setSelectedUnitId(null);
        setLoadingState('ready');
      })
      .catch(() => setLoadingState('failed'));
  }, [activeSuiteNode, initialReport]);

  const indexed = useMemo(() => buildIndex(report), [report]);
  const sourceComponentOptions = useMemo(() => [...report.components].sort((left, right) => left.name.localeCompare(right.name)), [report.components]);
  const targetComponentOptions = useMemo(() => buildDependencyComponentOptions(report, indexed), [indexed, report]);
  const sourceComponentIdsAll = useMemo(
    () => sourceComponentOptions.map((component) => component.id),
    [sourceComponentOptions],
  );
  const targetComponentIdsAll = useMemo(
    () => targetComponentOptions.map((component) => component.id),
    [targetComponentOptions],
  );
  const graphComponentIdsAll = useMemo(
    () => targetComponentOptions.map((component) => component.id),
    [targetComponentOptions],
  );
  const selectedGraphComponentIds = graphComponentIds ?? graphComponentIdsAll;
  const selectedComponent = indexed.componentsById.get(selectedComponentId) ?? null;
  const selectedUnit = indexed.unitsById.get(selectedUnitId) ?? null;
  const activeSuitePath = useMemo(
    () => suite ? findSuitePath(suite.tree, activeReportId) ?? [] : [],
    [activeReportId, suite],
  );
  const parentSuiteNode = activeSuitePath.length > 1 ? activeSuitePath[activeSuitePath.length - 2] : null;
  const childSuiteNodesByTitle = useMemo(
    () => new Map((activeSuiteNode?.children ?? []).map((child) => [child.title, child])),
    [activeSuiteNode],
  );
  const selectedComponentChildSuiteNode = selectedComponent ? childSuiteNodesByTitle.get(selectedComponent.name) ?? null : null;
  const selectedComponentChildReport = selectedComponentChildSuiteNode?.report ?? null;
  const selectedComponentChildIndexed = useMemo(
    () => selectedComponentChildReport ? buildIndex(selectedComponentChildReport) : null,
    [selectedComponentChildReport],
  );
  const selectedComponentChildGraphComponents = useMemo(
    () => selectedComponentChildReport && selectedComponentChildIndexed
      ? buildDependencyComponentOptions(selectedComponentChildReport, selectedComponentChildIndexed)
      : [],
    [selectedComponentChildIndexed, selectedComponentChildReport],
  );
  const selectedComponentChildGraphComponentIdsAll = useMemo(
    () => selectedComponentChildGraphComponents.map((component) => component.id),
    [selectedComponentChildGraphComponents],
  );
  const selectedComponentChildGraphComponentIds = internalGraphComponentIds ?? selectedComponentChildGraphComponentIdsAll;
  const selectedComponentUnits = useMemo(
    () => report.units.filter((unit) => !selectedComponent || unit.componentId === selectedComponent.id),
    [report.units, selectedComponent],
  );
  const unitsForCurrentSearch = query ? report.units : selectedComponentUnits;
  const visibleUnits = useMemo(
    () => unitsForCurrentSearch.filter((unit) => matchesUnit(unit, query)),
    [query, unitsForCurrentSearch],
  );
  const violationsForCurrentSearch = query
    ? report.violations
    : report.violations.filter((violation) => !selectedComponent || violation.fromComponentId === selectedComponent.id);
  const visibleViolations = useMemo(
    () => violationsForCurrentSearch.filter((violation) => matchesViolation(violation, indexed, query)),
    [indexed, query, violationsForCurrentSearch],
  );
  const visibleDependencies = useMemo(
    () => filterDependencies(
      report.dependencies,
      indexed,
      query,
      sourceComponentIds,
      targetComponentIds,
      dependencyStatus,
      selectedComponentId,
      componentGraphScope,
    ),
    [componentGraphScope, dependencyStatus, indexed, query, report.dependencies, selectedComponentId, sourceComponentIds, targetComponentIds],
  );
  const visibleDependencyLinks = useMemo(
    () => countComponentDependencyLinks(visibleDependencies),
    [visibleDependencies],
  );
  const searchSuggestions = useMemo(
    () => buildSearchSuggestions(query, report, indexed, t),
    [indexed, query, report, t],
  );
  const flatSearchSuggestions = useMemo(
    () => searchSuggestions.flatMap((group) => group.items),
    [searchSuggestions],
  );

  useEffect(() => {
    setActiveSearchIndex(0);
  }, [query]);

  useEffect(() => {
    if (!scrollTarget || scrollTarget.type === 'dependency') {
      return undefined;
    }

    const timer = window.setTimeout(() => {
      document
        .querySelector(`[data-search-target="${escapeCssAttribute(scrollTarget.id)}"]`)
        ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 80);

    return () => window.clearTimeout(timer);
  }, [scrollTarget, view, visibleUnits, visibleViolations]);

  useEffect(() => {
    if (!selectedUnit) {
      return;
    }

    if (selectedUnit.componentId !== selectedComponent?.id || !matchesUnit(selectedUnit, query)) {
      setSelectedUnitId(null);
    }
  }, [query, selectedComponent?.id, selectedUnit]);

  useEffect(() => {
    if (keepManualDependencyFilters.current) {
      keepManualDependencyFilters.current = false;
      return;
    }

    if (!sourceComponentIdsAll.length || !targetComponentIdsAll.length) {
      setSourceComponentIds([]);
      setTargetComponentIds([]);
      return;
    }

    if (selectedComponentId && sourceComponentIdsAll.includes(selectedComponentId)) {
      if (componentGraphScope === 'outgoing') {
        setSourceComponentIds([selectedComponentId]);
        setTargetComponentIds(targetComponentIdsAll.filter((componentId) => componentId !== selectedComponentId));
        return;
      }

      if (componentGraphScope === 'incoming') {
        setSourceComponentIds(sourceComponentIdsAll.filter((componentId) => componentId !== selectedComponentId));
        setTargetComponentIds([selectedComponentId]);
        return;
      }

      setSourceComponentIds(sourceComponentIdsAll);
      setTargetComponentIds(targetComponentIdsAll);
      return;
    }

    setSourceComponentIds(sourceComponentIdsAll);
    setTargetComponentIds(targetComponentIdsAll);
  }, [componentGraphScope, sourceComponentIdsAll, targetComponentIdsAll, selectedComponentId]);

  useEffect(() => {
    setGraphComponentIds(null);
  }, [report]);

  useEffect(() => {
    setInternalGraphComponentIds(null);
  }, [selectedComponentChildReport]);

  useEffect(() => {
    if (loadingState !== 'ready' || navigationReady.current || (activeSuiteNode?.report && report !== activeSuiteNode.report)) {
      return;
    }

    const navigationState = parseNavigationHash();
    applyNavigationState(navigationState, indexed, {
      setQuery,
      setSelectedComponentId,
      setSelectedUnitId,
      setView,
    });
    navigationReady.current = true;
    lastNavigationHash.current = window.location.hash || buildNavigationHash({
      activeReportId,
      query,
      rootReportId: suite?.rootId,
      selectedComponentId,
      selectedUnitId,
      view,
    });
  }, [activeReportId, activeSuiteNode, indexed, loadingState, query, report, selectedComponentId, selectedUnitId, suite?.rootId, view]);

  useEffect(() => {
    if (loadingState !== 'ready' || !pendingNavigationState.current || (activeSuiteNode?.report && report !== activeSuiteNode.report)) {
      return;
    }

    applyNavigationState(pendingNavigationState.current, indexed, {
      setQuery,
      setSelectedComponentId,
      setSelectedUnitId,
      setView,
    });
    pendingNavigationState.current = null;
    lastNavigationHash.current = window.location.hash;
  }, [activeSuiteNode, indexed, loadingState, report]);

  useEffect(() => {
    if (loadingState !== 'ready') {
      return undefined;
    }

    const onPopState = () => {
      const navigationState = parseNavigationHash();
      const nextReportId = normalizeSuiteReportId(suite, navigationState.reportId);
      applyingNavigation.current = true;

      if (nextReportId !== activeReportId) {
        pendingNavigationState.current = navigationState;
        setActiveReportId(nextReportId);
        return;
      }

      applyNavigationState(navigationState, indexed, {
        setQuery,
        setSelectedComponentId,
        setSelectedUnitId,
        setView,
      });
    };

    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, [activeReportId, indexed, loadingState, suite]);

  useEffect(() => {
    if (loadingState !== 'ready' || !navigationReady.current) {
      return;
    }

    const nextHash = buildNavigationHash({
      activeReportId,
      query,
      rootReportId: suite?.rootId,
      selectedComponentId,
      selectedUnitId,
      view,
    });
    if (nextHash === lastNavigationHash.current) {
      return;
    }

    if (applyingNavigation.current) {
      applyingNavigation.current = false;
      lastNavigationHash.current = nextHash;
      return;
    }

    const nextStackHash = stripNavigationQuery(nextHash);
    const previousStackHash = stripNavigationQuery(lastNavigationHash.current ?? '');
    lastNavigationHash.current = nextHash;
    if (nextStackHash === previousStackHash) {
      window.history.replaceState(null, '', nextHash);
      return;
    }
    window.history.pushState(null, '', nextHash);
  }, [activeReportId, loadingState, query, selectedComponentId, selectedUnitId, suite?.rootId, view]);

  const selectOverview = () => {
    setSelectedComponentId(null);
    setSelectedUnitId(null);
    setComponentGraphScope('all');
    setSourceComponentIds(sourceComponentIdsAll);
    setTargetComponentIds(targetComponentIdsAll);
  };
  const selectComponent = (componentId) => {
    setSelectedComponentId(componentId);
    setSelectedUnitId(null);
    setComponentGraphScope('all');
  };
  const selectReport = (reportId) => {
    pendingNavigationState.current = null;
    setSelectedComponentId(null);
    setSelectedUnitId(null);
    setQuery('');
    setView('violations');
    setComponentGraphScope('all');
    setDependencyStatus('all');
    setGraphComponentIds(null);
    setActiveReportId(reportId);
  };
  const selectNestedReportComponent = (reportId, componentId) => {
    pendingNavigationState.current = {
      componentId,
      query: '',
      reportId,
      unitId: null,
      view: 'violations',
    };
    setSelectedComponentId(null);
    setSelectedUnitId(null);
    setQuery('');
    setView('violations');
    setComponentGraphScope('all');
    setDependencyStatus('all');
    setGraphComponentIds(null);
    setActiveReportId(reportId);
  };
  const selectComponentRow = (component) => {
    selectComponent(component.id);
  };
  const changeComponentGraphScope = (scope) => {
    setComponentGraphScope(scope);
    if (!selectedComponentId) {
      return;
    }

    keepManualDependencyFilters.current = true;
    setSelectedUnitId(null);
    setDependencyStatus('all');
    setView('dependencies');

    if (scope === 'outgoing') {
      setDependencyDirection('source');
      setSourceComponentIds([selectedComponentId]);
      setTargetComponentIds(targetComponentIdsAll.filter((componentId) => componentId !== selectedComponentId));
      return;
    }

    if (scope === 'incoming') {
      setDependencyDirection('target');
      setSourceComponentIds(sourceComponentIdsAll.filter((componentId) => componentId !== selectedComponentId));
      setTargetComponentIds([selectedComponentId]);
      return;
    }

    setDependencyDirection('source');
    setSourceComponentIds(sourceComponentIdsAll);
    setTargetComponentIds(targetComponentIdsAll);
  };
  const changeSourceComponents = (componentIds) => {
    setSourceComponentIds(componentIds);
    setSelectedUnitId(null);

    if (componentIds.length === 1 && indexed.componentsById.has(componentIds[0])) {
      setTargetComponentIds(targetComponentIdsAll.filter((componentId) => componentId !== componentIds[0]));
      setSelectedComponentId(componentIds[0]);
      return;
    }

    keepManualDependencyFilters.current = true;
    setSelectedComponentId(null);
  };
  const selectUnit = (unitId) => {
    const unit = indexed.unitsById.get(unitId);
    if (unit) {
      setSelectedComponentId(unit.componentId);
    }
    setQuery('');
    setSelectedUnitId(unitId);
    setView('units');
  };
  const selectSearchResult = (result) => {
    setSearchOpen(false);

    if (result.type === 'component') {
      setSelectedComponentId(result.componentId);
      setSelectedUnitId(null);
      setScrollTarget(null);
      return;
    }

    if (result.type === 'unit') {
      setSelectedComponentId(result.componentId);
      setSelectedUnitId(result.unitId);
      setView('units');
      setScrollTarget({ type: 'unit', id: result.unitId });
      return;
    }

    if (result.type === 'violation') {
      setSelectedComponentId(result.componentId);
      setSelectedUnitId(null);
      setView('violations');
      setScrollTarget({ type: 'violation', id: result.violationId });
      return;
    }

    if (result.type === 'dependency') {
      keepManualDependencyFilters.current = true;
      setSelectedComponentId(result.fromComponentId);
      setSelectedUnitId(null);
      setSourceComponentIds([result.fromComponentId]);
      setTargetComponentIds([result.toComponentId]);
      setDependencyStatus('all');
      setView('dependencies');
      setScrollTarget({ type: 'dependency', id: result.dependencyId });
    }
  };
  const handleSearchKeyDown = (event) => {
    if (!flatSearchSuggestions.length) {
      return;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setSearchOpen(true);
      setActiveSearchIndex((index) => (index + 1) % flatSearchSuggestions.length);
      return;
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      setSearchOpen(true);
      setActiveSearchIndex((index) => (index - 1 + flatSearchSuggestions.length) % flatSearchSuggestions.length);
      return;
    }

    if (event.key === 'Enter' && searchOpen) {
      event.preventDefault();
      selectSearchResult(flatSearchSuggestions[activeSearchIndex]);
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      setSearchOpen(false);
    }
  };

  if (loadingState === 'loading') {
    return <ShellMessage title={t('loadingReport')} text={t('readingReport')} />;
  }

  if (loadingState === 'failed') {
    return <ShellMessage title={t('reportDataUnavailable')} text={t('reportJsonNotFound')} />;
  }

  return (
    <main className="app-shell">
      <aside className="sidebar">
        <div className="brand">
          <GitBranch size={24} />
          <div>
            <strong>PHPCA</strong>
            <span>{t('architectureReport')}</span>
          </div>
          <LocaleSwitcher locale={locale} onChange={setLocale} label={t('reportLanguage')} />
        </div>
        <LegacyProgressPanel compact sidebar legacy={report.summary.legacy} t={t} />
        <SummaryGrid summary={report.summary} t={t} />
        <div className="component-list">
          {parentSuiteNode && (
            <button
              className="component-row overview-row"
              onClick={() => selectReport(parentSuiteNode.id)}
              type="button"
            >
              <GitBranch size={16} />
              <span>{parentSuiteNode.id === suite?.rootId ? t('rootReport') : parentSuiteNode.title}</span>
              <ChevronRight className="component-row-action reverse" size={15} />
            </button>
          )}
          <button
            className={!selectedComponent ? 'component-row selected overview-row' : 'component-row overview-row'}
            onClick={selectOverview}
            type="button"
          >
            <GitBranch size={16} />
            <span>{t('noSelection')}</span>
            <SidebarMetrics
              metrics={[
                [<Layers3 size={13} />, report.summary.components, t('components')],
                [<ArrowRight size={13} />, report.summary.dependencies, t('dependencyRows')],
                [<ShieldAlert size={13} />, report.summary.activeViolations, t('activeIssues')],
              ]}
            />
          </button>
          {report.components.map((component) => {
            const violations = indexed.violationsByComponent.get(component.id) ?? [];
            const activeViolations = violations.filter((violation) => violation.status === 'active').length;
            const childSuiteNode = childSuiteNodesByTitle.get(component.name);

            return (
              <button
                className={[
                  component.id === selectedComponent?.id ? 'component-row selected' : 'component-row',
                  childSuiteNode ? 'has-sub-report' : '',
                ].filter(Boolean).join(' ')}
                key={component.id}
                onClick={() => selectComponentRow(component)}
                type="button"
              >
                <ComponentStatus component={component} violations={violations} />
                <span>{component.name}</span>
                <SidebarMetrics
                  metrics={[
                    [<FileCode2 size={13} />, component.metrics.units, t('units')],
                    [<CheckCircle2 size={13} />, formatPercent(component.legacy?.modernRate), t('modernized')],
                    [<ArrowRight size={13} />, component.metrics.outgoingComponents, t('componentDependsOn')],
                    [<ShieldAlert size={13} />, activeViolations, t('activeIssues')],
                  ]}
                />
              </button>
            );
          })}
        </div>
      </aside>

      <section className="workspace">
        <header className="toolbar">
          <div className="title-block">
            <span>{formatDate(report.generatedAt, locale)}</span>
            <h1>{selectedComponent?.name ?? t('overview')}</h1>
          </div>
          <div className="toolbar-actions">
            <div className="search-shell">
              <label className="search-box">
                <Search size={16} />
                <input
                  onChange={(event) => {
                    setQuery(event.target.value);
                    setSearchOpen(true);
                  }}
                  onFocus={() => setSearchOpen(true)}
                  onKeyDown={handleSearchKeyDown}
                  placeholder={t('searchPlaceholder')}
                  aria-label={t('openSearch')}
                  aria-expanded={searchOpen && Boolean(query)}
                  role="searchbox"
                  type="text"
                  value={query}
                />
                {query && (
                  <button aria-label={t('clearSearch')} onClick={() => {
                    setQuery('');
                    setSearchOpen(false);
                  }} type="button">
                    <X size={16} />
                  </button>
                )}
              </label>
              {query && searchOpen && (
                <SearchSuggestions
                  activeIndex={activeSearchIndex}
                  groups={searchSuggestions}
                  onHover={setActiveSearchIndex}
                  onSelect={selectSearchResult}
                  query={query}
                  t={t}
                />
              )}
            </div>
          </div>
        </header>

        <section className={selectedComponent ? 'overview-grid' : 'overview-grid global-overview'} aria-label={t('overview')}>
          {selectedComponent && <MetricPanel component={selectedComponent} t={t} />}
          <AIMatrix components={report.components} selectedComponentId={selectedComponent?.id} onSelectComponent={selectComponent} t={t} />
          <DistanceRanking components={report.components} selectedComponentId={selectedComponent?.id} onSelectComponent={selectComponent} t={t} />
          {selectedComponent && <FanPanel component={selectedComponent} indexed={indexed} onSelectComponent={selectComponent} t={t} />}
        </section>

        <details className="component-map" open>
          <summary>
            <GitBranch size={16} />
            <span>{t('dependencyOverview')}</span>
          </summary>
          <ComponentGraphPanel
            component={selectedComponent}
            graphComponentIds={selectedGraphComponentIds}
            graphComponents={targetComponentOptions}
            indexed={indexed}
            onGraphComponentsChange={setGraphComponentIds}
            onScopeChange={changeComponentGraphScope}
            onSelectComponent={selectComponent}
            report={report}
            scope={componentGraphScope}
            t={t}
            title={selectedComponent ? t('parentComponentRelations') : t('globalComponentGraph')}
          />
        </details>

        {selectedComponentChildSuiteNode && selectedComponentChildReport && selectedComponentChildIndexed && (
          <details className="component-map component-internals-map" open>
            <summary>
              <Layers3 size={16} />
              <span>{t('componentInternals')}</span>
            </summary>
            <div className="sub-report-intro">
              <span>{t('componentInternalsHint')}</span>
              <button onClick={() => selectReport(selectedComponentChildSuiteNode.id)} type="button">
                <ChevronRight size={15} />
                {t('openNestedReport')}
              </button>
            </div>
            <ComponentGraphPanel
              component={null}
              graphComponentIds={selectedComponentChildGraphComponentIds}
              graphComponents={selectedComponentChildGraphComponents}
              indexed={selectedComponentChildIndexed}
              onGraphComponentsChange={setInternalGraphComponentIds}
              onScopeChange={() => {}}
              onSelectComponent={(componentId) => selectNestedReportComponent(selectedComponentChildSuiteNode.id, componentId)}
              report={selectedComponentChildReport}
              scope="all"
              t={t}
              title={t('componentInternalsGraph')}
            />
          </details>
        )}

        <section className={view === 'units' ? 'workbench' : 'workbench single'}>
          <div className="workbench-main">
            <nav className="tabs" aria-label={t('reportView')}>
              <button className={view === 'violations' ? 'active' : ''} onClick={() => setView('violations')} type="button">
                <ShieldAlert size={16} />
                {t('violations')}
                <strong>{visibleViolations.length}</strong>
              </button>
              <button className={view === 'dependencies' ? 'active' : ''} onClick={() => setView('dependencies')} type="button">
                <ArrowRight size={16} />
                {t('dependencies')}
                <span className="tab-metrics">
                  <strong title={t('dependencyGroups')}>{t('componentLinksShort')}: {visibleDependencyLinks}</strong>
                  <strong title={t('dependencyRows')}>{t('dependencyUnitsShort')}: {visibleDependencies.length}</strong>
                </span>
              </button>
              <button className={view === 'units' ? 'active' : ''} onClick={() => setView('units')} type="button">
                <FileCode2 size={16} />
                {t('units')}
                <strong>{visibleUnits.length}</strong>
              </button>
            </nav>

            {view === 'violations' && (
              <ViolationsTable
                focusedViolationId={scrollTarget?.type === 'violation' ? scrollTarget.id : null}
                indexed={indexed}
                onSelectUnit={selectUnit}
                t={t}
                violations={visibleViolations}
              />
            )}
            {view === 'units' && (
              <UnitsTable
                focusedUnitId={scrollTarget?.type === 'unit' ? scrollTarget.id : null}
                onSelectUnit={selectUnit}
                selectedUnitId={selectedUnit?.id}
                t={t}
                units={visibleUnits}
              />
            )}
            {view === 'dependencies' && (
              <DependencyExplorer
                dependencies={report.dependencies}
                direction={dependencyDirection}
                focusedDependencyId={scrollTarget?.type === 'dependency' ? scrollTarget.id : null}
                indexed={indexed}
                onDirectionChange={setDependencyDirection}
                onSelectUnit={selectUnit}
                onSourceComponentsChange={changeSourceComponents}
                onStatusChange={setDependencyStatus}
                onTargetComponentsChange={setTargetComponentIds}
                query={query}
                relationComponentId={selectedComponentId}
                relationScope={componentGraphScope}
                sourceComponents={sourceComponentOptions}
                sourceComponentIds={sourceComponentIds}
                status={dependencyStatus}
                t={t}
                targetComponents={targetComponentOptions}
                targetComponentIds={targetComponentIds}
              />
            )}
          </div>
          {view === 'units' && (
            <aside className="unit-inspector">
              <UnitDetail unit={selectedUnit} indexed={indexed} onSelectUnit={selectUnit} t={t} />
            </aside>
          )}
        </section>
      </section>
    </main>
  );
}

function LocaleSwitcher({ locale, onChange, label }) {
  return (
    <div className="locale-switcher" role="group" aria-label={label}>
      {locales.map((item) => (
        <button
          className={item.code === locale ? 'active' : ''}
          key={item.code}
          onClick={() => onChange(item.code)}
          type="button"
        >
          {item.label}
        </button>
      ))}
    </div>
  );
}

function SummaryGrid({ summary, t }) {
  const items = [
    [t('components'), summary.components, <Layers3 size={18} />],
    [t('units'), summary.units, <FileCode2 size={18} />],
    [t('dependencies'), summary.dependencies, <ArrowRight size={18} />],
    [t('activeIssues'), summary.activeViolations, <ShieldAlert size={18} />],
  ];

  return (
    <div className="summary-grid">
      {items.map(([label, value, icon]) => (
        <div className="summary-item" key={label}>
          <div className="summary-label">
            {icon}
            <span>{label}</span>
          </div>
          <strong>{value}</strong>
        </div>
      ))}
    </div>
  );
}

function MetricPanel({ component, t }) {
  if (!component) {
    return <section className="panel">{t('noComponentData')}</section>;
  }

  const metrics = [
    [t('abstractness'), component.metrics.abstractness, componentMetricQuality(component, 'abstractness')],
    [t('instability'), component.metrics.instability, componentMetricQuality(component, 'instability')],
    [t('distance'), component.metrics.distance, componentMetricQuality(component, 'distance')],
    [t('primitive'), component.metrics.primitiveness, componentMetricQuality(component, 'primitiveness')],
  ];

  return (
    <section className="panel metrics-panel">
      <h2>{t('componentMetrics')}</h2>
      {metrics.map(([label, value, quality]) => (
        <div className="metric" key={label}>
          <div>
            <span>{label}</span>
            <strong>{formatRate(value)}</strong>
          </div>
          <ProgressMeter value={value} quality={quality} />
        </div>
      ))}
    </section>
  );
}

function LegacyProgressPanel({ compact = false, framed = false, sidebar = false, legacy, t }) {
  const metrics = legacyMetrics(legacy);
  const className = ['legacy-progress', compact ? 'compact' : '', framed ? 'panel' : '', sidebar ? 'sidebar-progress' : '']
    .filter(Boolean)
    .join(' ');

  return (
    <section className={className}>
      {!compact && <h2>{t('modernizationProgress')}</h2>}
      <div className="legacy-progress-heading">
        <span>{t('modernized')}</span>
        <strong>{formatPercent(metrics.modernRate)}</strong>
      </div>
      <div
        aria-label={`${t('modernized')}: ${formatPercent(metrics.modernRate)}`}
        className="legacy-meter"
        role="img"
        style={{
          '--modern-width': `${Math.max(metrics.modernRate * 100, metrics.modernLinesOfCode > 0 ? 2 : 0)}%`,
        }}
      >
        <i />
      </div>
      <div className="legacy-split">
        <div>
          <em>{t('modernCode')}</em>
          {compact ? (
            <span>
              <b>{formatInteger(metrics.modernLinesOfCode)}</b>
              {' LoC · '}
              <b>{formatInteger(metrics.modernUnits)}</b>
              {' UoC'}
            </span>
          ) : (
            <>
              <span>
                <b>{formatInteger(metrics.modernLinesOfCode)}</b>
                {' '}
                {t('modernLines')}
              </span>
              <span>
                <b>{formatInteger(metrics.modernUnits)}</b>
                {' '}
                {t('modernUnits')}
              </span>
            </>
          )}
        </div>
        <div>
          <em>{t('legacyCode')}</em>
          {compact ? (
            <span>
              <b>{formatInteger(metrics.legacyLinesOfCode)}</b>
              {' LoC · '}
              <b>{formatInteger(metrics.legacyUnits)}</b>
              {' UoC'}
            </span>
          ) : (
            <>
              <span>
                <b>{formatInteger(metrics.legacyLinesOfCode)}</b>
                {' '}
                {t('legacyLines')}
              </span>
              <span>
                <b>{formatInteger(metrics.legacyUnits)}</b>
                {' '}
                {t('legacyUnits')}
              </span>
            </>
          )}
        </div>
      </div>
    </section>
  );
}

function ProgressMeter({ value, quality }) {
  const clampedValue = clamp01(value);
  const clampedQuality = clamp01(quality);

  return (
    <div
      className="metric-meter"
      style={{
        '--metric-color': qualityColor(clampedQuality),
        '--metric-width': `${Math.max(clampedValue * 100, 2)}%`,
      }}
    >
      <i />
    </div>
  );
}

function AIMatrix({ components, selectedComponentId, onSelectComponent, t }) {
  const [hoveredComponentId, setHoveredComponentId] = useState(null);
  const chart = {
    bottom: 210,
    height: 196,
    left: 28,
    right: 344,
    top: 14,
    width: 316,
  };
  const labeledComponentIds = useMemo(() => {
    const ids = new Set(
      [...components]
        .sort((left, right) => right.metrics.distance - left.metrics.distance)
        .slice(0, 3)
        .map((component) => component.id),
    );
    if (selectedComponentId) {
      ids.add(selectedComponentId);
    }
    if (hoveredComponentId) {
      ids.add(hoveredComponentId);
    }

    return ids;
  }, [components, hoveredComponentId, selectedComponentId]);

  return (
    <section className="panel chart-panel ai-matrix-panel">
      <h2>{t('aiMatrix')}</h2>
      <svg viewBox="0 0 360 240" role="img" aria-label={t('aiMatrix')}>
        <path className="chart-zone pain" d={`M${chart.left} ${chart.bottom} L${chart.left} ${chart.bottom - 88} A88 88 0 0 1 ${chart.left + 88} ${chart.bottom} Z`} />
        <path className="chart-zone uselessness" d={`M${chart.right} ${chart.top} L${chart.right} ${chart.top + 88} A88 88 0 0 1 ${chart.right - 88} ${chart.top} Z`} />
        <text className="zone-label pain" textAnchor="middle" transform={`translate(${chart.left + 62} ${chart.bottom - 18}) rotate(45)`}>{t('zoneOfPain')}</text>
        <text className="zone-label uselessness" textAnchor="middle" transform={`translate(${chart.right - 44} ${chart.top + 48}) rotate(45)`}>{t('zoneOfUselessness')}</text>
        <line className="chart-grid strong" x1={chart.left} x2={chart.right} y1={chart.bottom} y2={chart.bottom} />
        <line className="chart-grid strong" x1={chart.left} x2={chart.left} y1={chart.top} y2={chart.bottom} />
        {[0.25, 0.5, 0.75, 1].map((value) => (
          <g key={value}>
            <line className="chart-grid" x1={chart.left + value * chart.width} x2={chart.left + value * chart.width} y1={chart.top} y2={chart.bottom} />
            <line className="chart-grid" x1={chart.left} x2={chart.right} y1={chart.bottom - value * chart.height} y2={chart.bottom - value * chart.height} />
          </g>
        ))}
        <line className="main-sequence" x1={chart.left} x2={chart.right} y1={chart.top} y2={chart.bottom} />
        {components.map((component, index) => {
          const x = chart.left + component.metrics.instability * chart.width;
          const y = chart.bottom - component.metrics.abstractness * chart.height;
          const labelX = x > 282 ? x - 10 : x + 10;
          const labelY = y + ((index % 3) - 1) * 12;
          const shouldShowLabel = labeledComponentIds.has(component.id);
          return (
            <g
              className={component.id === selectedComponentId ? 'chart-point selected' : 'chart-point'}
              key={component.id}
              onClick={() => onSelectComponent(component.id)}
              onBlur={() => setHoveredComponentId(null)}
              onFocus={() => setHoveredComponentId(component.id)}
              onMouseEnter={() => setHoveredComponentId(component.id)}
              onMouseLeave={() => setHoveredComponentId(null)}
              role="button"
              tabIndex="0"
            >
              <title>{`${component.name}: A ${formatRate(component.metrics.abstractness)}, I ${formatRate(component.metrics.instability)}, D ${formatRate(component.metrics.distance)}`}</title>
              <circle cx={x} cy={y} r={component.id === selectedComponentId ? 9 : 7} />
              {shouldShowLabel && (
                <text textAnchor={x > 282 ? 'end' : 'start'} x={labelX} y={labelY}>{truncate(component.name, 16)}</text>
              )}
            </g>
          );
        })}
        <text className="axis-label" x="178" y="234">{t('instability')}</text>
        <text className="axis-label vertical" x="-146" y="12">{t('abstractness')}</text>
      </svg>
    </section>
  );
}

function DistanceRanking({ components, selectedComponentId, onSelectComponent, t }) {
  const sortedComponents = [...components].sort((left, right) => right.metrics.distance - left.metrics.distance);

  return (
    <section className="panel chart-panel ranking-panel">
      <h2>{t('distanceRanking')}</h2>
      <div className="ranking-list">
        {sortedComponents.map((component) => (
          <button
            className={component.id === selectedComponentId ? 'ranking-row selected' : 'ranking-row'}
            key={component.id}
            onClick={() => onSelectComponent(component.id)}
            style={{ '--ranking-color': qualityColor(componentMetricQuality(component, 'distance')) }}
            type="button"
          >
            <span>{component.name}</span>
            <strong>{formatRate(component.metrics.distance)}</strong>
            <i style={{ width: `${Math.max(component.metrics.distance * 100, 2)}%` }} />
          </button>
        ))}
      </div>
    </section>
  );
}

function ComponentGraphPanel({
  component,
  graphComponentIds,
  graphComponents,
  indexed,
  onGraphComponentsChange,
  onScopeChange,
  onSelectComponent,
  report,
  scope,
  t,
  title,
}) {
  const [graphLayout, setGraphLayout] = useState(() => readGraphLayout(report));
  const [isFullscreen, setIsFullscreen] = useState(false);
  const [isGraphComponentFilterOpen, setIsGraphComponentFilterOpen] = useState(false);
  const graph = useMemo(
    () => componentGraph(component, report, indexed, scope, graphLayout, graphComponentIds),
    [component, graphComponentIds, graphLayout, indexed, report, scope],
  );
  const graphNodesKey = useMemo(() => graph.nodes.map((node) => node.id).sort().join('|'), [graph.nodes]);
  const graphKey = `${graphLayout}:${graphNodesKey}`;
  const [nodePositions, setNodePositions] = useState(() => readGraphPositions(report, graphKey));
  const [graphViewport, setGraphViewport] = useState(() => readGraphViewport(report, graphKey));
  const svgRef = useRef(null);
  const dragState = useRef(null);
  const suppressNodeClick = useRef(false);

  useEffect(() => {
    setNodePositions(readGraphPositions(report, graphKey));
    setGraphViewport(readGraphViewport(report, graphKey));
  }, [graphKey, report]);

  useEffect(() => {
    saveGraphLayout(report, graphLayout);
  }, [graphLayout, report]);

  const zoomGraphAtPoint = (pointer, factor) => {
    setGraphViewport((viewport) => {
      const nextScale = clamp(viewport.scale * factor, 0.35, 3);
      const ratio = nextScale / viewport.scale;

      return {
        x: pointer.x - (pointer.x - viewport.x) * ratio,
        y: pointer.y - (pointer.y - viewport.y) * ratio,
        scale: nextScale,
      };
    });
  };

  useEffect(() => {
    const svg = svgRef.current;
    if (!svg) {
      return undefined;
    }

    const preventPageScrollOnZoom = (event) => {
      event.preventDefault();
      zoomGraphAtPoint(svgPoint(svg, event), event.deltaY < 0 ? 1.12 : 1 / 1.12);
    };

    svg.addEventListener('wheel', preventPageScrollOnZoom, { passive: false });

    return () => {
      svg.removeEventListener('wheel', preventPageScrollOnZoom);
    };
  }, []);

  useEffect(() => {
    if (!isFullscreen) {
      return undefined;
    }

    const closeOnEscape = (event) => {
      if (event.key === 'Escape') {
        setIsFullscreen(false);
      }
    };
    document.body.classList.add('graph-fullscreen-open');
    window.addEventListener('keydown', closeOnEscape);

    return () => {
      document.body.classList.remove('graph-fullscreen-open');
      window.removeEventListener('keydown', closeOnEscape);
    };
  }, [isFullscreen]);

  useEffect(() => {
    const moveWindowDrag = (event) => {
      if (!dragState.current) {
        return;
      }

      const state = dragState.current;
      const deltaX = (event.clientX - state.originClientX) * state.svgUnitsPerClientX;
      const deltaY = (event.clientY - state.originClientY) * state.svgUnitsPerClientY;
      state.hasMoved = true;

      if (state.type === 'pan') {
        setGraphViewport({
          ...graphViewport,
          x: state.originViewportX + deltaX,
          y: state.originViewportY + deltaY,
        });
        return;
      }

      const nextPosition = {
        x: state.originNodeX + deltaX / state.scale,
        y: state.originNodeY + deltaY / state.scale,
      };
      suppressNodeClick.current = true;
      setNodePositions((positions) => {
        const nextPositions = { ...positions, [state.nodeId]: nextPosition };
        saveGraphPositions(report, graphKey, nextPositions);

        return nextPositions;
      });
    };

    const endWindowDrag = () => {
      if (!dragState.current) {
        return;
      }

      suppressNodeClick.current = dragState.current.type === 'node' && dragState.current.hasMoved;
      dragState.current = null;
      saveGraphViewport(report, graphKey, graphViewport);
    };

    window.addEventListener('pointermove', moveWindowDrag);
    window.addEventListener('pointerup', endWindowDrag);
    window.addEventListener('pointercancel', endWindowDrag);

    return () => {
      window.removeEventListener('pointermove', moveWindowDrag);
      window.removeEventListener('pointerup', endWindowDrag);
      window.removeEventListener('pointercancel', endWindowDrag);
    };
  }, [graphKey, graphViewport, report]);

  const positionedGraph = useMemo(() => ({
    nodes: graph.nodes.map((node) => nodePositions[node.id] ? { ...node, ...nodePositions[node.id] } : node),
    edges: graph.edges,
  }), [graph, nodePositions]);
  const positionedNodes = useMemo(() => new Map(positionedGraph.nodes.map((node) => [node.id, node])), [positionedGraph.nodes]);
  const positionedEdges = useMemo(() => positionedGraph.edges.map((edge) => ({
    ...edge,
    from: positionedNodes.get(edge.fromComponentId) ?? edge.from,
    to: positionedNodes.get(edge.toComponentId) ?? edge.to,
  })), [positionedGraph.edges, positionedNodes]);
  const graphEdgeShapes = useMemo(() => shapeGraphEdges(positionedEdges), [positionedEdges]);

  const startDrag = (event, node) => {
    event.preventDefault();
    event.stopPropagation();
    const svg = event.currentTarget.ownerSVGElement;
    const rect = svg.getBoundingClientRect();
    dragState.current = {
      type: 'node',
      hasMoved: false,
      nodeId: node.id,
      originClientX: event.clientX,
      originClientY: event.clientY,
      originNodeX: node.x,
      originNodeY: node.y,
      scale: graphViewport.scale,
      svgUnitsPerClientX: graphWidth / rect.width,
      svgUnitsPerClientY: graphHeight / rect.height,
    };
  };

  useEffect(() => {
    if (!dragState.current) {
      saveGraphPositions(report, graphKey, nodePositions);
    }
  }, [graphKey, nodePositions, report]);

  useEffect(() => {
    if (!dragState.current) {
      saveGraphViewport(report, graphKey, graphViewport);
    }
  }, [graphKey, graphViewport, report]);

  const applyGraphLayout = (layout) => {
    const nextGraphKey = `${layout}:${graphNodesKey}`;
    removeGraphPositions(report, nextGraphKey);
    removeGraphViewport(report, nextGraphKey);
    setGraphLayout(layout);
    setNodePositions({});
    setGraphViewport(defaultGraphViewport);
  };

  const startPan = (event) => {
    if (event.button !== 0) {
      return;
    }

    event.preventDefault();
    const rect = event.currentTarget.getBoundingClientRect();
    dragState.current = {
      type: 'pan',
      hasMoved: false,
      originClientX: event.clientX,
      originClientY: event.clientY,
      originViewportX: graphViewport.x,
      originViewportY: graphViewport.y,
      svgUnitsPerClientX: graphWidth / rect.width,
      svgUnitsPerClientY: graphHeight / rect.height,
    };
  };

  const zoomGraphFromButton = (factor) => {
    zoomGraphAtPoint({ x: graphWidth / 2, y: graphHeight / 2 }, factor);
  };

  return (
    <section className={isFullscreen ? 'panel graph-panel fullscreen' : 'panel graph-panel'}>
      <header className="graph-panel-header">
        {title && <h2 className="graph-panel-title">{title}</h2>}
        <div className="graph-controls">
          <div className="graph-layout-switcher" aria-label={t('graphLayout')}>
            {graphLayouts.map((layout) => (
              <button
                className={graphLayout === layout ? 'active' : ''}
                key={layout}
                onClick={(event) => {
                  event.preventDefault();
                  event.stopPropagation();
                  applyGraphLayout(layout);
                }}
                title={t(`graphLayout${capitalize(layout)}`)}
                type="button"
              >
                {layout === 'circle' && <Repeat2 size={15} />}
                {layout === 'grid' && <Layers3 size={15} />}
                {layout === 'layers' && <GitBranch size={15} />}
                <span>{t(`graphLayout${capitalize(layout)}`)}</span>
              </button>
            ))}
          </div>
          <div className="graph-scope-switcher" aria-label={t('dependencyDirection')}>
            {[
              ['all', t('all')],
              ['outgoing', t('outgoing')],
              ['incoming', t('incoming')],
            ].map(([value, label]) => (
              <button
                className={scope === value ? 'active' : ''}
                disabled={!component && value !== 'all'}
                key={value}
                onClick={(event) => {
                  event.preventDefault();
                  event.stopPropagation();
                  onScopeChange(value);
                }}
                type="button"
              >
                {label}
              </button>
            ))}
          </div>
          {!component && (
            <ComponentFilter
              compact
              components={graphComponents}
              id="graph"
              isOpen={isGraphComponentFilterOpen}
              label={t('graphComponents')}
              onChange={onGraphComponentsChange}
              onClose={() => setIsGraphComponentFilterOpen(false)}
              onOpen={() => setIsGraphComponentFilterOpen(true)}
              selectedIds={graphComponentIds}
              t={t}
            />
          )}
          <div className="graph-zoom-controls">
            <button
              aria-label={t('graphZoomOut')}
              onClick={() => zoomGraphFromButton(1 / 1.18)}
              title={t('graphZoomOut')}
              type="button"
            >
              <ZoomOut size={15} />
            </button>
            <button
              aria-label={t('graphViewportReset')}
              onClick={() => setGraphViewport(defaultGraphViewport)}
              title={t('graphViewportReset')}
              type="button"
            >
              <RotateCcw size={15} />
            </button>
            <button
              aria-label={t('graphZoomIn')}
              onClick={() => zoomGraphFromButton(1.18)}
              title={t('graphZoomIn')}
              type="button"
            >
              <ZoomIn size={15} />
            </button>
          </div>
          <button
            className="graph-fullscreen-button"
            aria-label={isFullscreen ? t('graphFullscreenClose') : t('graphFullscreenOpen')}
            onClick={(event) => {
              event.preventDefault();
              event.stopPropagation();
              setIsFullscreen((current) => !current);
            }}
            title={isFullscreen ? t('graphFullscreenClose') : t('graphFullscreenOpen')}
            type="button"
          >
            {isFullscreen ? <Minimize2 size={16} /> : <Maximize2 size={16} />}
            <span>{isFullscreen ? t('graphFullscreenClose') : t('graphFullscreenOpen')}</span>
          </button>
        </div>
      </header>
      <svg
        ref={svgRef}
        viewBox={`0 0 ${graphWidth} ${graphHeight}`}
        role="img"
        aria-label={t('dependencyGraphLabel')}
        onPointerDown={startPan}
      >
        <defs>
          {dependencyStatuses.map((status) => (
            <marker id={`arrow-${status}`} key={status} markerHeight="8" markerWidth="8" orient="auto" refX="8" refY="4">
              <path d="M0,0 L8,4 L0,8 Z" />
            </marker>
          ))}
        </defs>
        <rect className="graph-pan-surface" width={graphWidth} height={graphHeight} />
        <g className="graph-viewport" transform={`translate(${graphViewport.x} ${graphViewport.y}) scale(${graphViewport.scale})`}>
          {graphEdgeShapes.map((shape) => (
            <g key={shape.edge.id}>
              <title>{`${shape.edge.from.name} -> ${shape.edge.to.name}: ${shape.edge.sourceUnitCount}->${shape.edge.targetUnitCount} (${shape.edge.weight} ${t('dependencyRows')}, ${edgeStatusLabel(shape.edge.status, t)})`}</title>
              <path
                className={graphEdgeClassName(shape.edge)}
                d={shape.path}
                markerEnd={`url(#arrow-${shape.edge.status})`}
              />
              <text className={shape.edge.isSelected ? 'edge-label selected' : 'edge-label'} x={shape.label.x} y={shape.label.y}>
                {shape.edge.sourceUnitCount}-&gt;{shape.edge.targetUnitCount}
              </text>
            </g>
          ))}
          {positionedGraph.nodes.map((node) => {
            const violations = indexed.violationsByComponent.get(node.id) ?? [];
            return (
              <g
                className={graphNodeClassName(node, component)}
                key={node.id}
                onClick={() => {
                  if (suppressNodeClick.current) {
                    suppressNodeClick.current = false;
                    return;
                  }
                  if (!node.isExternal) {
                    onSelectComponent(node.id);
                  }
                }}
                onPointerDown={(event) => startDrag(event, node)}
                role={node.isExternal ? undefined : 'button'}
                tabIndex={node.isExternal ? undefined : '0'}
                transform={`translate(${node.x} ${node.y})`}
              >
                <circle r="38" />
                <text textAnchor="middle" y="-3">{truncate(node.name, 14)}</text>
                <text className="node-meta" textAnchor="middle" y="15">
                  {node.isExternal ? t('external') : (violations.length ? `${violations.length} ${t('issues')}` : `${node.units} ${t('unitsCount')}`)}
                </text>
              </g>
            );
          })}
        </g>
      </svg>
    </section>
  );
}

function FanPanel({ component, indexed, onSelectComponent, t }) {
  if (!component) {
    return <section className="panel fan-panel">{t('noDependencyDistribution')}</section>;
  }

  const outgoing = indexed.outgoingComponentEdges.get(component.id) ?? [];
  const incoming = indexed.incomingComponentEdges.get(component.id) ?? [];

  return (
    <section className="panel fan-panel">
      <h2>{t('componentDependencyDistribution')}</h2>
      <FanList title={t('outgoing')} items={outgoing} direction="to" indexed={indexed} onSelectComponent={onSelectComponent} t={t} />
      <FanList title={t('incoming')} items={incoming} direction="from" indexed={indexed} onSelectComponent={onSelectComponent} t={t} />
    </section>
  );
}

function FanList({ title, items, direction, indexed, onSelectComponent, t }) {
  const max = Math.max(...items.map((item) => item.sourceUnitCount), 1);

  return (
    <div className="fan-list">
      <h3>{title}</h3>
      {items.length ? items.map((item) => {
        const componentId = direction === 'to' ? item.toComponentId : item.fromComponentId;
        const component = indexed.componentsById.get(componentId);
        const componentName = component?.name ?? (direction === 'to' ? item.toComponentName : item.fromComponentName);
        return (
          <button
            className={component ? `fan-row ${item.status}` : `fan-row disabled ${item.status}`}
            disabled={!component}
            key={item.id}
            onClick={() => component && onSelectComponent(componentId)}
            title={`${item.fromComponentName} -> ${item.toComponentName}: ${item.sourceUnitCount}->${item.targetUnitCount} (${item.weight} ${t('dependencyRows')}, ${edgeStatusLabel(item.status, t)})`}
            type="button"
          >
            <span>{componentName}</span>
            <strong>{item.sourceUnitCount}</strong>
            <i style={{ width: `${Math.max((item.sourceUnitCount / max) * 100, 4)}%` }} />
          </button>
        );
      }) : <p>{t('noExternalDependencies')}</p>}
    </div>
  );
}

function SearchSuggestions({ activeIndex, groups, onHover, onSelect, query, t }) {
  let optionIndex = 0;

  return (
    <div className="search-suggestions" role="listbox" aria-label={t('searchResults')}>
      {groups.length ? groups.map((group) => (
        <section className="search-suggestion-group" key={group.id}>
          <h3>{group.label}</h3>
          {group.items.map((item) => {
            const currentIndex = optionIndex;
            optionIndex += 1;

            return (
              <button
                className={currentIndex === activeIndex ? 'active' : ''}
                key={item.id}
                onMouseDown={(event) => {
                  event.preventDefault();
                  onSelect(item);
                }}
                onMouseEnter={() => onHover(currentIndex)}
                role="option"
                type="button"
              >
                {item.icon}
                <span>
                  <strong><SearchHighlight text={item.title} query={query} /></strong>
                  <small><SearchHighlight text={item.subtitle} query={query} /></small>
                </span>
              </button>
            );
          })}
        </section>
      )) : (
        <p>{t('noSearchResults')}</p>
      )}
    </div>
  );
}

function SearchHighlight({ text, query }) {
  const normalizedQuery = normalizeQuery(query);
  if (!normalizedQuery || !text) {
    return text;
  }

  const lowerText = text.toLowerCase();
  const matchIndex = lowerText.indexOf(normalizedQuery);
  if (matchIndex === -1) {
    return text;
  }

  const before = text.slice(0, matchIndex);
  const match = text.slice(matchIndex, matchIndex + normalizedQuery.length);
  const after = text.slice(matchIndex + normalizedQuery.length);

  return (
    <>
      {before}
      <mark>{match}</mark>
      {after}
    </>
  );
}

function ViolationsTable({ focusedViolationId, violations, indexed, onSelectUnit, t }) {
  if (!violations.length) {
    return <EmptyState icon={<CheckCircle2 size={22} />} title={t('noActiveIssues')} />;
  }

  return (
    <section className="table-shell">
      {violations.map((violation) => {
        const dependency = indexed.dependenciesById.get(violation.dependencyId);
        const status = violation.status === 'allowed-state' ? 'allowed-state' : violation.type === 'private-unit' ? 'private' : 'blocked';
        return (
          <article
            className={`issue-row ${status}${violation.id === focusedViolationId ? ' focused-search-target' : ''}`}
            data-search-target={violation.id}
            key={violation.id}
          >
            <AlertTriangle size={18} />
            <div>
              <strong>{violationMessage(violation, dependency, t)}</strong>
              <DependencyEndpoints dependency={dependency} onSelectUnit={onSelectUnit} t={t} />
            </div>
            <mark className={status}>{violation.status === 'allowed-state' ? t('allowedState') : violation.type}</mark>
          </article>
        );
      })}
    </section>
  );
}

function UnitsTable({ focusedUnitId, units, selectedUnitId, onSelectUnit, t }) {
  if (!units.length) {
    return <EmptyState icon={<FileCode2 size={22} />} title={t('noMatchingUnits')} />;
  }

  return (
    <section className="table-shell">
      <table>
        <thead>
          <tr>
            <th>{t('name')}</th>
            <th>{t('type')}</th>
            <th>{t('api')}</th>
            <th>{t('legacyCode')}</th>
            <th>{t('linesOfCode')}</th>
            <th>{t('in')}</th>
            <th>{t('out')}</th>
          </tr>
        </thead>
        <tbody>
          {units.map((unit) => (
            <tr
              className={`${unit.id === selectedUnitId ? 'selected-row' : ''}${unit.id === focusedUnitId ? ' focused-search-target' : ''}`}
              data-search-target={unit.id}
              key={unit.id}
              onClick={() => onSelectUnit(unit.id)}
            >
              <td>
                <strong>{unit.shortName}</strong>
                <span title={unit.name}>{compactNamespace(unit.name, unit.shortName)}</span>
              </td>
              <td>{unit.type}</td>
              <td>{unit.isPublic ? t('publicApi') : t('privateApi')}</td>
              <td>
                <span className={unit.isLegacy ? 'unit-legacy legacy' : 'unit-legacy modern'}>
                  {unit.isLegacy ? t('legacyCode') : t('modernCode')}
                </span>
              </td>
              <td>{formatInteger(unit.linesOfCode ?? 0)}</td>
              <td>{unit.metrics.incoming}</td>
              <td>{unit.metrics.outgoing}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}

function DependencyExplorer({
  dependencies,
  direction,
  focusedDependencyId,
  indexed,
  onDirectionChange,
  onSelectUnit,
  onSourceComponentsChange,
  onStatusChange,
  onTargetComponentsChange,
  query,
  relationComponentId,
  relationScope,
  sourceComponents,
  sourceComponentIds,
  status,
  t,
  targetComponents,
  targetComponentIds,
}) {
  const [openComponentFilter, setOpenComponentFilter] = useState(null);
  const filteredDependencies = useMemo(() => {
    const sourceIds = new Set(sourceComponentIds);
    const targetIds = new Set(targetComponentIds);

    return dependencies
      .filter((dependency) => matchesDependencyRelationScope(dependency, relationComponentId, relationScope))
      .filter((dependency) => sourceIds.has(dependency.fromComponentId))
      .filter((dependency) => targetIds.has(dependency.toComponentId))
      .filter((dependency) => status === 'all' || dependencyStatusKey(dependency) === status)
      .filter((dependency) => matchesDependencyWithUnits(dependency, indexed, query));
  }, [dependencies, indexed, query, relationComponentId, relationScope, sourceComponentIds, status, targetComponentIds]);

  const groups = useMemo(
    () => buildDependencyGroups(filteredDependencies, indexed, direction),
    [direction, filteredDependencies, indexed],
  );
  const listRef = useRef(null);
  const rowVirtualizer = useVirtualizer({
    count: groups.length,
    getScrollElement: () => listRef.current,
    estimateSize: () => 92,
    overscan: 8,
  });
  const focusedGroupIndex = useMemo(
    () => focusedDependencyId ? groups.findIndex((group) => group.dependencies.some((dependency) => dependency.id === focusedDependencyId)) : -1,
    [focusedDependencyId, groups],
  );
  const semanticSummary = useMemo(
    () => buildDependencyListSummary(filteredDependencies, direction, sourceComponentIds, targetComponentIds, t),
    [direction, filteredDependencies, sourceComponentIds, t, targetComponentIds],
  );

  useEffect(() => {
    if (focusedGroupIndex >= 0) {
      rowVirtualizer.scrollToIndex(focusedGroupIndex, { align: 'center' });
    }
  }, [focusedGroupIndex, rowVirtualizer]);

  if (!dependencies.length) {
    return <EmptyState icon={<ArrowRight size={22} />} title={t('noMatchingDependencies')} />;
  }

  return (
    <section className="dependency-explorer">
      <details className="dependency-filter-panel">
        <summary>
          <SlidersHorizontal size={16} />
          <span>{t('dependencyFilters')}</span>
          <small>{filteredDependencies.length} {t('dependencyRows')}</small>
        </summary>
        <div className="dependency-filter-grid">
          <div className="filter-block">
            <span>{t('dependencyDirection')}</span>
            <button className="flip-button" onClick={() => onDirectionChange(direction === 'source' ? 'target' : 'source')} type="button">
              <Repeat2 size={16} />
              {direction === 'source' ? t('sourceFirst') : t('targetFirst')}
            </button>
          </div>
          <SegmentedFilter
            label={t('dependencyStatus')}
            onChange={onStatusChange}
            options={[
              ['all', t('all')],
              ['blocked', t('blocked')],
              ['private', t('privateApi')],
              ['allowed-state', t('allowedState')],
              ['internal', t('internal')],
              ['allowed', t('allowed')],
            ]}
            value={status}
          />
          <ComponentFilter
            components={sourceComponents}
            id="source"
            isOpen={openComponentFilter === 'source'}
            label={t('fromComponents')}
            onChange={onSourceComponentsChange}
            onClose={() => setOpenComponentFilter((current) => (current === 'source' ? null : current))}
            onOpen={() => setOpenComponentFilter('source')}
            selectedIds={sourceComponentIds}
            t={t}
          />
          <ComponentFilter
            components={targetComponents}
            id="target"
            isOpen={openComponentFilter === 'target'}
            label={t('toComponents')}
            onChange={onTargetComponentsChange}
            onClose={() => setOpenComponentFilter((current) => (current === 'target' ? null : current))}
            onOpen={() => setOpenComponentFilter('target')}
            selectedIds={targetComponentIds}
            t={t}
          />
        </div>
      </details>

      {groups.length ? (
        <>
        <div className="dependency-list-summary">
          <strong>{semanticSummary.componentText}</strong>
          <span>·</span>
          <span>{semanticSummary.unitText}</span>
        </div>
        <div className="dependency-group-list" ref={listRef}>
          <div style={{ height: `${rowVirtualizer.getTotalSize()}px`, position: 'relative' }}>
            {rowVirtualizer.getVirtualItems().map((virtualRow) => {
              const group = groups[virtualRow.index];
              return (
                <div
                  data-index={virtualRow.index}
                  key={group.id}
                  ref={rowVirtualizer.measureElement}
                  style={{
                    left: 0,
                    position: 'absolute',
                    top: 0,
                    transform: `translateY(${virtualRow.start}px)`,
                    width: '100%',
                  }}
                >
                  <DependencyGroupCard
                    direction={direction}
                    focused={group.dependencies.some((dependency) => dependency.id === focusedDependencyId)}
                    group={group}
                    onSelectUnit={onSelectUnit}
                    t={t}
                  />
                </div>
              );
            })}
          </div>
        </div>
        </>
      ) : (
        <EmptyState icon={<ArrowRight size={22} />} title={t('noMatchingDependencies')} />
      )}
    </section>
  );
}

function SegmentedFilter({ label, options, value, onChange }) {
  return (
    <div className="filter-block">
      <span>{label}</span>
      <div className="segmented-filter">
        {options.map(([optionValue, optionLabel]) => (
          <button className={optionValue === value ? 'active' : ''} key={optionValue} onClick={() => onChange(optionValue)} type="button">
            {optionLabel}
          </button>
        ))}
      </div>
    </div>
  );
}

function ComponentFilter({ compact = false, components, id, isOpen, label, selectedIds, onChange, onClose, onOpen, t }) {
  const [componentQuery, setComponentQuery] = useState('');
  const inputRef = useRef(null);
  const menuRef = useRef(null);
  const selected = new Set(selectedIds);
  const allSelected = selectedIds.length === components.length;
  const normalizedQuery = componentQuery.trim().toLowerCase();
  const visibleComponents = normalizedQuery
    ? components.filter((component) => component.name.toLowerCase().includes(normalizedQuery))
    : components;
  const selectedComponents = components.filter((component) => selected.has(component.id));
  const summary = allSelected
    ? components.map((component) => component.name).join(', ')
    : selectedComponents.length === 0
      ? t('noComponentsSelected')
      : selectedComponents.map((component) => component.name).join(', ');

  const toggleComponent = (componentId) => {
    const next = new Set(selected);
    if (next.has(componentId)) {
      next.delete(componentId);
    } else {
      next.add(componentId);
    }
    onChange([...next]);
  };

  useEffect(() => {
    if (!isOpen) {
      return undefined;
    }

    const timer = window.setTimeout(() => inputRef.current?.focus(), 0);
    return () => window.clearTimeout(timer);
  }, [isOpen]);

  useEffect(() => {
    if (!isOpen) {
      return undefined;
    }

    const isInsideMenu = (target) => target instanceof Node && menuRef.current?.contains(target);
    const closeOnOutsideInteraction = (event) => {
      if (!isInsideMenu(event.target)) {
        onClose();
      }
    };
    const closeOnEscape = (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        onClose();
      }
    };

    document.addEventListener('pointerdown', closeOnOutsideInteraction);
    document.addEventListener('focusin', closeOnOutsideInteraction);
    document.addEventListener('keydown', closeOnEscape);
    return () => {
      document.removeEventListener('pointerdown', closeOnOutsideInteraction);
      document.removeEventListener('focusin', closeOnOutsideInteraction);
      document.removeEventListener('keydown', closeOnEscape);
    };
  }, [isOpen, onClose]);

  return (
    <div className={compact ? 'filter-block component-filter compact' : 'filter-block component-filter'}>
      {!compact && <span>{label}</span>}
      <div className="component-filter-menu" ref={menuRef}>
        <button
          className="component-filter-trigger"
          aria-label={label}
          onClick={() => {
            if (isOpen) {
              onClose();
              return;
            }
            onOpen();
          }}
          type="button"
        >
          <span title={summary}>{summary}</span>
          <small>{selectedIds.length}/{components.length}</small>
        </button>
        {isOpen && (
          <div className="component-filter-popover">
          <label className="component-filter-search">
            <Search size={14} />
            <input
              aria-label={`${label}: ${t('componentFilterSearch')}`}
              id={`component-filter-search-${id}`}
              onChange={(event) => setComponentQuery(event.target.value)}
              placeholder={t('componentFilterSearch')}
              ref={inputRef}
              type="search"
              value={componentQuery}
            />
          </label>
          <div className="component-filter-actions">
            <button onClick={() => onChange(components.map((component) => component.id))} type="button">{t('selectAll')}</button>
            <button onClick={() => onChange([])} type="button">{t('clearSelection')}</button>
          </div>
          <div className="component-filter-options">
            {visibleComponents.length ? visibleComponents.map((component) => (
              <label className={selected.has(component.id) ? 'checked' : ''} key={component.id}>
                <input checked={selected.has(component.id)} onChange={() => toggleComponent(component.id)} type="checkbox" />
                <span>{component.name}</span>
              </label>
            )) : (
              <p>{t('noComponents')}</p>
            )}
          </div>
        </div>
        )}
      </div>
    </div>
  );
}

function DependencyGroupCard({ direction, focused, group, onSelectUnit, t }) {
  return (
    <details className={`dependency-group-card ${group.status}${focused ? ' focused-search-target' : ''}`} open={focused || undefined}>
      <summary>
        <CircleDot size={16} />
        <div>
          <strong>{direction === 'source'
            ? `${group.primaryName} -> ${group.secondaryName}`
            : `${group.primaryName} <- ${group.secondaryName}`}</strong>
          <span>{group.dependencies.length} {t('dependencyRows')} · {group.fileCount} {t('dependencyFiles')}</span>
        </div>
        <DependencyGroupBadges group={group} t={t} />
      </summary>
      <div className="dependency-tree">
        <h3>{t('directoryTree')}</h3>
        {group.tree.children.map((node) => (
          <DependencyTreeNode key={node.id} node={node} onSelectUnit={onSelectUnit} t={t} />
        ))}
        {group.tree.files.map((file) => (
          <DependencyFileGroup file={file} key={file.id} onSelectUnit={onSelectUnit} t={t} />
        ))}
      </div>
    </details>
  );
}

function DependencyGroupBadges({ group, t }) {
  return (
    <div className="dependency-badges">
      {group.counts.blocked > 0 && <mark className="blocked">{group.counts.blocked} {t('blocked')}</mark>}
      {group.counts.private > 0 && <mark className="private">{group.counts.private} {t('privateApi')}</mark>}
      {group.counts.allowedState > 0 && <mark className="allowed-state">{group.counts.allowedState} {t('allowedState')}</mark>}
      {group.counts.internal > 0 && <mark className="internal">{group.counts.internal} {t('internal')}</mark>}
      {group.counts.allowed > 0 && <mark className="allowed">{group.counts.allowed} {t('allowed')}</mark>}
    </div>
  );
}

function DependencyTreeNode({ node, onSelectUnit, t }) {
  return (
    <details className="dependency-tree-node">
      <summary>
        <span>{node.name}</span>
        <small>{node.count} {t('dependencyRows')}</small>
      </summary>
      <div className="dependency-tree-children">
        {node.children.map((child) => (
          <DependencyTreeNode key={child.id} node={child} onSelectUnit={onSelectUnit} t={t} />
        ))}
        {node.files.map((file) => (
          <DependencyFileGroup file={file} key={file.id} onSelectUnit={onSelectUnit} t={t} />
        ))}
      </div>
    </details>
  );
}

function DependencyFileGroup({ file, onSelectUnit, t }) {
  const [expanded, setExpanded] = useState(false);
  const visibleDependencies = expanded ? file.dependencies : file.dependencies.slice(0, 12);
  const hiddenCount = file.dependencies.length - visibleDependencies.length;
  const copyValue = file.path ?? file.fallbackName ?? file.name;

  return (
    <details className="dependency-file-group">
      <summary>
        <FileCode2 size={15} />
        <span>{file.name}</span>
        <small>{file.dependencies.length} {t('dependencyRows')}</small>
        <CopyAction
          ariaLabel={t('copyFilePath')}
          className="inline-copy"
          title={t('copyFilePath')}
          value={copyValue}
          t={t}
        />
      </summary>
      <div className="dependency-file-rows">
        {visibleDependencies.map((dependency) => (
          <article className={`dependency-row ${dependencyStatusKey(dependency)}`} key={dependency.id}>
            <CircleDot size={16} />
            <div>
              <strong title={`${dependency.fromUnitName} -> ${dependency.toUnitName}`}>
                {shortUnitName(dependency.fromUnitName)} {'->'} {shortUnitName(dependency.toUnitName)}
              </strong>
              <DependencyEndpoints dependency={dependency} onSelectUnit={onSelectUnit} t={t} />
            </div>
            <mark className={dependencyStatusKey(dependency)}>{dependencyStatusLabel(dependency, t)}</mark>
          </article>
        ))}
        {hiddenCount > 0 && (
          <button className="show-more-row" onClick={() => setExpanded(true)} type="button">
            {t('showMore')} · {hiddenCount}
          </button>
        )}
      </div>
    </details>
  );
}

function DependencyEndpoints({ dependency, onSelectUnit, t }) {
  if (!dependency) {
    return <span>{t('dependencyUnavailable')}</span>;
  }

  return (
    <span className="dependency-endpoints">
      <span className="dependency-endpoint">
        <button onClick={() => onSelectUnit(dependency.fromUnitId)} title={dependency.fromUnitName} type="button">
          {compactNamespace(dependency.fromUnitName, shortUnitName(dependency.fromUnitName))}
        </button>
        <CopyAction ariaLabel={t('copyUnitName')} title={t('copyUnitName')} value={dependency.fromUnitName} t={t} />
      </span>
      <span>{'->'}</span>
      <span className="dependency-endpoint">
        <button onClick={() => onSelectUnit(dependency.toUnitId)} title={dependency.toUnitName} type="button">
          {compactNamespace(dependency.toUnitName, shortUnitName(dependency.toUnitName))}
        </button>
        <CopyAction ariaLabel={t('copyUnitName')} title={t('copyUnitName')} value={dependency.toUnitName} t={t} />
      </span>
    </span>
  );
}

function UnitDetail({ unit, indexed, onSelectUnit, t }) {
  if (!unit) {
    return <EmptyState icon={<FileCode2 size={22} />} title={t('noUnitSelected')} />;
  }

  const outgoing = indexed.dependenciesByFromUnit.get(unit.id) ?? [];
  const incoming = indexed.dependenciesByToUnit.get(unit.id) ?? [];

  return (
    <section className="panel unit-detail">
      <header>
        <div>
          <span>{t('selectedUnit')}</span>
          <h2>{unit.shortName}</h2>
          <p title={unit.name}>{compactNamespace(unit.name, unit.shortName)}</p>
        </div>
        <mark>{unit.componentName}</mark>
      </header>
      <div className="unit-detail-grid">
        <div className="unit-facts">
          <Fact label={t('type')} value={unit.type} t={t} />
          <Fact label={t('api')} value={unit.isPublic ? t('publicApi') : t('privateApi')} t={t} />
          <Fact label={t('abstract')} value={unit.isAbstract === null ? t('notApplicable') : unit.isAbstract ? t('yes') : t('no')} t={t} />
          <Fact label={t('instability')} value={formatRate(unit.metrics.instability)} t={t} />
          <Fact label={t('primitive')} value={formatRate(unit.metrics.primitiveness)} t={t} />
          <Fact label={t('path')} value={unit.path ?? t('unknown')} copyable t={t} />
        </div>
        <UnitGraph unit={unit} incoming={incoming} outgoing={outgoing} indexed={indexed} onSelectUnit={onSelectUnit} t={t} />
      </div>
      <div className="unit-dependency-grid">
        <UnitDependencyList title={t('outgoingDependencies')} dependencies={outgoing} side="to" onSelectUnit={onSelectUnit} t={t} />
        <UnitDependencyList title={t('incomingDependencies')} dependencies={incoming} side="from" onSelectUnit={onSelectUnit} t={t} />
      </div>
    </section>
  );
}

function Fact({ label, value, copyable = false, t }) {
  return (
    <div className="fact">
      <span>{label}</span>
      <strong title={String(value)}>{value}</strong>
      {copyable && (
        <CopyAction ariaLabel={t('copy')} title={t('copy')} value={value} t={t} />
      )}
    </div>
  );
}

function CopyAction({ ariaLabel, className = '', title, value, t }) {
  const [copied, setCopied] = useState(false);

  const copyValue = async (event) => {
    event.preventDefault();
    event.stopPropagation();
    await writeClipboard(String(value ?? ''));
    setCopied(true);
    window.setTimeout(() => setCopied(false), 1100);
  };

  return (
    <button
      aria-label={copied ? t('copied') : ariaLabel}
      className={['copy-action', copied ? 'copied' : '', className].filter(Boolean).join(' ')}
      onClick={copyValue}
      title={copied ? t('copied') : title}
      type="button"
    >
      {copied ? <Check size={14} /> : <Copy size={14} />}
    </button>
  );
}

function UnitGraph({ unit, incoming, outgoing, indexed, onSelectUnit, t }) {
  const related = [
    ...incoming.slice(0, 5).map((dependency, index) => ({ dependency, id: dependency.fromUnitId, angle: Math.PI + (index - 2) * 0.32 })),
    ...outgoing.slice(0, 5).map((dependency, index) => ({ dependency, id: dependency.toUnitId, angle: (index - 2) * 0.32 })),
  ];
  const center = { x: 260, y: 130 };

  return (
    <div className="unit-graph">
      <svg viewBox="0 0 520 260" role="img" aria-label={t('unitDependencyGraphLabel')}>
        {related.map((item) => {
          const relatedUnit = indexed.unitsById.get(item.id);
          const x = center.x + Math.cos(item.angle) * 190;
          const y = center.y + Math.sin(item.angle) * 82;
          const isIncoming = item.dependency.toUnitId === unit.id;
          const status = dependencyStatusKey(item.dependency);
          return (
            <g key={item.dependency.id}>
              <title>{`${item.dependency.fromUnitName} -> ${item.dependency.toUnitName}: ${dependencyStatusLabel(item.dependency, t)}`}</title>
              <line className={`edge ${status}`} x1={isIncoming ? x : center.x} x2={isIncoming ? center.x : x} y1={isIncoming ? y : center.y} y2={isIncoming ? center.y : y} />
              <g className="unit-node" onClick={() => onSelectUnit(item.id)} role="button" tabIndex="0" transform={`translate(${x} ${y})`}>
                <rect height="34" rx="8" width="150" x="-75" y="-17" />
                <text textAnchor="middle" y="4">{truncate(relatedUnit?.shortName ?? item.dependency.toUnitName, 20)}</text>
              </g>
            </g>
          );
        })}
        <g className="unit-node selected" transform={`translate(${center.x} ${center.y})`}>
          <rect height="40" rx="8" width="170" x="-85" y="-20" />
          <text textAnchor="middle" y="5">{truncate(unit.shortName, 22)}</text>
        </g>
      </svg>
    </div>
  );
}

function UnitDependencyList({ title, dependencies, side, onSelectUnit, t }) {
  const [expanded, setExpanded] = useState(false);
  const visibleDependencies = expanded ? dependencies : dependencies.slice(0, 12);
  const hiddenCount = dependencies.length - visibleDependencies.length;

  return (
    <div className="unit-dependency-list">
      <h3>{title}</h3>
      {dependencies.length ? visibleDependencies.map((dependency) => {
        const unitId = side === 'to' ? dependency.toUnitId : dependency.fromUnitId;
        const unitName = side === 'to' ? dependency.toUnitName : dependency.fromUnitName;
        const componentName = side === 'to' ? dependency.toComponentName : dependency.fromComponentName;
        const status = dependencyStatusKey(dependency);
        return (
          <button className={`unit-dependency-row ${status}`} key={dependency.id} onClick={() => onSelectUnit(unitId)} type="button">
            <span title={unitName}>{shortUnitName(unitName)}</span>
            <mark className={status}>{componentName}</mark>
          </button>
        );
      }) : <p>{t('noDependencies')}</p>}
      {hiddenCount > 0 && (
        <button className="show-more-row" onClick={() => setExpanded(true)} type="button">
          {t('showMore')} · {hiddenCount}
        </button>
      )}
    </div>
  );
}

function EmptyState({ icon, title }) {
  return (
    <section className="empty-state">
      {icon}
      <strong>{title}</strong>
    </section>
  );
}

function ShellMessage({ title, text }) {
  return (
    <main className="shell-message">
      <Box size={32} />
      <h1>{title}</h1>
      <p>{text}</p>
    </main>
  );
}

function ComponentStatus({ component, violations }) {
  if (violations.some((violation) => violation.status === 'active')) {
    return <ShieldAlert className="status-icon problem" size={16} />;
  }
  if (component.health.hasDistanceOverage) {
    return <AlertTriangle className="status-icon warning" size={16} />;
  }
  return <CheckCircle2 className="status-icon ok" size={16} />;
}

function SidebarMetrics({ metrics }) {
  return (
    <div className="component-row-metrics">
      {metrics.map(([icon, value, title]) => (
        <small aria-label={`${title}: ${value}`} className="component-row-metric" key={title} title={`${title}: ${value}`}>
          {icon}
          <span>{value}</span>
        </small>
      ))}
    </div>
  );
}

function buildIndex(report) {
  const componentsById = new Map(report.components.map((component) => [component.id, component]));
  const unitsById = new Map(report.units.map((unit) => [unit.id, unit]));
  const dependenciesById = new Map(report.dependencies.map((dependency) => [dependency.id, dependency]));
  const dependenciesByFromUnit = new Map();
  const dependenciesByToUnit = new Map();
  const violationsByComponent = new Map();
  const componentEdges = new Map();

  for (const dependency of report.dependencies) {
    pushMapList(dependenciesByFromUnit, dependency.fromUnitId, dependency);
    pushMapList(dependenciesByToUnit, dependency.toUnitId, dependency);
    if (!dependency.isInternal && dependency.fromComponentId !== dependency.toComponentId) {
      const key = `${dependency.fromComponentId}->${dependency.toComponentId}`;
      const edge = componentEdges.get(key) ?? {
        id: key,
        fromComponentId: dependency.fromComponentId,
        toComponentId: dependency.toComponentId,
        fromComponentName: dependency.fromComponentName,
        toComponentName: dependency.toComponentName,
        weight: 0,
        sourceUnitIds: new Set(),
        targetUnitIds: new Set(),
        counts: { allowed: 0, allowedState: 0, blocked: 0, internal: 0, private: 0 },
      };
      edge.weight += 1;
      edge.sourceUnitIds.add(dependency.fromUnitId);
      edge.targetUnitIds.add(dependency.toUnitId);
      incrementDependencyCount(edge.counts, dependencyStatusKey(dependency));
      componentEdges.set(key, edge);
    }
  }

  for (const violation of report.violations) {
    pushMapList(violationsByComponent, violation.fromComponentId, violation);
  }

  const outgoingComponentEdges = new Map();
  const incomingComponentEdges = new Map();
  const componentEdgeList = [...componentEdges.values()].map(({ sourceUnitIds, targetUnitIds, ...edge }) => ({
    ...edge,
    sourceUnitCount: sourceUnitIds.size,
    targetUnitCount: targetUnitIds.size,
    status: worstDependencyStatus(edge.counts),
  }));
  for (const edge of componentEdgeList) {
    pushMapList(outgoingComponentEdges, edge.fromComponentId, edge);
    pushMapList(incomingComponentEdges, edge.toComponentId, edge);
  }

  return {
    componentsById,
    unitsById,
    dependenciesById,
    dependenciesByFromUnit,
    dependenciesByToUnit,
    violationsByComponent,
    componentEdges: componentEdgeList,
    outgoingComponentEdges,
    incomingComponentEdges,
  };
}

function pushMapList(map, key, item) {
  const list = map.get(key) ?? [];
  list.push(item);
  map.set(key, list);
}

function buildDependencyComponentOptions(report, indexed) {
  const components = new Map(report.components.map((component) => [component.id, component]));

  report.dependencies.forEach((dependency) => {
    if (!components.has(dependency.fromComponentId)) {
      components.set(dependency.fromComponentId, {
        id: dependency.fromComponentId,
        name: dependency.fromComponentName,
        metrics: { units: 0 },
      });
    }
    if (!components.has(dependency.toComponentId)) {
      components.set(dependency.toComponentId, {
        id: dependency.toComponentId,
        name: dependency.toComponentName,
        metrics: { units: 0 },
      });
    }
  });

  return [...components.values()].sort((left, right) => {
    const leftKnown = indexed.componentsById.has(left.id) ? 0 : 1;
    const rightKnown = indexed.componentsById.has(right.id) ? 0 : 1;
    return leftKnown - rightKnown || left.name.localeCompare(right.name);
  });
}

function componentGraph(component, report, indexed, scope = 'all', layout = 'circle', selectedComponentIds = []) {
  if (!report.components.length && !indexed.componentEdges.length) {
    return { nodes: [], edges: [] };
  }

  const selectedComponentSet = new Set(selectedComponentIds);
  const showSelectedComponentContext = !component && selectedComponentSet.size === 1;
  const graphEdges = component
    ? indexed.componentEdges.filter((edge) => matchesComponentGraphScope(edge, component.id, scope))
    : indexed.componentEdges.filter((edge) => {
      if (showSelectedComponentContext) {
        return selectedComponentSet.has(edge.fromComponentId) || selectedComponentSet.has(edge.toComponentId);
      }

      return selectedComponentSet.has(edge.fromComponentId) && selectedComponentSet.has(edge.toComponentId);
    });
  const componentOrder = new Map(report.components.map((item, index) => [item.id, index]));
  const analyzedComponents = new Map(report.components.map((item) => [item.id, item]));
  const allDependencyComponents = new Map(buildDependencyComponentOptions(report, indexed).map((item) => [item.id, item]));
  const graphComponents = new Map();

  if (!component) {
    [...allDependencyComponents.values()]
      .filter((item) => selectedComponentSet.has(item.id))
      .forEach((item) => {
        graphComponents.set(item.id, componentGraphNode(item, !analyzedComponents.has(item.id)));
      });
  } else {
    graphComponents.set(component.id, componentGraphNode(component, false));
  }

  graphEdges.forEach((edge) => {
    if (!graphComponents.has(edge.fromComponentId)) {
      const analyzedComponent = analyzedComponents.get(edge.fromComponentId);
      graphComponents.set(
        edge.fromComponentId,
        analyzedComponent
          ? componentGraphNode(analyzedComponent, false)
          : componentGraphExternalNode(edge.fromComponentId, edge.fromComponentName),
      );
    }
    if (!graphComponents.has(edge.toComponentId)) {
      const analyzedComponent = analyzedComponents.get(edge.toComponentId);
      graphComponents.set(
        edge.toComponentId,
        analyzedComponent
          ? componentGraphNode(analyzedComponent, false)
          : componentGraphExternalNode(edge.toComponentId, edge.toComponentName),
      );
    }
  });

  const graphComponentList = [...graphComponents.values()].sort((left, right) => {
    const leftOrder = componentOrder.get(left.id) ?? Number.MAX_SAFE_INTEGER;
    const rightOrder = componentOrder.get(right.id) ?? Number.MAX_SAFE_INTEGER;
    return leftOrder - rightOrder || left.name.localeCompare(right.name);
  });

  const nodes = layoutGraphComponents(graphComponentList, graphEdges, layout);
  const nodesById = new Map(nodes.map((node) => [node.id, node]));
  const edges = graphEdges
    .map((edge) => ({
      ...edge,
      from: nodesById.get(edge.fromComponentId),
      to: nodesById.get(edge.toComponentId),
      isSelected: component ? edge.fromComponentId === component.id || edge.toComponentId === component.id : false,
    }))
    .filter((edge) => edge.from && edge.to);

  return { nodes, edges };
}

function layoutGraphComponents(components, edges, layout) {
  if (layout === 'grid') {
    return gridGraphNodes(components);
  }
  if (layout === 'layers') {
    return layeredGraphNodes(components, edges);
  }

  return circleGraphNodes(components);
}

function circleGraphNodes(components) {
  const centerX = graphWidth / 2;
  const centerY = graphHeight / 2;
  const radiusX = Math.max(220, graphWidth / 2 - 130);
  const radiusY = Math.max(120, graphHeight / 2 - 82);

  return components.map((item, index) => {
    const angle = (Math.PI * 2 * index) / Math.max(components.length, 1) - Math.PI / 2;
    return graphNodeWithPosition(item, centerX + Math.cos(angle) * radiusX, centerY + Math.sin(angle) * radiusY);
  });
}

function gridGraphNodes(components) {
  const columns = Math.max(1, Math.ceil(Math.sqrt(components.length * (graphWidth / graphHeight))));
  const rows = Math.max(1, Math.ceil(components.length / columns));
  const cellWidth = (graphWidth - graphPadding * 2) / columns;
  const cellHeight = (graphHeight - graphPadding * 2) / rows;

  return components.map((item, index) => graphNodeWithPosition(
    item,
    graphPadding + cellWidth * (index % columns) + cellWidth / 2,
    graphPadding + cellHeight * Math.floor(index / columns) + cellHeight / 2,
  ));
}

function layeredGraphNodes(components, edges) {
  const incoming = new Map(components.map((item) => [item.id, 0]));
  const outgoing = new Map(components.map((item) => [item.id, 0]));
  edges.forEach((edge) => {
    outgoing.set(edge.fromComponentId, (outgoing.get(edge.fromComponentId) ?? 0) + edge.weight);
    incoming.set(edge.toComponentId, (incoming.get(edge.toComponentId) ?? 0) + edge.weight);
  });

  const buckets = [
    components.filter((item) => (incoming.get(item.id) ?? 0) === 0 && (outgoing.get(item.id) ?? 0) > 0),
    components.filter((item) => (incoming.get(item.id) ?? 0) > 0 && (outgoing.get(item.id) ?? 0) > 0),
    components.filter((item) => (incoming.get(item.id) ?? 0) > 0 && (outgoing.get(item.id) ?? 0) === 0),
  ];
  const isolated = components.filter((item) => (incoming.get(item.id) ?? 0) === 0 && (outgoing.get(item.id) ?? 0) === 0);
  if (isolated.length > 0) {
    buckets[1].push(...isolated);
  }

  const visibleBuckets = buckets.filter((bucket) => bucket.length > 0);
  const columnCount = Math.max(visibleBuckets.length, 1);
  const columnWidth = (graphWidth - graphPadding * 2) / columnCount;

  return visibleBuckets.flatMap((bucket, columnIndex) => {
    const rowHeight = (graphHeight - graphPadding * 2) / Math.max(bucket.length, 1);
    return bucket.map((item, rowIndex) => graphNodeWithPosition(
      item,
      graphPadding + columnWidth * columnIndex + columnWidth / 2,
      graphPadding + rowHeight * rowIndex + rowHeight / 2,
    ));
  });
}

function graphNodeWithPosition(item, x, y) {
  return {
    id: item.id,
    name: item.name,
    units: item.units,
    isExternal: item.isExternal,
    x: clamp(x, graphPadding, graphWidth - graphPadding),
    y: clamp(y, graphPadding, graphHeight - graphPadding),
  };
}

function matchesComponentGraphScope(edge, componentId, scope) {
  if (scope === 'outgoing') {
    return edge.fromComponentId === componentId;
  }
  if (scope === 'incoming') {
    return edge.toComponentId === componentId;
  }

  return edge.fromComponentId === componentId || edge.toComponentId === componentId;
}

function shapeGraphEdges(edges) {
  const reciprocalPairs = new Set();
  edges.forEach((edge) => {
    if (edges.some((candidate) => candidate.fromComponentId === edge.toComponentId && candidate.toComponentId === edge.fromComponentId)) {
      reciprocalPairs.add(edge.id);
    }
  });

  return edges.map((edge) => shapeGraphEdge(edge, reciprocalPairs.has(edge.id) ? 28 : 0));
}

function shapeGraphEdge(edge, curveOffset) {
  const nodeRadius = 42;
  const dx = edge.to.x - edge.from.x;
  const dy = edge.to.y - edge.from.y;
  const length = Math.max(Math.hypot(dx, dy), 1);
  const unitX = dx / length;
  const unitY = dy / length;
  const start = {
    x: edge.from.x + unitX * nodeRadius,
    y: edge.from.y + unitY * nodeRadius,
  };
  const end = {
    x: edge.to.x - unitX * nodeRadius,
    y: edge.to.y - unitY * nodeRadius,
  };
  const mid = {
    x: (start.x + end.x) / 2,
    y: (start.y + end.y) / 2,
  };
  const normal = { x: -unitY, y: unitX };
  const control = {
    x: mid.x + normal.x * curveOffset,
    y: mid.y + normal.y * curveOffset,
  };
  const label = {
    x: 0.25 * start.x + 0.5 * control.x + 0.25 * end.x,
    y: 0.25 * start.y + 0.5 * control.y + 0.25 * end.y - 7,
  };

  return {
    edge,
    label,
    path: curveOffset
      ? `M ${start.x} ${start.y} Q ${control.x} ${control.y} ${end.x} ${end.y}`
      : `M ${start.x} ${start.y} L ${end.x} ${end.y}`,
  };
}

function graphEdgeClassName(edge) {
  return [
    'edge',
    edge.status,
    edge.isSelected ? 'selected' : '',
  ].filter(Boolean).join(' ');
}

function graphNodeClassName(node, component) {
  return [
    'node',
    node.id === component?.id ? 'selected' : '',
    node.isExternal ? 'external' : '',
  ].filter(Boolean).join(' ');
}

function componentGraphNode(component, isExternal) {
  return {
    id: component.id,
    name: component.name,
    units: component.metrics.units,
    isExternal,
  };
}

function componentGraphExternalNode(id, name) {
  return {
    id,
    name,
    units: 0,
    isExternal: true,
  };
}

function matchesUnit(unit, query) {
  const normalizedQuery = normalizeQuery(query);
  return !normalizedQuery
    || unit.name.toLowerCase().includes(normalizedQuery)
    || unit.shortName.toLowerCase().includes(normalizedQuery)
    || (unit.path ?? '').toLowerCase().includes(normalizedQuery)
    || unit.componentName.toLowerCase().includes(normalizedQuery);
}

function matchesDependency(dependency, query) {
  const normalizedQuery = normalizeQuery(query);
  return !normalizedQuery
    || dependency.fromUnitName.toLowerCase().includes(normalizedQuery)
    || dependency.toUnitName.toLowerCase().includes(normalizedQuery)
    || dependency.fromComponentName.toLowerCase().includes(normalizedQuery)
    || dependency.toComponentName.toLowerCase().includes(normalizedQuery);
}

function matchesDependencyWithUnits(dependency, indexed, query) {
  const normalizedQuery = normalizeQuery(query);
  if (!normalizedQuery) {
    return true;
  }

  const fromUnit = indexed.unitsById.get(dependency.fromUnitId);
  const toUnit = indexed.unitsById.get(dependency.toUnitId);

  return matchesDependency(dependency, query)
    || (fromUnit?.path ?? '').toLowerCase().includes(normalizedQuery)
    || (toUnit?.path ?? '').toLowerCase().includes(normalizedQuery);
}

function filterDependencies(
  dependencies,
  indexed,
  query,
  sourceComponentIds,
  targetComponentIds,
  status,
  relationComponentId = null,
  relationScope = 'all',
) {
  const sourceIds = new Set(sourceComponentIds);
  const targetIds = new Set(targetComponentIds);

  return dependencies
    .filter((dependency) => matchesDependencyRelationScope(dependency, relationComponentId, relationScope))
    .filter((dependency) => sourceIds.has(dependency.fromComponentId))
    .filter((dependency) => targetIds.has(dependency.toComponentId))
    .filter((dependency) => status === 'all' || dependencyStatusKey(dependency) === status)
    .filter((dependency) => matchesDependencyWithUnits(dependency, indexed, query));
}

function matchesDependencyRelationScope(dependency, componentId, scope) {
  if (!componentId) {
    return true;
  }
  if (dependency.fromComponentId === dependency.toComponentId) {
    return false;
  }
  if (scope === 'outgoing') {
    return dependency.fromComponentId === componentId;
  }
  if (scope === 'incoming') {
    return dependency.toComponentId === componentId;
  }

  return dependency.fromComponentId === componentId || dependency.toComponentId === componentId;
}

function buildDependencyListSummary(dependencies, direction, sourceComponentIds, targetComponentIds, t) {
  let componentKey = 'componentLinksSummary';
  let componentCount = countComponentDependencyLinks(dependencies);

  if (direction === 'source' && sourceComponentIds.length === 1) {
    componentKey = 'dependsOnComponentsSummary';
    componentCount = countUniqueDependencySide(dependencies, 'toComponentId', sourceComponentIds[0]);
  } else if (direction === 'target' && targetComponentIds.length === 1) {
    componentKey = 'dependedOnByComponentsSummary';
    componentCount = countUniqueDependencySide(dependencies, 'fromComponentId', targetComponentIds[0]);
  }

  return {
    componentText: interpolate(t(componentKey), { count: componentCount }),
    unitText: interpolate(t('unitDependenciesSummary'), { count: dependencies.length }),
  };
}

function countUniqueDependencySide(dependencies, key, excludedComponentId) {
  const ids = new Set();
  dependencies.forEach((dependency) => {
    const componentId = dependency[key];
    if (componentId !== excludedComponentId) {
      ids.add(componentId);
    }
  });

  return ids.size;
}

function countComponentDependencyLinks(dependencies) {
  const links = new Set();
  dependencies.forEach((dependency) => {
    if (dependency.fromComponentId !== dependency.toComponentId) {
      links.add(`${dependency.fromComponentId}->${dependency.toComponentId}`);
    }
  });

  return links.size;
}

function buildSearchSuggestions(query, report, indexed, t) {
  const normalizedQuery = normalizeQuery(query);
  if (!normalizedQuery) {
    return [];
  }

  const groups = [];
  const matchingComponents = report.components
    .filter((component) => component.name.toLowerCase().includes(normalizedQuery))
    .slice(0, 5)
    .map((component) => ({
      id: `component:${component.id}`,
      type: 'component',
      componentId: component.id,
      icon: <Layers3 size={15} />,
      title: component.name,
      subtitle: `${component.metrics.units} ${t('unitsCount')} · ${component.metrics.outgoingComponents} ${t('componentDependsOn')}`,
    }));

  if (matchingComponents.length) {
    groups.push({ id: 'components', label: t('componentResults'), items: matchingComponents });
  }

  const matchingUnits = report.units
    .filter((unit) => matchesUnit(unit, query))
    .slice(0, 6)
    .map((unit) => ({
      id: `unit:${unit.id}`,
      type: 'unit',
      componentId: unit.componentId,
      unitId: unit.id,
      icon: <FileCode2 size={15} />,
      title: unit.shortName,
      subtitle: `${unit.componentName} · ${compactNamespace(unit.name, unit.shortName)}`,
    }));

  if (matchingUnits.length) {
    groups.push({ id: 'units', label: t('unitResults'), items: matchingUnits });
  }

  const matchingViolations = report.violations
    .filter((violation) => matchesViolation(violation, indexed, query))
    .slice(0, 5)
    .map((violation) => {
      const dependency = indexed.dependenciesById.get(violation.dependencyId);
      return {
        id: `violation:${violation.id}`,
        type: 'violation',
        componentId: violation.fromComponentId,
        violationId: violation.id,
        icon: <ShieldAlert size={15} />,
        title: violationMessage(violation, dependency, t),
        subtitle: dependency ? `${dependency.fromComponentName} -> ${dependency.toComponentName}` : violation.type,
      };
    });

  if (matchingViolations.length) {
    groups.push({ id: 'violations', label: t('violationResults'), items: matchingViolations });
  }

  const matchingDependencies = report.dependencies
    .filter((dependency) => matchesDependencyWithUnits(dependency, indexed, query))
    .slice(0, 6)
    .map((dependency) => ({
      id: `dependency:${dependency.id}`,
      type: 'dependency',
      dependencyId: dependency.id,
      fromComponentId: dependency.fromComponentId,
      toComponentId: dependency.toComponentId,
      icon: <ArrowRight size={15} />,
      title: `${dependency.fromComponentName} -> ${dependency.toComponentName}`,
      subtitle: `${shortUnitName(dependency.fromUnitName)} -> ${shortUnitName(dependency.toUnitName)} · ${dependencyStatusLabel(dependency, t)}`,
    }));

  if (matchingDependencies.length) {
    groups.push({ id: 'dependencies', label: t('dependencyResults'), items: matchingDependencies });
  }

  return groups;
}

function matchesViolation(violation, indexed, query) {
  const normalizedQuery = normalizeQuery(query);
  if (!normalizedQuery) {
    return true;
  }

  const dependency = indexed.dependenciesById.get(violation.dependencyId);
  return violation.message.toLowerCase().includes(normalizedQuery)
    || violation.type.toLowerCase().includes(normalizedQuery)
    || (dependency ? matchesDependency(dependency, query) : false);
}

function parseNavigationHash() {
  const hash = window.location.hash.replace(/^#/, '');
  const params = new URLSearchParams(hash);

  return {
    componentId: params.get('component'),
    query: params.get('q') ?? '',
    reportId: params.get('report'),
    unitId: params.get('unit'),
    view: normalizeReportView(params.get('view')),
  };
}

function buildNavigationHash({ activeReportId, query, rootReportId, selectedComponentId, selectedUnitId, view }) {
  const params = new URLSearchParams();
  params.set('view', normalizeReportView(view));
  if (rootReportId && activeReportId && activeReportId !== rootReportId) {
    params.set('report', activeReportId);
  }
  if (selectedComponentId) {
    params.set('component', selectedComponentId);
  }
  if (selectedUnitId) {
    params.set('unit', selectedUnitId);
  }
  if (query) {
    params.set('q', query);
  }

  return `#${params.toString()}`;
}

function normalizeSuiteReportId(suite, reportId) {
  if (!suite) {
    return 'root';
  }

  return reportId && findSuiteNode(suite.tree, reportId) ? reportId : suite.rootId;
}

function stripNavigationQuery(hash) {
  const params = new URLSearchParams(hash.replace(/^#/, ''));
  params.delete('q');

  return `#${params.toString()}`;
}

function applyNavigationState(state, indexed, setters) {
  const unit = state.unitId ? indexed.unitsById.get(state.unitId) : null;
  const componentId = unit?.componentId ?? (state.componentId && indexed.componentsById.has(state.componentId) ? state.componentId : null);

  setters.setQuery(state.query);
  setters.setSelectedComponentId(componentId);
  setters.setSelectedUnitId(unit?.id ?? null);
  setters.setView(unit ? 'units' : state.view);
}

function normalizeReportView(view) {
  return ['violations', 'dependencies', 'units'].includes(view) ? view : 'violations';
}

function normalizeQuery(query) {
  return query.trim().toLowerCase();
}

async function writeClipboard(value) {
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(value);
    return;
  }

  const textarea = document.createElement('textarea');
  textarea.value = value;
  textarea.setAttribute('readonly', 'readonly');
  textarea.style.position = 'fixed';
  textarea.style.left = '-9999px';
  document.body.appendChild(textarea);
  textarea.select();
  document.execCommand('copy');
  textarea.remove();
}

function escapeCssAttribute(value) {
  if (window.CSS?.escape) {
    return window.CSS.escape(value);
  }

  return value.replace(/["\\]/g, '\\$&');
}

function shortUnitName(name) {
  const parts = name.split('\\');
  return parts[parts.length - 1] || name;
}

function compactNamespace(name, shortName) {
  const suffix = shortName || shortUnitName(name);
  const namespace = name.endsWith(suffix) ? name.slice(0, -suffix.length).replace(/\\$/, '') : '';
  return namespace ? namespace : name;
}

function readEmbeddedReport() {
  const element = document.getElementById('phpca-report-data');
  if (!element?.textContent) {
    return null;
  }

  try {
    return JSON.parse(element.textContent);
  } catch {
    return null;
  }
}

function readEmbeddedSuite(rootReport) {
  const element = document.getElementById('phpca-report-suite');
  if (!element?.textContent) {
    return null;
  }

  try {
    const suite = JSON.parse(element.textContent);
    if (rootReport && suite?.tree && !suite.tree.report) {
      return {
        ...suite,
        tree: {
          ...suite.tree,
          report: rootReport,
        },
      };
    }

    return suite;
  } catch {
    return null;
  }
}

function findSuiteNode(node, id) {
  if (!node || node.id === id) {
    return node;
  }

  for (const child of node.children ?? []) {
    const match = findSuiteNode(child, id);
    if (match) {
      return match;
    }
  }

  return null;
}

function findSuitePath(node, id, path = []) {
  if (!node) {
    return null;
  }

  const nextPath = [...path, node];
  if (node.id === id) {
    return nextPath;
  }

  for (const child of node.children ?? []) {
    const match = findSuitePath(child, id, nextPath);
    if (match) {
      return match;
    }
  }

  return null;
}

function formatRate(value) {
  return Number(value ?? 0).toFixed(3);
}

function formatPercent(value) {
  return `${Math.round(clamp01(value) * 100)}%`;
}

function formatInteger(value) {
  return new Intl.NumberFormat().format(Number(value ?? 0));
}

function legacyMetrics(legacy) {
  return {
    units: Number(legacy?.units ?? 0),
    legacyUnits: Number(legacy?.legacyUnits ?? 0),
    modernUnits: Number(legacy?.modernUnits ?? 0),
    linesOfCode: Number(legacy?.linesOfCode ?? 0),
    legacyLinesOfCode: Number(legacy?.legacyLinesOfCode ?? 0),
    modernLinesOfCode: Number(legacy?.modernLinesOfCode ?? 0),
    legacyRate: clamp01(legacy?.legacyRate),
    modernRate: clamp01(legacy?.modernRate),
  };
}

function formatDate(value, locale) {
  if (!value) {
    return dictionaries[locale]?.generatedReport ?? dictionaries.en.generatedReport;
  }
  return new Intl.DateTimeFormat(localeToIntl[locale] ?? localeToIntl.en, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function componentMetricQuality(component, metric) {
  if (metric === 'distance' || metric === 'primitiveness') {
    return 1 - component.metrics[metric];
  }

  return 1 - component.metrics.distance;
}

function qualityColor(quality) {
  const hue = clamp01(quality) * 120;

  return `hsl(${hue}deg 72% 38%)`;
}

function svgPoint(svg, event) {
  const point = svg.createSVGPoint();
  point.x = event.clientX;
  point.y = event.clientY;

  return point.matrixTransform(svg.getScreenCTM().inverse());
}

function readGraphPositions(report, graphKey) {
  try {
    return JSON.parse(localStorage.getItem(graphPositionStorageKey(report, graphKey)) ?? '{}');
  } catch {
    return {};
  }
}

function saveGraphPositions(report, graphKey, positions) {
  localStorage.setItem(graphPositionStorageKey(report, graphKey), JSON.stringify(positions));
}

function removeGraphPositions(report, graphKey) {
  localStorage.removeItem(graphPositionStorageKey(report, graphKey));
}

function graphPositionStorageKey(report, graphKey) {
  const reportKey = report.components.map((component) => component.id).join('|');

  return `phpca-report-graph:${reportKey}:${graphKey}`;
}

function readGraphViewport(report, graphKey) {
  try {
    const viewport = JSON.parse(localStorage.getItem(graphViewportStorageKey(report, graphKey)) ?? 'null');

    if (!viewport || typeof viewport !== 'object') {
      return defaultGraphViewport;
    }

    return {
      x: Number(viewport.x) || 0,
      y: Number(viewport.y) || 0,
      scale: clamp(Number(viewport.scale) || 1, 0.35, 3),
    };
  } catch {
    return defaultGraphViewport;
  }
}

function saveGraphViewport(report, graphKey, viewport) {
  localStorage.setItem(graphViewportStorageKey(report, graphKey), JSON.stringify(viewport));
}

function removeGraphViewport(report, graphKey) {
  localStorage.removeItem(graphViewportStorageKey(report, graphKey));
}

function graphViewportStorageKey(report, graphKey) {
  const reportKey = report.components.map((component) => component.id).join('|');

  return `phpca-report-graph-viewport:${reportKey}:${graphKey}`;
}

function readGraphLayout(report) {
  const layout = localStorage.getItem(graphLayoutStorageKey(report));

  return graphLayouts.includes(layout) ? layout : 'circle';
}

function saveGraphLayout(report, layout) {
  localStorage.setItem(graphLayoutStorageKey(report), layout);
}

function graphLayoutStorageKey(report) {
  const reportKey = report.components.map((component) => component.id).join('|');

  return `phpca-report-graph-layout:${reportKey}`;
}

function clamp(value, min, max) {
  return Math.max(min, Math.min(max, value));
}

function capitalize(value) {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

function clamp01(value) {
  return clamp(Number(value) || 0, 0, 1);
}

function dependencyStatusLabel(dependency, t) {
  return edgeStatusLabel(dependencyStatusKey(dependency), t);
}

function dependencyStatusKey(dependency) {
  if (dependency.isInternal) {
    return 'internal';
  }
  if (dependency.isAllowedState && (!dependency.isComponentAllowed || !dependency.isTargetPublic)) {
    return 'allowed-state';
  }
  if (!dependency.isComponentAllowed) {
    return 'blocked';
  }
  if (!dependency.isTargetPublic) {
    return 'private';
  }
  return 'allowed';
}

function edgeStatusLabel(status, t) {
  if (status === 'blocked') {
    return t('blocked');
  }
  if (status === 'private') {
    return t('privateApi');
  }
  if (status === 'allowed-state') {
    return t('allowedState');
  }
  if (status === 'internal') {
    return t('internal');
  }
  return t('allowed');
}

function violationMessage(violation, dependency, t) {
  if (!dependency) {
    return violation.message;
  }

  const key = violation.type === 'private-unit'
    ? 'dependencyPrivateViolation'
    : 'dependencyBlockedViolation';

  return interpolate(t(key), {
    from: dependency.fromUnitName,
    to: dependency.toUnitName,
  });
}

function interpolate(template, values) {
  return template.replace(/\{(\w+)}/g, (_, key) => values[key] ?? '');
}

function buildDependencyGroups(dependencies, indexed, direction) {
  const groups = new Map();

  for (const dependency of dependencies) {
    const primaryComponentId = direction === 'source' ? dependency.fromComponentId : dependency.toComponentId;
    const secondaryComponentId = direction === 'source' ? dependency.toComponentId : dependency.fromComponentId;
    const primaryComponentName = direction === 'source' ? dependency.fromComponentName : dependency.toComponentName;
    const secondaryComponentName = direction === 'source' ? dependency.toComponentName : dependency.fromComponentName;
    const key = `${primaryComponentId}->${secondaryComponentId}`;
    const group = groups.get(key) ?? {
      id: key,
      primaryName: primaryComponentName,
      secondaryName: secondaryComponentName,
      dependencies: [],
      counts: { allowed: 0, allowedState: 0, blocked: 0, internal: 0, private: 0 },
      tree: createDirectoryNode('root', 'root'),
      filePaths: new Set(),
    };

    group.dependencies.push(dependency);
    incrementDependencyCount(group.counts, dependencyStatusKey(dependency));
    addDependencyToTree(group.tree, dependency, indexed, direction);
    group.filePaths.add(primaryUnitPath(dependency, indexed, direction) ?? 'unknown');
    groups.set(key, group);
  }

  return [...groups.values()]
    .map((group) => ({
      ...group,
      fileCount: group.filePaths.size,
      status: worstDependencyStatus(group.counts),
      tree: sortDirectoryNode(group.tree),
    }))
    .sort((left, right) => right.dependencies.length - left.dependencies.length || left.primaryName.localeCompare(right.primaryName));
}

function incrementDependencyCount(counts, status) {
  if (status === 'allowed-state') {
    counts.allowedState += 1;
    return;
  }
  counts[status] += 1;
}

function worstDependencyStatus(counts) {
  if (counts.blocked > 0) {
    return 'blocked';
  }
  if (counts.private > 0) {
    return 'private';
  }
  if (counts.allowedState > 0) {
    return 'allowed-state';
  }
  if (counts.internal > 0 && counts.allowed === 0) {
    return 'internal';
  }
  return 'allowed';
}

function addDependencyToTree(root, dependency, indexed, direction) {
  const path = primaryUnitPath(dependency, indexed, direction);
  const fallbackName = primaryUnitName(dependency, direction);
  const parts = normalizePathParts(path);
  const fileName = parts.pop() ?? 'unknown';
  let node = root;
  node.count += 1;

  for (const part of parts) {
    let child = node.childMap.get(part);
    if (!child) {
      child = createDirectoryNode(`${node.id}/${part}`, part);
      node.childMap.set(part, child);
      node.children.push(child);
    }
    child.count += 1;
    node = child;
  }

  let file = node.fileMap.get(fileName);
  if (!file) {
    file = { id: `${node.id}/${fileName}`, name: fileName, path, fallbackName, dependencies: [] };
    node.fileMap.set(fileName, file);
    node.files.push(file);
  }
  file.dependencies.push(dependency);
}

function createDirectoryNode(id, name) {
  return {
    id,
    name,
    count: 0,
    children: [],
    files: [],
    childMap: new Map(),
    fileMap: new Map(),
  };
}

function sortDirectoryNode(node) {
  return {
    ...node,
    children: node.children
      .map(sortDirectoryNode)
      .sort((left, right) => right.count - left.count || left.name.localeCompare(right.name)),
    files: [...node.files].sort((left, right) => right.dependencies.length - left.dependencies.length || left.name.localeCompare(right.name)),
  };
}

function primaryUnitPath(dependency, indexed, direction) {
  const unitId = direction === 'source' ? dependency.fromUnitId : dependency.toUnitId;
  return indexed.unitsById.get(unitId)?.path;
}

function primaryUnitName(dependency, direction) {
  return direction === 'source' ? dependency.fromUnitName : dependency.toUnitName;
}

function normalizePathParts(path) {
  const parts = (path ?? 'unknown')
    .replace(/\\/g, '/')
    .split('/')
    .filter(Boolean);
  const anchors = ['src', 'app', 'lib', 'tests', 'bin', 'vendor'];
  const anchorIndex = parts.findIndex((part) => anchors.includes(part));

  if (anchorIndex >= 0) {
    return parts.slice(anchorIndex);
  }

  return parts.slice(Math.max(0, parts.length - 4));
}

function truncate(value, maxLength) {
  return value.length > maxLength ? `${value.slice(0, maxLength - 1)}...` : value;
}

createRoot(document.getElementById('root')).render(<App />);
