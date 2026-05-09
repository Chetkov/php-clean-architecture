import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import {
  AlertTriangle,
  ArrowRight,
  Box,
  CheckCircle2,
  CircleDot,
  FileCode2,
  GitBranch,
  Layers3,
  Search,
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

function App() {
  const [report, setReport] = useState(fallbackReport);
  const [loadingState, setLoadingState] = useState('loading');
  const [query, setQuery] = useState('');
  const [selectedComponentId, setSelectedComponentId] = useState(null);
  const [selectedUnitId, setSelectedUnitId] = useState(null);
  const [view, setView] = useState('violations');

  useEffect(() => {
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
  }, []);

  const indexed = useMemo(() => buildIndex(report), [report]);
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
  const visibleDependencies = useMemo(
    () => report.dependencies
      .filter((dependency) => !selectedComponent || dependency.fromComponentId === selectedComponent.id || dependency.toComponentId === selectedComponent.id)
      .filter((dependency) => matchesDependency(dependency, query)),
    [query, report.dependencies, selectedComponent],
  );

  useEffect(() => {
    if (selectedUnit && selectedUnit.componentId === selectedComponent?.id) {
      return;
    }

    setSelectedUnitId(selectedComponentUnits[0]?.id ?? null);
  }, [selectedComponent?.id, selectedComponentUnits, selectedUnit]);

  const selectComponent = (componentId) => {
    setSelectedComponentId(componentId);
    setSelectedUnitId(null);
  };
  const selectUnit = (unitId) => {
    const unit = indexed.unitsById.get(unitId);
    if (unit) {
      setSelectedComponentId(unit.componentId);
    }
    setSelectedUnitId(unitId);
    setView('units');
  };

  if (loadingState === 'loading') {
    return <ShellMessage title="Loading report" text="Reading report.json..." />;
  }

  if (loadingState === 'failed') {
    return <ShellMessage title="Report data unavailable" text="report.json was not found near index.html." />;
  }

  return (
    <main className="app-shell">
      <aside className="sidebar">
        <div className="brand">
          <GitBranch size={24} />
          <div>
            <strong>PHPCA</strong>
            <span>Architecture report</span>
          </div>
        </div>
        <SummaryGrid summary={report.summary} />
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
            <span>{formatDate(report.generatedAt)}</span>
            <h1>{selectedComponent?.name ?? 'No components'}</h1>
          </div>
          <label className="search-box">
            <Search size={16} />
            <input
              onChange={(event) => setQuery(event.target.value)}
              placeholder="Search components, units, paths or dependencies"
              type="search"
              value={query}
            />
          </label>
        </header>

        <section className="overview-grid">
          <MetricPanel component={selectedComponent} />
          <AIMatrix components={report.components} selectedComponentId={selectedComponent?.id} onSelectComponent={selectComponent} />
          <DistanceRanking components={report.components} selectedComponentId={selectedComponent?.id} onSelectComponent={selectComponent} />
        </section>

        <section className="relationship-grid">
          <ComponentGraphPanel component={selectedComponent} report={report} indexed={indexed} onSelectComponent={selectComponent} />
          <FanPanel component={selectedComponent} indexed={indexed} onSelectComponent={selectComponent} />
        </section>

        <nav className="tabs" aria-label="Report view">
          <button className={view === 'violations' ? 'active' : ''} onClick={() => setView('violations')} type="button">
            <ShieldAlert size={16} />
            Violations
          </button>
          <button className={view === 'units' ? 'active' : ''} onClick={() => setView('units')} type="button">
            <FileCode2 size={16} />
            Units
          </button>
          <button className={view === 'dependencies' ? 'active' : ''} onClick={() => setView('dependencies')} type="button">
            <ArrowRight size={16} />
            Dependencies
          </button>
        </nav>

        {view === 'violations' && <ViolationsTable violations={visibleViolations} indexed={indexed} onSelectUnit={selectUnit} />}
        {view === 'units' && <UnitsTable units={visibleUnits} selectedUnitId={selectedUnit?.id} onSelectUnit={selectUnit} />}
        {view === 'dependencies' && <DependenciesTable dependencies={visibleDependencies} onSelectUnit={selectUnit} />}

        <UnitDetail unit={selectedUnit} indexed={indexed} onSelectUnit={selectUnit} />
      </section>
    </main>
  );
}

function SummaryGrid({ summary }) {
  const items = [
    ['Components', summary.components, <Layers3 size={18} />],
    ['Units', summary.units, <FileCode2 size={18} />],
    ['Dependencies', summary.dependencies, <ArrowRight size={18} />],
    ['Active issues', summary.activeViolations, <ShieldAlert size={18} />],
  ];

  return (
    <div className="summary-grid">
      {items.map(([label, value, icon]) => (
        <div className="summary-item" key={label}>
          {icon}
          <span>{label}</span>
          <strong>{value}</strong>
        </div>
      ))}
    </div>
  );
}

function MetricPanel({ component }) {
  if (!component) {
    return <section className="panel">No component data</section>;
  }

  const metrics = [
    ['Abstractness', component.metrics.abstractness],
    ['Instability', component.metrics.instability],
    ['Distance', component.metrics.distance],
    ['Primitiveness', component.metrics.primitiveness],
  ];

  return (
    <section className="panel metrics-panel">
      <h2>Component metrics</h2>
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

function AIMatrix({ components, selectedComponentId, onSelectComponent }) {
  return (
    <section className="panel chart-panel">
      <h2>A/I matrix</h2>
      <svg viewBox="0 0 360 240" role="img" aria-label="Abstractness instability matrix">
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
        <text className="axis-label" x="170" y="228">Instability</text>
        <text className="axis-label vertical" x="-140" y="14">Abstractness</text>
      </svg>
    </section>
  );
}

function DistanceRanking({ components, selectedComponentId, onSelectComponent }) {
  const sortedComponents = [...components].sort((left, right) => right.metrics.distance - left.metrics.distance);

  return (
    <section className="panel chart-panel">
      <h2>Distance ranking</h2>
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

function ComponentGraphPanel({ component, report, indexed, onSelectComponent }) {
  const graph = useMemo(() => componentGraph(component, report, indexed), [component, indexed, report]);

  return (
    <section className="panel graph-panel">
      <h2>Component graph</h2>
      <svg viewBox="0 0 720 320" role="img" aria-label="Component dependency graph">
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
              <text className="node-meta" textAnchor="middle" y="15">{violations.length ? `${violations.length} issues` : `${node.units} units`}</text>
            </g>
          );
        })}
      </svg>
    </section>
  );
}

function FanPanel({ component, indexed, onSelectComponent }) {
  if (!component) {
    return <section className="panel fan-panel">No dependency distribution</section>;
  }

  const outgoing = indexed.outgoingComponentEdges.get(component.id) ?? [];
  const incoming = indexed.incomingComponentEdges.get(component.id) ?? [];

  return (
    <section className="panel fan-panel">
      <h2>Component dependency distribution</h2>
      <FanList title="Outgoing" items={outgoing} direction="to" indexed={indexed} onSelectComponent={onSelectComponent} />
      <FanList title="Incoming" items={incoming} direction="from" indexed={indexed} onSelectComponent={onSelectComponent} />
    </section>
  );
}

function FanList({ title, items, direction, indexed, onSelectComponent }) {
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
      }) : <p>No external component dependencies</p>}
    </div>
  );
}

