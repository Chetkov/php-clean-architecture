import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { useVirtualizer } from '@tanstack/react-virtual';
import {
  AlertTriangle,
  ArrowRight,
  Box,
  CheckCircle2,
  CircleDot,
  ChevronsLeftRight,
  Copy,
  FileCode2,
  GitBranch,
  Layers3,
  Search,
  SlidersHorizontal,
  X,
  ShieldAlert,
} from 'lucide-react';
import './styles.css';

const fallbackReport = {
  schemaVersion: 1,
  generatedAt: null,
  summary: { components: 0, units: 0, dependencies: 0, violations: 0, activeViolations: 0 },
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
    clearSearch: 'Clear search',
    dependencyOverview: 'Dependency map',
    filtered: 'filtered',
    noUnitSelected: 'Select a unit to inspect its dependencies',
    openSearch: 'Search',
    overview: 'Overview',
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
    dependencyStatus: 'Status',
    directoryTree: 'Directory tree',
    dependencies: 'Dependencies',
    dependencyGraphLabel: 'Component dependency graph',
    dependencyUnavailable: 'Unknown dependency',
    distance: 'Distance',
    distanceRanking: 'Distance ranking',
    flipDependencyDirection: 'Flip dependency direction',
    fromComponents: 'From components',
    generatedReport: 'Generated report',
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
    noDependencies: 'No dependencies',
    noDependencyDistribution: 'No dependency distribution',
    noExternalDependencies: 'No external component dependencies',
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
    searchPlaceholder: 'Search components, units, paths or dependencies',
    selectAll: 'Select all',
    selectedUnit: 'Selected unit',
    sourceFirst: 'Source first',
    targetFirst: 'Target first',
    toComponents: 'To components',
    type: 'Type',
    unitDependencyGraphLabel: 'Selected unit dependency graph',
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
    clearSearch: 'Очистить поиск',
    dependencyOverview: 'Карта зависимостей',
    filtered: 'отфильтровано',
    noUnitSelected: 'Выберите юнит, чтобы посмотреть его зависимости',
    openSearch: 'Поиск',
    overview: 'Обзор',
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
    dependencyStatus: 'Статус',
    directoryTree: 'Дерево директорий',
    dependencies: 'Зависимости',
    dependencyGraphLabel: 'Граф зависимостей компонентов',
    dependencyUnavailable: 'Зависимость не найдена',
    distance: 'Расстояние',
    distanceRanking: 'Рейтинг расстояния',
    flipDependencyDirection: 'Перевернуть направление зависимостей',
    fromComponents: 'Исходные компоненты',
    generatedReport: 'Сформированный отчет',
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
    noDependencies: 'Нет зависимостей',
    noDependencyDistribution: 'Нет распределения зависимостей',
    noExternalDependencies: 'Нет внешних зависимостей компонентов',
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
    searchPlaceholder: 'Поиск по компонентам, юнитам, путям или зависимостям',
    selectAll: 'Выбрать все',
    selectedUnit: 'Выбранный юнит',
    sourceFirst: 'Сначала кто зависит',
    targetFirst: 'Сначала от чего зависят',
    toComponents: 'Целевые компоненты',
    type: 'Тип',
    unitDependencyGraphLabel: 'Граф зависимостей выбранного юнита',
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
    clearSearch: '清除搜索',
    dependencyOverview: '依赖地图',
    filtered: '已筛选',
    noUnitSelected: '选择一个单元以查看其依赖',
    openSearch: '搜索',
    overview: '概览',
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
    dependencyStatus: '状态',
    directoryTree: '目录树',
    dependencies: '依赖',
    dependencyGraphLabel: '组件依赖图',
    dependencyUnavailable: '未知依赖',
    distance: '距离',
    distanceRanking: '距离排名',
    flipDependencyDirection: '切换依赖方向',
    fromComponents: '来源组件',
    generatedReport: '已生成报告',
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
    noDependencies: '没有依赖',
    noDependencyDistribution: '没有依赖分布',
    noExternalDependencies: '没有外部组件依赖',
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
    searchPlaceholder: '搜索组件、单元、路径或依赖',
    selectAll: '全选',
    selectedUnit: '选中的单元',
    sourceFirst: '来源优先',
    targetFirst: '目标优先',
    toComponents: '目标组件',
    type: '类型',
    unitDependencyGraphLabel: '选中单元的依赖图',
    unitsCount: '单元',
    units: '单元',
    violations: '违规',
    yes: '是',
  },
};

