/**
 * t1 Schema API Client.
 *
 * Wraps WordPress REST API calls with nonce handling
 * and provides TanStack Query hooks for all endpoints.
 */

const config = window.t1SchemaConfig || {};

/**
 * Build an endpoint URL for both pretty and plain WordPress permalinks.
 *
 * On a default installation, rest_url() returns a URL such as
 * `index.php?rest_route=/teil1-schema-manager/v1/`. Appending an endpoint and
 * its query string directly would put `?page=...` inside the rest_route value.
 */
function buildApiUrl(endpoint) {
  const queryIndex = endpoint.indexOf('?');
  const endpointPath = queryIndex === -1 ? endpoint : endpoint.slice(0, queryIndex);
  const endpointQuery = queryIndex === -1 ? '' : endpoint.slice(queryIndex + 1);
  const base = new URL(config.restUrl, window.location.origin);
  const restRoute = base.searchParams.get('rest_route');

  if (restRoute !== null) {
    const routeBase = restRoute.endsWith('/') ? restRoute : `${restRoute}/`;
    base.searchParams.set('rest_route', `${routeBase}${endpointPath.replace(/^\/+/, '')}`);

    new URLSearchParams(endpointQuery).forEach((value, key) => {
      base.searchParams.append(key, value);
    });

    return base.toString();
  }

  const baseUrl = config.restUrl.endsWith('/') ? config.restUrl : `${config.restUrl}/`;
  return `${baseUrl}${endpoint.replace(/^\/+/, '')}`;
}

/**
 * Base fetch wrapper with WP nonce handling.
 */
async function apiFetch(endpoint, options = {}) {
  const url = buildApiUrl(endpoint);

  const headers = {
    'Content-Type': 'application/json',
    'X-WP-Nonce': config.nonce,
    ...options.headers,
  };

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(error.message || `API error: ${response.status}`);
  }

  return response.json();
}

// =============================================================================
// Global Schema API
// =============================================================================

export const globalsApi = {
  list: () => apiFetch('globals'),
  get: (id) => apiFetch(`globals/${id}`),
  create: (data) => apiFetch('globals', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  update: (id, data) => apiFetch(`globals/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
  delete: (id) => apiFetch(`globals/${id}`, {
    method: 'DELETE',
  }),
};

// =============================================================================
// Local Schema API
// =============================================================================

export const localApi = {
  get: (postId) => apiFetch(`local/${postId}`),
  update: (postId, schemas) => apiFetch(`local/${postId}`, {
    method: 'PUT',
    body: JSON.stringify({ schemas }),
  }),
};

// =============================================================================
// Health API
// =============================================================================

export const healthApi = {
  getSiteHealth: () => apiFetch('health'),
  getPostHealth: (postId) => apiFetch(`health/${postId}`),
};

// =============================================================================
// Posts Browser API
// =============================================================================

export const postsApi = {
  list: (params = {}) => {
    const query = new URLSearchParams();
    if (params.search) query.set('search', params.search);
    if (params.post_type) query.set('post_type', params.post_type);
    if (params.filter) query.set('filter', params.filter);
    if (params.page) query.set('page', params.page);
    if (params.per_page) query.set('per_page', params.per_page);
    const qs = query.toString();
    return apiFetch(`posts${qs ? `?${qs}` : ''}`);
  },
};

// =============================================================================
// Schema Rules API
// =============================================================================

export const rulesApi = {
  list: () => apiFetch('rules'),
  get: (id) => apiFetch(`rules/${id}`),
  create: (data) => apiFetch('rules', { method: 'POST', body: JSON.stringify(data) }),
  update: (id, data) => apiFetch(`rules/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  delete: (id) => apiFetch(`rules/${id}`, { method: 'DELETE' }),
};

// =============================================================================
// Site Structure & Contexts API
// =============================================================================

export const siteStructureApi = {
  get: () => apiFetch('site-structure'),
};

export const contextsApi = {
  list: () => apiFetch('contexts'),
};

// =============================================================================
// Utility APIs
// =============================================================================

export const postTypesApi = {
  list: () => apiFetch('post-types'),
};

export const typesApi = {
  list: () => apiFetch('types'),
};

export const parseApi = {
  parse: (jsonld) => apiFetch('parse', {
    method: 'POST',
    body: JSON.stringify({ jsonld }),
  }),
};

export const variablesApi = {
  list: () => apiFetch('variables'),
};

export const settingsApi = {
  get: () => apiFetch('settings'),
  update: (data) => apiFetch('settings', {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
};

// =============================================================================
// Score API
// =============================================================================

export const scoreApi = {
  get: () => apiFetch('score'),
};

// =============================================================================
// Recommended Rules API
// =============================================================================

export const recommendedRulesApi = {
  list: () => apiFetch('recommended-rules'),
  activate: (key) => apiFetch('recommended-rules', {
    method: 'POST',
    body: JSON.stringify({ key }),
  }),
};

// =============================================================================
// Custom Variables API
// =============================================================================

export const customVariablesApi = {
  get: () => apiFetch('custom-variables'),
  update: (data) => apiFetch('custom-variables', {
    method: 'PUT',
    body: JSON.stringify(data),
  }),
};