function ViolationsTable({ violations, indexed, onSelectUnit }) {
  if (!violations.length) {
    return <EmptyState icon={<CheckCircle2 size={22} />} title="No active architecture issues for the current filters" />;
  }

  return (
    <section className="table-shell">
      {violations.map((violation) => {
        const dependency = indexed.dependenciesById.get(violation.dependencyId);
        return (
          <article className="issue-row" key={violation.id}>
            <AlertTriangle size={18} />
            <div>
              <strong>{violation.message}</strong>
              <DependencyEndpoints dependency={dependency} onSelectUnit={onSelectUnit} />
            </div>
            <mark>{violation.status === 'allowed-state' ? 'allowed state' : violation.type}</mark>
          </article>
        );
      })}
    </section>
  );
}

function UnitsTable({ units, selectedUnitId, onSelectUnit }) {
  if (!units.length) {
    return <EmptyState icon={<FileCode2 size={22} />} title="No units match the current filters" />;
  }

  return (
    <section className="table-shell">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>API</th>
            <th>In</th>
            <th>Out</th>
          </tr>
        </thead>
        <tbody>
          {units.map((unit) => (
            <tr className={unit.id === selectedUnitId ? 'selected-row' : ''} key={unit.id} onClick={() => onSelectUnit(unit.id)}>
              <td>
                <strong>{unit.shortName}</strong>
                <span>{unit.name}</span>
              </td>
              <td>{unit.type}</td>
              <td>{unit.isPublic ? 'public' : 'private'}</td>
              <td>{unit.metrics.incoming}</td>
              <td>{unit.metrics.outgoing}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}

function DependenciesTable({ dependencies, onSelectUnit }) {
  if (!dependencies.length) {
    return <EmptyState icon={<ArrowRight size={22} />} title="No dependencies match the current filters" />;
  }

  return (
    <section className="table-shell">
      {dependencies.map((dependency) => (
        <article className={dependency.isComponentAllowed && dependency.isTargetPublic ? 'dependency-row' : 'dependency-row problem'} key={dependency.id}>
          <CircleDot size={16} />
          <div>
            <strong>{dependency.fromComponentName} {'->'} {dependency.toComponentName}</strong>
            <DependencyEndpoints dependency={dependency} onSelectUnit={onSelectUnit} />
          </div>
          <mark>{dependency.isInternal ? 'internal' : dependency.isAllowedState ? 'allowed state' : dependency.isComponentAllowed ? 'allowed' : 'blocked'}</mark>
        </article>
      ))}
    </section>
  );
}

function DependencyEndpoints({ dependency, onSelectUnit }) {
  if (!dependency) {
    return <span>Unknown dependency</span>;
  }

  return (
    <span className="dependency-endpoints">
      <button onClick={() => onSelectUnit(dependency.fromUnitId)} type="button">{dependency.fromUnitName}</button>
      <span>{'->'}</span>
      <button onClick={() => onSelectUnit(dependency.toUnitId)} type="button">{dependency.toUnitName}</button>
    </span>
  );
}

function UnitDetail({ unit, indexed, onSelectUnit }) {
  if (!unit) {
    return null;
  }

  const outgoing = indexed.dependenciesByFromUnit.get(unit.id) ?? [];
  const incoming = indexed.dependenciesByToUnit.get(unit.id) ?? [];

  return (
    <section className="panel unit-detail">
      <header>
        <div>
          <span>Selected unit</span>
          <h2>{unit.shortName}</h2>
          <p>{unit.name}</p>
        </div>
        <mark>{unit.componentName}</mark>
      </header>
      <div className="unit-detail-grid">
        <div className="unit-facts">
          <Fact label="Type" value={unit.type} />
          <Fact label="API" value={unit.isPublic ? 'public' : 'private'} />
          <Fact label="Abstract" value={unit.isAbstract === null ? 'n/a' : unit.isAbstract ? 'yes' : 'no'} />
          <Fact label="Instability" value={formatRate(unit.metrics.instability)} />
          <Fact label="Primitiveness" value={formatRate(unit.metrics.primitiveness)} />
          <Fact label="Path" value={unit.path ?? 'unknown'} />
        </div>
        <UnitGraph unit={unit} incoming={incoming} outgoing={outgoing} indexed={indexed} onSelectUnit={onSelectUnit} />
      </div>
      <div className="unit-dependency-grid">
        <UnitDependencyList title="Outgoing dependencies" dependencies={outgoing} side="to" onSelectUnit={onSelectUnit} />
        <UnitDependencyList title="Incoming dependencies" dependencies={incoming} side="from" onSelectUnit={onSelectUnit} />
      </div>
    </section>
  );
}

function Fact({ label, value }) {
  return (
    <div className="fact">
      <span>{label}</span>
      <strong>{value}</strong>
    </div>
  );
}

function UnitGraph({ unit, incoming, outgoing, indexed, onSelectUnit }) {
  const related = [
    ...incoming.slice(0, 5).map((dependency, index) => ({ dependency, id: dependency.fromUnitId, angle: Math.PI + (index - 2) * 0.32 })),
    ...outgoing.slice(0, 5).map((dependency, index) => ({ dependency, id: dependency.toUnitId, angle: (index - 2) * 0.32 })),
  ];
  const center = { x: 260, y: 130 };

  return (
    <div className="unit-graph">
      <svg viewBox="0 0 520 260" role="img" aria-label="Selected unit dependency graph">
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

function UnitDependencyList({ title, dependencies, side, onSelectUnit }) {
  return (
    <div className="unit-dependency-list">
      <h3>{title}</h3>
      {dependencies.length ? dependencies.map((dependency) => {
        const unitId = side === 'to' ? dependency.toUnitId : dependency.fromUnitId;
        const unitName = side === 'to' ? dependency.toUnitName : dependency.fromUnitName;
        const componentName = side === 'to' ? dependency.toComponentName : dependency.fromComponentName;
        return (
          <button className="unit-dependency-row" key={dependency.id} onClick={() => onSelectUnit(unitId)} type="button">
            <span>{unitName}</span>
            <mark>{componentName}</mark>
          </button>
        );
      }) : <p>No dependencies</p>}
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

function formatRate(value) {
  return Number(value ?? 0).toFixed(3);
}

function formatDate(value) {
  if (!value) {
    return 'Generated report';
  }
  return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function truncate(value, maxLength) {
  return value.length > maxLength ? `${value.slice(0, maxLength - 1)}...` : value;
}

createRoot(document.getElementById('root')).render(<App />);