function App() {
  const [initialReport] = useState(() => readEmbeddedReport());
  const [report, setReport] = useState(() => initialReport ?? fallbackReport);
  const [loadingState, setLoadingState] = useState(initialReport ? 'ready' : 'loading');
  const [query, setQuery] = useState('');
  const [selectedComponentId, setSelectedComponentId] = useState(null);
  const [selectedUnitId, setSelectedUnitId] = useState(null);
  const [view, setView] = useState('violations');
  const [dependencyDirection, setDependencyDirection] = useState('source');
  const [dependencyStatus, setDependencyStatus] = useState('all');
  const [sourceComponentIds, setSourceComponentIds] = useState([]);
  const [targetComponentIds, setTargetComponentIds] = useState([]);
  const [locale, setLocale] = useState(() => localStorage.getItem('phpca-report-locale') || 'ru');
  const t = useMemo(() => (key) => dictionaries[locale]?.[key] ?? dictionaries.en[key] ?? key, [locale]);

  useEffect(() => {
    localStorage.setItem('phpca-report-locale', locale);
    document.documentElement.lang = localeToIntl[locale];
  }, [locale]);

  useEffect(() => {
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
        setSelectedComponentId(data.components[0]?.id ?? null);
        setSelectedUnitId(data.units[0]?.id ?? null);
        setLoadingState('ready');
      })
      .catch(() => setLoadingState('failed'));
  }, [initialReport]);

  const indexed = useMemo(() => buildIndex(report), [report]);
  const dependencyComponentOptions = useMemo(() => buildDependencyComponentOptions(report, indexed), [indexed, report]);
  const selectedComponent = indexed.componentsById.get(selectedComponentId) ?? report.components[0] ?? null;
  const selectedUnit = indexed.unitsById.get(selectedUnitId) ?? null;
  const selectedComponentUnits = useMemo(
    () => report.units.filter((unit) => !selectedComponent || unit.componentId === selectedComponent.id),
    [report.units, selectedComponent],
  );
  const visibleUnits = useMemo(
    () => selectedComponentUnits.filter((unit) => matchesUnit(unit, query)),
    [query, selectedComponentUnits],
  );
  const visibleViolations = useMemo(
    () => report.violations
      .filter((violation) => !selectedComponent || violation.fromComponentId === selectedComponent.id)
      .filter((violation) => matchesViolation(violation, indexed, query)),
    [indexed, query, report.violations, selectedComponent],
  );

  useEffect(() => {
    if (selectedUnit && selectedUnit.componentId === selectedComponent?.id && matchesUnit(selectedUnit, query)) {
      return;
    }

    setSelectedUnitId(visibleUnits[0]?.id ?? null);
  }, [query, selectedComponent?.id, selectedUnit, visibleUnits]);

  useEffect(() => {
    const componentIds = dependencyComponentOptions.map((component) => component.id);
    setSourceComponentIds(componentIds);
    setTargetComponentIds(componentIds);
  }, [dependencyComponentOptions]);

  const selectComponent = (componentId) => {
    setSelectedComponentId(componentId);
    setSelectedUnitId(null);
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
        <SummaryGrid summary={report.summary} t={t} />
        <div className="component-list">
          {report.components.map((component) => (
            <button
              className={component.id === selectedComponent?.id ? 'component-row selected' : 'component-row'}
              key={component.id}
              onClick={() => selectComponent(component.id)}
              type="button"
            >
              <ComponentStatus component={component} violations={indexed.violationsByComponent.get(component.id) ?? []} />
              <span>{component.name}</span>
              <small>{component.metrics.units}</small>
            </button>
          ))}
        </div>
      </aside>

      <section className="workspace">
        <header className="toolbar">
          <div className="title-block">
            <span>{formatDate(report.generatedAt, locale)}</span>
            <h1>{selectedComponent?.name ?? t('noComponents')}</h1>
          </div>
          <div className="toolbar-actions">
            <label className="search-box">
              <Search size={16} />
              <input
                onChange={(event) => setQuery(event.target.value)}
                placeholder={t('searchPlaceholder')}
                aria-label={t('openSearch')}
                type="search"
                value={query}
              />
              {query && (
                <button aria-label={t('clearSearch')} onClick={() => setQuery('')} type="button">
                  <X size={16} />
                </button>
              )}
            </label>
          </div>
        </header>

        <section className="workbench">
          <div className="workbench-main">
            <nav className="tabs" aria-label={t('reportView')}>
              <button className={view === 'violations' ? 'active' : ''} onClick={() => setView('violations')} type="button">
                <ShieldAlert size={16} />
                {t('violations')}
                <strong>{visibleViolations.length}</strong>
              </button>
              <button className={view === 'units' ? 'active' : ''} onClick={() => setView('units')} type="button">
                <FileCode2 size={16} />
                {t('units')}
                <strong>{visibleUnits.length}</strong>
              </button>
              <button className={view === 'dependencies' ? 'active' : ''} onClick={() => setView('dependencies')} type="button">
                <ArrowRight size={16} />
                {t('dependencies')}
                <strong>{report.dependencies.length}</strong>
              </button>
            </nav>

            {view === 'violations' && <ViolationsTable violations={visibleViolations} indexed={indexed} onSelectUnit={selectUnit} t={t} />}
            {view === 'units' && <UnitsTable units={visibleUnits} selectedUnitId={selectedUnit?.id} onSelectUnit={selectUnit} t={t} />}
            {view === 'dependencies' && (
              <DependencyExplorer
                components={dependencyComponentOptions}
                dependencies={report.dependencies}
                direction={dependencyDirection}
                indexed={indexed}
                onDirectionChange={setDependencyDirection}
                onSelectUnit={selectUnit}
                onSourceComponentsChange={setSourceComponentIds}
                onStatusChange={setDependencyStatus}
                onTargetComponentsChange={setTargetComponentIds}
                query={query}
                sourceComponentIds={sourceComponentIds}
                status={dependencyStatus}
                t={t}
                targetComponentIds={targetComponentIds}
              />
            )}
          </div>
          <aside className="unit-inspector">
            <UnitDetail unit={selectedUnit} indexed={indexed} onSelectUnit={selectUnit} t={t} />
          </aside>
        </section>

        <section className="overview-grid" aria-label={t('overview')}>
          <MetricPanel component={selectedComponent} t={t} />
          <AIMatrix components={report.components} selectedComponentId={selectedComponent?.id} onSelectComponent={selectComponent} t={t} />
          <DistanceRanking components={report.components} selectedComponentId={selectedComponent?.id} onSelectComponent={selectComponent} t={t} />
          <FanPanel component={selectedComponent} indexed={indexed} onSelectComponent={selectComponent} t={t} />
        </section>

        <details className="component-map">
          <summary>
            <GitBranch size={16} />
            <span>{t('dependencyOverview')}</span>
          </summary>
          <ComponentGraphPanel component={selectedComponent} report={report} indexed={indexed} onSelectComponent={selectComponent} t={t} />
        </details>
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
    [t('abstractness'), component.metrics.abstractness],
    [t('instability'), component.metrics.instability],
    [t('distance'), component.metrics.distance],
    [t('primitive'), component.metrics.primitiveness],
  ];

  return (
    <section className="panel metrics-panel">
      <h2>{t('componentMetrics')}</h2>
      {metrics.map(([label, value]) => (
        <div className="metric" key={label}>
          <div>
            <span>{label}</span>
            <strong>{formatRate(value)}</strong>
          </div>
          <meter max="1" min="0" value={value} />
        </div>
      ))}
    </section>
  );
}

