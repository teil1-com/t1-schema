import React, { useState } from 'react';
import Dashboard from './components/Dashboard/BentoGrid';
import SchemaEditor from './components/Editor/SchemaBuilder';
import LocalSchemaEditor from './components/Editor/LocalSchemaEditor';
import PageBrowser from './components/Pages/PageBrowser';
import RulesList from './components/Rules/RulesList';
import RuleBuilder from './components/Rules/RuleBuilder';
import SiteStructure from './components/SiteMap/SiteStructure';
import HelpPanel from './components/Help/HelpPanel';

const TABS = {
  DASHBOARD: 'dashboard',
  RULES: 'rules',
  STRUCTURE: 'structure',
  PAGES: 'pages',
  HELP: 'help',
};

const VIEWS = {
  TAB: 'tab',
  GLOBAL_EDITOR: 'global_editor',
  LOCAL_EDITOR: 'local_editor',
  RULE_EDITOR: 'rule_editor',
};

/**
 * Post ID from `?t1_post=`, set by the post editor meta box link.
 */
function readLinkedPostId() {
  const id = Number.parseInt(new URLSearchParams(window.location.search).get('t1_post') ?? '', 10);
  return Number.isInteger(id) && id > 0 ? id : null;
}

export default function App() {
  const linkedPostId = readLinkedPostId();

  const [activeTab, setActiveTab] = useState(linkedPostId ? TABS.PAGES : TABS.DASHBOARD);
  const [view, setView] = useState(linkedPostId ? VIEWS.LOCAL_EDITOR : VIEWS.TAB);
  const [editingSchema, setEditingSchema] = useState(null);
  const [editingPost, setEditingPost] = useState(linkedPostId ? { id: linkedPostId } : null);
  const [editingRule, setEditingRule] = useState(null);

  const handleEditGlobal = (schema) => {
    setEditingSchema(schema);
    setView(VIEWS.GLOBAL_EDITOR);
  };

  const handleCreateGlobal = (type = null) => {
    setEditingSchema({ isNew: true, preselectedType: type });
    setView(VIEWS.GLOBAL_EDITOR);
  };

  const handleEditLocal = (post) => {
    setEditingPost(post);
    setView(VIEWS.LOCAL_EDITOR);
  };

  const handleEditRule = (rule) => {
    setEditingRule(rule);
    setView(VIEWS.RULE_EDITOR);
  };

  const handleCreateRule = (context = null) => {
    // Pre-fill condition from site structure click.
    let conditions = [{ type: '', value: '' }];
    if (context) {
      const parts = context.split(':');
      conditions = [{ type: parts[0], value: parts.slice(1).join(':') || '' }];
    }
    setEditingRule({ isNew: true, conditions });
    setView(VIEWS.RULE_EDITOR);
  };

  const handleBack = () => {
    setEditingSchema(null);
    setEditingPost(null);
    setEditingRule(null);
    setView(VIEWS.TAB);

    // Drop ?t1_post= so reloading after Back does not reopen the editor.
    const url = new URL(window.location.href);
    if (url.searchParams.has('t1_post')) {
      url.searchParams.delete('t1_post');
      window.history.replaceState({}, '', url);
    }
  };

  const tabs = [
    { id: TABS.DASHBOARD, label: wp.i18n.__( 'Globals', 'teil1-schema-manager' ), icon: '📊' },
    { id: TABS.RULES, label: wp.i18n.__( 'Rules', 'teil1-schema-manager' ), icon: '🎯' },
    { id: TABS.STRUCTURE, label: wp.i18n.__( 'Site Map', 'teil1-schema-manager' ), icon: '🗺️' },
    { id: TABS.PAGES, label: wp.i18n.__( 'Pages', 'teil1-schema-manager' ), icon: '📄' },
    { id: TABS.HELP, label: wp.i18n.__( 'Help', 'teil1-schema-manager' ), icon: '📖' },
  ];

  return (
    <div className="sp-min-h-screen sp-bg-surface-1 sp-p-6">
      {/* Header */}
      <header className="sp-mb-6 sp-flex sp-items-center sp-justify-between">
        <div className="sp-flex sp-items-center sp-gap-3">
          <div className="sp-flex sp-h-9 sp-w-9 sp-items-center sp-justify-center sp-rounded-lg sp-bg-brand-600 sp-text-white">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <path d="M4 7V4h16v3" /><path d="M9 20h6" /><path d="M12 4v16" />
            </svg>
          </div>
          <div>
            <h1 className="sp-text-lg sp-font-semibold sp-text-ink-0">Teil1 Schema Manager</h1>
            <p className="sp-text-2xs sp-font-medium sp-uppercase sp-tracking-wider sp-text-ink-3">
              {wp.i18n.__( 'Structured Data Engine', 'teil1-schema-manager' )}
            </p>
          </div>
        </div>

        <div className="sp-flex sp-items-center sp-gap-2">
          {view !== VIEWS.TAB && (
            <button
              onClick={handleBack}
              className="sp-inline-flex sp-items-center sp-gap-1.5 sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-px-3 sp-py-1.5 sp-text-sm sp-font-medium sp-text-ink-1 sp-shadow-bento sp-transition-all hover:sp-shadow-bento-hover"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
              </svg>
              {wp.i18n.__( 'Back', 'teil1-schema-manager' )}
            </button>
          )}
          {view === VIEWS.TAB && activeTab === TABS.DASHBOARD && (
            <button
              onClick={() => handleCreateGlobal()}
              className="sp-inline-flex sp-items-center sp-gap-1.5 sp-rounded-lg sp-bg-brand-600 sp-px-3 sp-py-1.5 sp-text-sm sp-font-medium sp-text-white sp-shadow-bento sp-transition-all hover:sp-bg-brand-700 hover:sp-shadow-bento-hover"
            >
              {wp.i18n.__( '+ New Global', 'teil1-schema-manager' )}
            </button>
          )}
          {view === VIEWS.TAB && activeTab === TABS.RULES && (
            <button
              onClick={() => handleCreateRule()}
              className="sp-inline-flex sp-items-center sp-gap-1.5 sp-rounded-lg sp-bg-brand-600 sp-px-3 sp-py-1.5 sp-text-sm sp-font-medium sp-text-white sp-shadow-bento sp-transition-all hover:sp-bg-brand-700 hover:sp-shadow-bento-hover"
            >
              {wp.i18n.__( '+ New Rule', 'teil1-schema-manager' )}
            </button>
          )}
        </div>
      </header>

      {/* Tab Navigation */}
      {view === VIEWS.TAB && (
        <nav className="sp-mb-6 sp-flex sp-gap-1 sp-rounded-lg sp-border sp-border-surface-3 sp-bg-white sp-p-1 sp-shadow-bento">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`sp-flex sp-items-center sp-gap-1.5 sp-rounded-md sp-px-4 sp-py-2 sp-text-sm sp-font-medium sp-transition-all ${
                activeTab === tab.id
                  ? 'sp-bg-brand-600 sp-text-white sp-shadow-sm'
                  : 'sp-text-ink-2 hover:sp-bg-surface-1 hover:sp-text-ink-0'
              }`}
            >
              <span>{tab.icon}</span>
              {tab.label}
            </button>
          ))}
        </nav>
      )}

      {/* Main Content */}
      <main className="sp-animate-fade-in">
        {view === VIEWS.TAB && activeTab === TABS.DASHBOARD && (
          <Dashboard
            onEdit={handleEditGlobal}
            onCreate={handleCreateGlobal}
            onNavigateToRules={() => setActiveTab(TABS.RULES)}
          />
        )}
        {view === VIEWS.TAB && activeTab === TABS.RULES && (
          <RulesList onEdit={handleEditRule} onCreateNew={() => handleCreateRule()} />
        )}
        {view === VIEWS.TAB && activeTab === TABS.STRUCTURE && (
          <SiteStructure onCreateRule={handleCreateRule} />
        )}
        {view === VIEWS.TAB && activeTab === TABS.PAGES && (
          <PageBrowser onEditLocal={handleEditLocal} />
        )}
        {view === VIEWS.TAB && activeTab === TABS.HELP && (
          <HelpPanel />
        )}
        {view === VIEWS.GLOBAL_EDITOR && (
          <SchemaEditor schema={editingSchema} onBack={handleBack} />
        )}
        {view === VIEWS.LOCAL_EDITOR && (
          <LocalSchemaEditor post={editingPost} onBack={handleBack} />
        )}
        {view === VIEWS.RULE_EDITOR && (
          <RuleBuilder rule={editingRule} onBack={handleBack} />
        )}
      </main>
    </div>
  );
}
