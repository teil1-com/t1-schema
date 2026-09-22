import React, { useState } from 'react';
import { usePosts, usePostTypes } from '../../hooks/useSchema';

/**
 * PageBrowser — browse all posts/pages, see their local schema status,
 * and manage per-page schemas.
 */
export default function PageBrowser({ onEditLocal }) {
  const [search, setSearch] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [filter, setFilter] = useState('');
  const [postType, setPostType] = useState('');
  const [page, setPage] = useState(1);

  const { data: postTypes = [] } = usePostTypes();

  const { data, isLoading } = usePosts({
    search,
    filter,
    post_type: postType,
    page,
    per_page: 15,
  });

  const posts = data?.posts || [];
  const totalPages = data?.total_pages || 1;
  const total = data?.total || 0;

  const handleSearch = (e) => {
    e.preventDefault();
    setSearch(searchInput);
    setPage(1);
  };

  return (
    <div className="sp-space-y-4">
      {/* Header */}
      <div className="sp-flex sp-items-center sp-justify-between">
        <div>
          <h2 className="sp-text-base sp-font-semibold sp-text-ink-0">
            {wp.i18n.__('Pages & Posts', 'teil1-schema-manager')}
          </h2>
          <p className="sp-text-sm sp-text-ink-3">
            {wp.i18n.__(
              'Manage local schemas for individual pages — these override global schemas per type',
              'teil1-schema-manager'
            )}
          </p>
        </div>
        <span className="sp-rounded-full sp-bg-surface-2 sp-px-2.5 sp-py-0.5 sp-text-xs sp-font-medium sp-text-ink-2">
          {wp.i18n.sprintf(
            /* translators: %1$d: Total number of pages and posts. */
            wp.i18n._n('%1$d item total', '%1$d items total', total, 'teil1-schema-manager'),
            total
          )}
        </span>
      </div>

      {/* Search + Filters */}
      <div className="sp-flex sp-flex-wrap sp-items-center sp-gap-3 sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-p-4 sp-shadow-bento">
        <form onSubmit={handleSearch} className="sp-flex sp-flex-1 sp-items-center sp-gap-2">
          <div className="sp-relative sp-flex-1">
            <svg className="sp-absolute sp-left-3 sp-top-1/2 sp--translate-y-1/2 sp-text-ink-4" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" />
            </svg>
            <input
              type="text"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder={wp.i18n.__('Search pages…', 'teil1-schema-manager')}
              className="sp-w-full sp-rounded-lg sp-border sp-border-surface-3 sp-bg-surface-1 sp-py-2 sp-pl-9 sp-pr-3 sp-text-sm sp-outline-none sp-transition-colors focus:sp-border-brand-400 focus:sp-bg-white"
            />
          </div>
          <button
            type="submit"
            className="sp-rounded-lg sp-bg-brand-600 sp-px-3 sp-py-2 sp-text-sm sp-font-medium sp-text-white sp-transition-colors hover:sp-bg-brand-700"
          >
            {wp.i18n.__('Search', 'teil1-schema-manager')}
          </button>
        </form>

        <div className="sp-flex sp-items-center sp-gap-2">
          <select
            value={filter}
            onChange={(e) => { setFilter(e.target.value); setPage(1); }}
            className="sp-rounded-lg sp-border sp-border-surface-3 sp-bg-surface-1 sp-px-3 sp-py-2 sp-text-sm sp-text-ink-1 sp-outline-none"
          >
            <option value="">{wp.i18n.__('All pages', 'teil1-schema-manager')}</option>
            <option value="with_schema">{wp.i18n.__('With schema', 'teil1-schema-manager')}</option>
            <option value="without_schema">{wp.i18n.__('Without schema', 'teil1-schema-manager')}</option>
          </select>

          <select
            value={postType}
            onChange={(e) => { setPostType(e.target.value); setPage(1); }}
            className="sp-rounded-lg sp-border sp-border-surface-3 sp-bg-surface-1 sp-px-3 sp-py-2 sp-text-sm sp-text-ink-1 sp-outline-none"
          >
            <option value="">{wp.i18n.__('All types', 'teil1-schema-manager')}</option>
            {postTypes.map((pt) => (
              <option key={pt.slug} value={pt.slug}>
                {pt.label} ({pt.count})
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Posts List */}
      {isLoading ? (
        <div className="sp-flex sp-items-center sp-justify-center sp-py-16">
          <div
            role="status"
            aria-label={wp.i18n.__('Loading pages and posts…', 'teil1-schema-manager')}
            className="sp-h-6 sp-w-6 sp-animate-spin sp-rounded-full sp-border-2 sp-border-brand-200 sp-border-t-brand-600"
          />
        </div>
      ) : posts.length === 0 ? (
        <div className="sp-flex sp-flex-col sp-items-center sp-justify-center sp-rounded-xl sp-border sp-border-dashed sp-border-surface-3 sp-bg-white/50 sp-py-16">
          <span className="sp-mb-2 sp-text-3xl">🔍</span>
          <p className="sp-text-sm sp-text-ink-3">
            {wp.i18n.__('No posts found matching your criteria', 'teil1-schema-manager')}
          </p>
        </div>
      ) : (
        <div className="sp-space-y-2">
          {posts.map((post) => (
            <PostRow key={post.id} post={post} onEdit={() => onEditLocal(post)} />
          ))}
        </div>
      )}

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="sp-flex sp-items-center sp-justify-between sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-px-4 sp-py-3 sp-shadow-bento">
          <span className="sp-text-xs sp-text-ink-3">
            {wp.i18n.sprintf(
              /* translators: %1$d: Current page number, %2$d: Total number of pages. */
              wp.i18n.__('Page %1$d of %2$d', 'teil1-schema-manager'),
              page,
              totalPages
            )}
          </span>
          <div className="sp-flex sp-gap-1">
            <button
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page <= 1}
              className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-1 disabled:sp-opacity-40"
            >
              {wp.i18n.__('← Prev', 'teil1-schema-manager')}
            </button>
            <button
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              disabled={page >= totalPages}
              className="sp-rounded-lg sp-border sp-border-surface-3 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-2 sp-transition-colors hover:sp-bg-surface-1 disabled:sp-opacity-40"
            >
              {wp.i18n.__('Next →', 'teil1-schema-manager')}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

/**
 * Single post row in the browser.
 */
function PostRow({ post, onEdit }) {
  const hasSchema = post.schema_count > 0;

  // Deterministic color per post type.
  const typeColors = {
    page: 'sp-bg-purple-100 sp-text-purple-600',
    post: 'sp-bg-blue-100 sp-text-blue-600',
  };
  const fallbackColors = [
    'sp-bg-teal-100 sp-text-teal-600',
    'sp-bg-orange-100 sp-text-orange-600',
    'sp-bg-pink-100 sp-text-pink-600',
    'sp-bg-cyan-100 sp-text-cyan-600',
    'sp-bg-amber-100 sp-text-amber-600',
    'sp-bg-emerald-100 sp-text-emerald-600',
  ];
  const typeColor = typeColors[post.post_type]
    || fallbackColors[post.post_type.length % fallbackColors.length];

  return (
    <div className="sp-group sp-flex sp-items-center sp-justify-between sp-rounded-xl sp-border sp-border-surface-3 sp-bg-white sp-px-5 sp-py-4 sp-shadow-bento sp-transition-all hover:sp-shadow-bento-hover">
      <div className="sp-flex sp-flex-1 sp-items-center sp-gap-4">
        {/* Post type badge */}
        <span className={`sp-rounded sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-uppercase sp-tracking-wider ${typeColor}`}>
          {post.post_type}
        </span>

        {/* Title + URL */}
        <div className="sp-min-w-0 sp-flex-1">
          <p className="sp-text-sm sp-font-medium sp-text-ink-0 sp-truncate">{post.title}</p>
          <p className="sp-text-xs sp-text-ink-4 sp-truncate">{post.url}</p>
        </div>

        {/* Schema status */}
        <div className="sp-flex sp-items-center sp-gap-2">
          {hasSchema ? (
            <>
              <span className="sp-rounded-full sp-bg-green-100 sp-px-2 sp-py-0.5 sp-text-2xs sp-font-semibold sp-text-green-700">
                {wp.i18n.sprintf(
                  /* translators: %1$d: Number of schemas. */
                  wp.i18n._n('%1$d schema', '%1$d schemas', post.schema_count, 'teil1-schema-manager'),
                  post.schema_count
                )}
              </span>
              <div className="sp-flex sp-gap-1">
                {post.schema_types.map((type, i) => (
                  <span key={i} className="sp-rounded sp-bg-surface-2 sp-px-1.5 sp-py-0.5 sp-text-2xs sp-font-medium sp-text-ink-2">
                    {type}
                  </span>
                ))}
              </div>
            </>
          ) : (
            <span className="sp-text-2xs sp-text-ink-4">
              {wp.i18n.__('No local schema', 'teil1-schema-manager')}
            </span>
          )}
        </div>
      </div>

      {/* Actions */}
      <button
        onClick={onEdit}
        className="sp-ml-4 sp-rounded-lg sp-border sp-border-surface-3 sp-bg-surface-1 sp-px-3 sp-py-1.5 sp-text-xs sp-font-medium sp-text-ink-1 sp-opacity-0 sp-transition-all group-hover:sp-opacity-100 hover:sp-bg-brand-50 hover:sp-text-brand-600 hover:sp-border-brand-200"
      >
        {hasSchema
          ? wp.i18n.__('Edit Schema', 'teil1-schema-manager')
          : wp.i18n.__('+ Add Schema', 'teil1-schema-manager')}
      </button>
    </div>
  );
}