function AIMatrix({ components, selectedComponentId, onSelectComponent, t }) {
  return (
    <section className="panel chart-panel">
      <h2>{t('aiMatrix')}</h2>
      <svg viewBox="0 0 360 240" role="img" aria-label={t('aiMatrix')}>
        <path className="chart-zone pain" d="M40 200 L40 122 A78 78 0 0 1 118 200 Z" />
        <path className="chart-zone uselessness" d="M320 24 L320 102 A78 78 0 0 1 242 24 Z" />
        <text className="zone-label pain" textAnchor="middle" transform="translate(96 184) rotate(45)">{t('zoneOfPain')}</text>
        <text className="zone-label uselessness" textAnchor="middle" transform="translate(284 62) rotate(45)">{t('zoneOfUselessness')}</text>
        <line className="chart-grid strong" x1="40" x2="320" y1="200" y2="200" />
        <line className="chart-grid strong" x1="40" x2="40" y1="24" y2="200" />
        {[0.25, 0.5, 0.75, 1].map((value) => (
          <g key={value}>
            <line className="chart-grid" x1={40 + value * 280} x2={40 + value * 280} y1="24" y2="200" />
            <line className="chart-grid" x1="40" x2="320" y1={200 - value * 176} y2={200 - value * 176} />
          </g>
        ))}
        <line className="main-sequence" x1="40" x2="320" y1="24" y2="200" />
        {components.map((component, index) => {
          const x = 40 + component.metrics.instability * 280;
          const y = 200 - component.metrics.abstractness * 176;
          const labelX = x > 260 ? x - 10 : x + 10;
          const labelY = y + ((index % 3) - 1) * 12;
          return (
            <g
              className={component.id === selectedComponentId ? 'chart-point selected' : 'chart-point'}
              key={component.id}
              onClick={() => onSelectComponent(component.id)}
              role="button"
              tabIndex="0"
            >
              <circle cx={x} cy={y} r={component.id === selectedComponentId ? 9 : 7} />
              <text textAnchor={x > 260 ? 'end' : 'start'} x={labelX} y={labelY}>{truncate(component.name, 16)}</text>
            </g>
          );
        })}
        <text className="axis-label" x="170" y="228">{t('instability')}</text>
        <text className="axis-label vertical" x="-140" y="14">{t('abstractness')}</text>
      </svg>
    </section>
  );
}

