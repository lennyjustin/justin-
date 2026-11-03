import test from 'node:test';
import assert from 'node:assert/strict';
import { api, formatKes, updateCsrfToken } from './api.js';

test('api sends same-origin credentials, JSON, and the CSRF token', async () => {
  const originalFetch = globalThis.fetch;
  updateCsrfToken('csrf-test-token');
  globalThis.fetch = async (url, options) => {
    assert.equal(url, '/api/session');
    assert.equal(options.credentials, 'same-origin');
    assert.equal(options.headers.get('X-CSRF-Token'), 'csrf-test-token');
    assert.equal(options.headers.get('Content-Type'), 'application/json');
    return new Response(JSON.stringify({ ok: true }), { status: 200 });
  };
  try {
    assert.deepEqual(await api('/session', { method: 'POST', body: '{}' }), { ok: true });
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('api exposes server error messages', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () => new Response(JSON.stringify({ error: 'Invalid estimate.' }), { status: 400 });
  try {
    await assert.rejects(api('/estimates/preview'), /Invalid estimate\./);
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('formatKes renders amounts as Kenyan shillings', () => {
  assert.match(formatKes(1200), /KSh\s*1,200/);
});
