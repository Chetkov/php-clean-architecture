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
        setLoadingState('ready');
      })
      .catch(() => setLoadingState('failed'));
  }, []);

  const indexed = useMemo(() => buildIndex(report), [report]);
  const selectedComponent = indexed.componentsById.get(selectedComponentId) ?? report.components[0] ?? null;
  const filteredUnits = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();
    return report.units.filter((unit) => {
      const matchesComponent = !selectedComponent || unit.componentId === selectedComponent.id;
      const matchesQuery = !normalizedQuery
        || unit.name.toLowerCase().includes(normalizedQuery)
        || (unit.path ?? '').toLowerCase().includes(normalizedQuery);
      return matchesComponent && matchesQuery;
    });
  }, [query, report.units, selectedComponent]);

  const selectedViolations = useMemo(() => {
    if (!selectedComponent) {
      return report.violations;
    }
    return report.violations.filter((violation) => violation.fromComponentId === selectedComponent.id);
  }, [report.violations, selectedComponent]);

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
              onClick={() => setSelectedComponentId(component.id)}
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
              placeholder="Search units or paths"
              type="search"
              value={query}
            />
          </label>
        </header>

        <section className="inspector-grid">
          <MetricPanel component={selectedComponent} />
          <GraphPanel component={selectedComponent} report={report} indexed={indexed} />
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

        {view === 'violations' && <ViolationsTable violations={selectedViolations} indexed={indexed} />}
        {view === 'units' && <UnitsTable units={filteredUnits} />}
        {view === 'dependencies' && <DependenciesTable dependencies={dependenciesForComponent(report, selectedComponent)} />}
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

function GraphPanel({ component, report, indexed }) {
  const graph = useMemo(() => componentGraph(component, report), [component, report]);

  return (
    <section className="panel graph-panel">
      <svg viewBox="0 0 680 280" role="img" aria-label="Component dependency graph">
        {graph.edges.map((edge) => (
          <g key={edge.id}>
            <line className={edge.isProblem ? 'edge problem' : 'edge'} x1={edge.from.x} x2={edge.to.x} y1={edge.from.y} y2={edge.to.y} />
            <circle className={edge.isProblem ? 'edge-arrow problem' : 'edge-arrow'} cx={edge.to.x} cy={edge.to.y} r="4" />
          </g>
        ))}
        {graph.nodes.map((node) => {
          const violations = indexed.violationsByComponent.get(node.id) ?? [];
          return (
            <g className={node.id === component?.id ? 'node selected' : 'node'} key={node.id} transform={`translate(${node.x} ${node.y})`}>
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

function ViolationsTable({ violations, indexed }) {
  if (!violations.length) {
    return <EmptyState icon={<CheckCircle2 size={22} />} title="No active architecture issues for this component" />;
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
              <span>{dependency?.fromUnitName} {'->'} {dependency?.toUnitName}</span>
            </div>
            <mark>{violation.status === 'allowed-state' ? 'allowed state' : violation.type}</mark>
          </article>
        );
      })}
    </section>
  );
}

function UnitsTable({ units }) {
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
            <tr key={unit.id}>
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

function DependenciesTable({ dependencies }) {
  if (!dependencies.length) {
    return <EmptyState icon={<ArrowRight size={22} />} title="No external dependencies for this component" />;
  }

  return (
    <section className="table-shell">
      {dependencies.map((dependency) => (
        <article className={dependency.isComponentAllowed && dependency.isTargetPublic ? 'dependency-row' : 'dependency-row problem'} key={dependency.id}>
          <CircleDot size={16} />
          <div>
            <strong>{dependency.fromComponentName} {'->'} {dependency.toComponentName}</strong>
            <span>{dependency.fromUnitName} {'->'} {dependency.toUnitName}</span>
          </div>
          <mark>{dependency.isAllowedState ? 'allowed state' : dependency.isComponentAllowed ? 'allowed' : 'blocked'}</mark>
        </article>
      ))}
    </section>
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
  const dependenciesById = new Map(report.dependencies.map((dependency) => [dependency.id, dependency]));
  const violationsByComponent = new Map();
  for (const violation of report.violations) {
    const list = violationsByComponent.get(violation.fromComponentId) ?? [];
    list.push(violation);
    violationsByComponent.set(violation.fromComponentId, list);
  }

  return { componentsById, dependenciesById, violationsByComponent };
}

function componentGraph(component, report) {
  if (!report.components.length) {
    return { nodes: [], edges: [] };
  }

  const nodes = report.components.map((item, index) => {
    const angle = (Math.PI * 2 * index) / report.components.length - Math.PI / 2;
    const radiusX = 240;
    const radiusY = 96;
    return {
      id: item.id,
      name: item.name,
      units: item.metrics.units,
      x: 340 + Math.cos(angle) * radiusX,
      y: 140 + Math.sin(angle) * radiusY,
    };
  });
  const nodesById = new Map(nodes.map((node) => [node.id, node]));
  const selectedDependencies = component
    ? report.dependencies.filter((dependency) => dependency.fromComponentId === component.id || dependency.toComponentId === component.id)
    : report.dependencies;
  const edgeKeys = new Set();
  const edges = [];

  for (const dependency of selectedDependencies) {
    const key = `${dependency.fromComponentId}->${dependency.toComponentId}`;
    if (edgeKeys.has(key) || dependency.fromComponentId === dependency.toComponentId) {
      continue;
    }
    const from = nodesById.get(dependency.fromComponentId);
    const to = nodesById.get(dependency.toComponentId);
    if (!from || !to) {
      continue;
    }
    edgeKeys.add(key);
    edges.push({ id: key, from, to, isProblem: !dependency.isComponentAllowed || !dependency.isTargetPublic });
  }

  return { nodes, edges };
}

function dependenciesForComponent(report, component) {
  if (!component) {
    return report.dependencies;
  }

  return report.dependencies.filter((dependency) => dependency.fromComponentId === component.id);
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