function DistanceRanking({ components, selectedComponentId, onSelectComponent, t }) {
  const sortedComponents = [...components].sort((left, right) => right.metrics.distance - left.metrics.distance);

  return (
    <section className="panel chart-panel">
      <h2>{t('distanceRanking')}</h2>
      <div className="ranking-list">
        {sortedComponents.map((component) => (
          <button
            className={component.id === selectedComponentId ? 'ranking-row selected' : 'ranking-row'}
            key={component.id}
            onClick={() => onSelectComponent(component.id)}
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

function ComponentGraphPanel({ component, report, indexed, onSelectComponent, t }) {
  const graph = useMemo(() => componentGraph(component, report, indexed), [component, indexed, report]);

  return (
    <section className="panel graph-panel">
      <h2>{t('componentGraph')}</h2>
      <svg viewBox="0 0 720 320" role="img" aria-label={t('dependencyGraphLabel')}>
        <defs>
          <marker id="arrow" markerHeight="8" markerWidth="8" orient="auto" refX="8" refY="4">
            <path d="M0,0 L8,4 L0,8 Z" />
          </marker>
          <marker id="arrow-problem" markerHeight="8" markerWidth="8" orient="auto" refX="8" refY="4">
            <path d="M0,0 L8,4 L0,8 Z" />
          </marker>
        </defs>
        {graph.edges.map((edge) => (
          <g key={edge.id}>
            <line
              className={edge.isProblem ? 'edge problem' : 'edge'}
              markerEnd={edge.isProblem ? 'url(#arrow-problem)' : 'url(#arrow)'}
              x1={edge.from.x}
              x2={edge.to.x}
              y1={edge.from.y}
              y2={edge.to.y}
            />
            <text className="edge-label" x={(edge.from.x + edge.to.x) / 2} y={(edge.from.y + edge.to.y) / 2 - 6}>{edge.weight}</text>
          </g>
        ))}
        {graph.nodes.map((node) => {
          const violations = indexed.violationsByComponent.get(node.id) ?? [];
          return (
            <g
              className={node.id === component?.id ? 'node selected' : 'node'}
              key={node.id}
              onClick={() => onSelectComponent(node.id)}
              role="button"
              tabIndex="0"
              transform={`translate(${node.x} ${node.y})`}
            >
              <circle r="38" />
              <text textAnchor="middle" y="-3">{truncate(node.name, 14)}</text>
              <text className="node-meta" textAnchor="middle" y="15">{violations.length ? `${violations.length} ${t('issues')}` : `${node.units} ${t('unitsCount')}`}</text>
            </g>
          );
        })}
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
  const max = Math.max(...items.map((item) => item.weight), 1);

  return (
    <div className="fan-list">
      <h3>{title}</h3>
      {items.length ? items.map((item) => {
        const componentId = direction === 'to' ? item.toComponentId : item.fromComponentId;
        const component = indexed.componentsById.get(componentId);
        const componentName = component?.name ?? (direction === 'to' ? item.toComponentName : item.fromComponentName);
        return (
          <button
            className={component ? 'fan-row' : 'fan-row disabled'}
            disabled={!component}
            key={item.id}
            onClick={() => component && onSelectComponent(componentId)}
            type="button"
          >
            <span>{componentName}</span>
            <strong>{item.weight}</strong>
            <i style={{ width: `${Math.max((item.weight / max) * 100, 4)}%` }} />
          </button>
        );
      }) : <p>{t('noExternalDependencies')}</p>}
    </div>
  );
}

function ViolationsTable({ violations, indexed, onSelectUnit, t }) {
  if (!violations.length) {
    return <EmptyState icon={<CheckCircle2 size={22} />} title={t('noActiveIssues')} />;
  }

  return (
    <section className="table-shell">
      {violations.map((violation) => {
        const dependency = indexed.dependenciesById.get(violation.dependencyId);
        return (
          <article className="issue-row" key={violation.id}>
            <AlertTriangle size={18} />
            <div>
              <strong>{violationMessage(violation, dependency, t)}</strong>
              <DependencyEndpoints dependency={dependency} onSelectUnit={onSelectUnit} t={t} />
            </div>
            <mark>{violation.status === 'allowed-state' ? t('allowedState') : violation.type}</mark>
          </article>
        );
      })}
    </section>
  );
}

function UnitsTable({ units, selectedUnitId, onSelectUnit, t }) {
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
            <th>{t('in')}</th>
            <th>{t('out')}</th>
          </tr>
        </thead>
        <tbody>
          {units.map((unit) => (
            <tr className={unit.id === selectedUnitId ? 'selected-row' : ''} key={unit.id} onClick={() => onSelectUnit(unit.id)}>
              <td>
                <strong>{unit.shortName}</strong>
                <span title={unit.name}>{compactNamespace(unit.name, unit.shortName)}</span>
              </td>
              <td>{unit.type}</td>
              <td>{unit.isPublic ? t('publicApi') : t('privateApi')}</td>
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
  components,
  dependencies,
  direction,
  indexed,
  onDirectionChange,
  onSelectUnit,
  onSourceComponentsChange,
  onStatusChange,
  onTargetComponentsChange,
  query,
  sourceComponentIds,
  status,
  t,
  targetComponentIds,
}) {
  const filteredDependencies = useMemo(() => {
    const sourceIds = new Set(sourceComponentIds);
    const targetIds = new Set(targetComponentIds);

    return dependencies
      .filter((dependency) => sourceIds.has(dependency.fromComponentId))
      .filter((dependency) => targetIds.has(dependency.toComponentId))
      .filter((dependency) => status === 'all' || dependencyStatusKey(dependency) === status)
      .filter((dependency) => matchesDependencyWithUnits(dependency, indexed, query));
  }, [dependencies, indexed, query, sourceComponentIds, status, targetComponentIds]);

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
              <ChevronsLeftRight size={16} />
              {direction === 'source' ? t('sourceFirst') : t('targetFirst')}
            </button>
          </div>
          <SegmentedFilter
            label={t('dependencyStatus')}
            onChange={onStatusChange}
            options={[
              ['all', t('all')],
              ['blocked', t('blocked')],
              ['allowed-state', t('allowedState')],
              ['internal', t('internal')],
              ['allowed', t('allowed')],
            ]}
            value={status}
          />
          <ComponentFilter
            components={components}
            label={t('fromComponents')}
            onChange={onSourceComponentsChange}
            selectedIds={sourceComponentIds}
            t={t}
          />
          <ComponentFilter
            components={components}
            label={t('toComponents')}
            onChange={onTargetComponentsChange}
            selectedIds={targetComponentIds}
            t={t}
          />
        </div>
      </details>

      <header className="dependency-explorer-header">
        <div>
          <h2>{t('dependencyGroups')}</h2>
          <span>{groups.length} {t('dependencyGroups')} · {filteredDependencies.length} {t('dependencyRows')}</span>
        </div>
      </header>

      {groups.length ? (
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
                  <DependencyGroupCard group={group} onSelectUnit={onSelectUnit} t={t} />
                </div>
              );
            })}
          </div>
        </div>
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

function ComponentFilter({ components, label, selectedIds, onChange, t }) {
  const selected = new Set(selectedIds);
  const allSelected = selectedIds.length === components.length;

  const toggleComponent = (componentId) => {
    const next = new Set(selected);
    if (next.has(componentId)) {
      next.delete(componentId);
    } else {
      next.add(componentId);
    }
    onChange([...next]);
  };

  return (
    <div className="filter-block component-filter">
      <div className="filter-heading">
        <span>{label}</span>
        <button onClick={() => onChange(components.map((component) => component.id))} type="button">{t('selectAll')}</button>
      </div>
      <div className="component-filter-options">
        {components.map((component) => (
          <label className={selected.has(component.id) ? 'checked' : ''} key={component.id}>
            <input checked={selected.has(component.id)} onChange={() => toggleComponent(component.id)} type="checkbox" />
            <span>{component.name}</span>
          </label>
        ))}
      </div>
      <small>{selectedIds.length}/{components.length} {allSelected ? t('all') : ''}</small>
    </div>
  );
}

function DependencyGroupCard({ group, onSelectUnit, t }) {
  return (
    <details className="dependency-group-card">
      <summary>
        <CircleDot size={16} />
        <div>
          <strong>{group.primaryName} {'->'} {group.secondaryName}</strong>
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
      {group.counts.blocked > 0 && <mark className="problem">{group.counts.blocked} {t('blocked')}</mark>}
      {group.counts.allowedState > 0 && <mark>{group.counts.allowedState} {t('allowedState')}</mark>}
      {group.counts.internal > 0 && <mark>{group.counts.internal} {t('internal')}</mark>}
      {group.counts.allowed > 0 && <mark>{group.counts.allowed} {t('allowed')}</mark>}
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

  return (
    <details className="dependency-file-group">
      <summary>
        <FileCode2 size={15} />
        <span>{file.name}</span>
        <small>{file.dependencies.length} {t('dependencyRows')}</small>
      </summary>
      <div className="dependency-file-rows">
        {visibleDependencies.map((dependency) => (
          <article className={dependencyStatusKey(dependency) === 'blocked' ? 'dependency-row problem' : 'dependency-row'} key={dependency.id}>
            <CircleDot size={16} />
            <div>
              <strong>{dependency.fromComponentName} {'->'} {dependency.toComponentName}</strong>
              <DependencyEndpoints dependency={dependency} onSelectUnit={onSelectUnit} t={t} />
            </div>
            <mark>{dependencyStatusLabel(dependency, t)}</mark>
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
      <button onClick={() => onSelectUnit(dependency.fromUnitId)} title={dependency.fromUnitName} type="button">{shortUnitName(dependency.fromUnitName)}</button>
      <span>{'->'}</span>
      <button onClick={() => onSelectUnit(dependency.toUnitId)} title={dependency.toUnitName} type="button">{shortUnitName(dependency.toUnitName)}</button>
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
          <Fact label={t('type')} value={unit.type} />
          <Fact label={t('api')} value={unit.isPublic ? t('publicApi') : t('privateApi')} />
          <Fact label={t('abstract')} value={unit.isAbstract === null ? t('notApplicable') : unit.isAbstract ? t('yes') : t('no')} />
          <Fact label={t('instability')} value={formatRate(unit.metrics.instability)} />
          <Fact label={t('primitive')} value={formatRate(unit.metrics.primitiveness)} />
          <Fact label={t('path')} value={unit.path ?? t('unknown')} copyable />
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

function Fact({ label, value, copyable = false }) {
  const copyValue = () => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(String(value));
    }
  };

  return (
    <div className="fact">
      <span>{label}</span>
      <strong title={String(value)}>{value}</strong>
      {copyable && (
        <button aria-label="Copy" onClick={copyValue} type="button">
          <Copy size={14} />
        </button>
      )}
    </div>
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
          return (
            <g key={item.dependency.id}>
              <line className="edge" x1={isIncoming ? x : center.x} x2={isIncoming ? center.x : x} y1={isIncoming ? y : center.y} y2={isIncoming ? center.y : y} />
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
        return (
          <button className="unit-dependency-row" key={dependency.id} onClick={() => onSelectUnit(unitId)} type="button">
            <span title={unitName}>{shortUnitName(unitName)}</span>
            <mark>{componentName}</mark>
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
        isProblem: false,
      };
      edge.weight += 1;
      edge.isProblem = edge.isProblem || !dependency.isComponentAllowed || !dependency.isTargetPublic;
      componentEdges.set(key, edge);
    }
  }

  for (const violation of report.violations) {
    pushMapList(violationsByComponent, violation.fromComponentId, violation);
  }

  const outgoingComponentEdges = new Map();
  const incomingComponentEdges = new Map();
  for (const edge of componentEdges.values()) {
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
    componentEdges: [...componentEdges.values()],
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

function componentGraph(component, report, indexed) {
  if (!report.components.length) {
    return { nodes: [], edges: [] };
  }

  const nodes = report.components.map((item, index) => {
    const angle = (Math.PI * 2 * index) / report.components.length - Math.PI / 2;
    return {
      id: item.id,
      name: item.name,
      units: item.metrics.units,
      x: 360 + Math.cos(angle) * 260,
      y: 160 + Math.sin(angle) * 104,
    };
  });
  const nodesById = new Map(nodes.map((node) => [node.id, node]));
  const selectedEdges = component
    ? indexed.componentEdges.filter((edge) => edge.fromComponentId === component.id || edge.toComponentId === component.id)
    : indexed.componentEdges;
  const edges = selectedEdges
    .map((edge) => ({ ...edge, from: nodesById.get(edge.fromComponentId), to: nodesById.get(edge.toComponentId) }))
    .filter((edge) => edge.from && edge.to);

  return { nodes, edges };
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

function normalizeQuery(query) {
  return query.trim().toLowerCase();
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

function formatRate(value) {
  return Number(value ?? 0).toFixed(3);
}

function formatDate(value, locale) {
  if (!value) {
    return dictionaries[locale]?.generatedReport ?? dictionaries.en.generatedReport;
  }
  return new Intl.DateTimeFormat(localeToIntl[locale] ?? localeToIntl.en, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function dependencyStatusLabel(dependency, t) {
  if (dependency.isInternal) {
    return t('internal');
  }
  if (dependency.isAllowedState) {
    return t('allowedState');
  }
  return dependency.isComponentAllowed && dependency.isTargetPublic ? t('allowed') : t('blocked');
}

function dependencyStatusKey(dependency) {
  if (dependency.isInternal) {
    return 'internal';
  }
  if (dependency.isAllowedState) {
    return 'allowed-state';
  }
  return dependency.isComponentAllowed && dependency.isTargetPublic ? 'allowed' : 'blocked';
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
      counts: { allowed: 0, allowedState: 0, blocked: 0, internal: 0 },
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

function addDependencyToTree(root, dependency, indexed, direction) {
  const path = primaryUnitPath(dependency, indexed, direction);
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
    file = { id: `${node.id}/${fileName}`, name: fileName, dependencies: [] };
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
