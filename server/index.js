import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { readFile } from 'node:fs/promises';
import { randomBytes, timingSafeEqual } from 'node:crypto';
import bcrypt from 'bcryptjs';
import connectPgSimple from 'connect-pg-simple';
import express from 'express';
import { rateLimit } from 'express-rate-limit';
import session from 'express-session';
import helmet from 'helmet';
import pg from 'pg';

const { Pool } = pg;
const dirname = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(dirname, '..');
const port = Number(process.env.PORT || 3000);
const databaseUrl = process.env.DATABASE_URL;
const calculatorUrl = (process.env.ESTIMATE_SERVICE_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const sessionSecret = process.env.SESSION_SECRET;

if (!databaseUrl) throw new Error('DATABASE_URL is required.');
if (!sessionSecret || sessionSecret.length < 32) throw new Error('SESSION_SECRET must contain at least 32 characters.');

const pool = new Pool({
  connectionString: databaseUrl,
  ssl: /sslmode=require/i.test(databaseUrl) ? { rejectUnauthorized: false } : undefined,
  max: Number(process.env.PG_POOL_SIZE || 10),
});
const app = express();
const PgSession = connectPgSimple(session);

async function initializeDatabase() {
  const schema = await readFile(path.join(projectRoot, 'database', 'schema.postgresql.sql'), 'utf8');
  await pool.query(schema);
}

app.disable('x-powered-by');
app.set('trust proxy', process.env.TRUST_PROXY === 'true' ? 1 : false);
app.use(helmet({ contentSecurityPolicy: false, crossOriginEmbedderPolicy: false }));
app.use(express.json({ limit: '32kb' }));
app.use(session({
  name: 'jenga.sid',
  store: new PgSession({ pool, tableName: 'user_sessions', createTableIfMissing: false }),
  secret: sessionSecret,
  resave: false,
  saveUninitialized: false,
  cookie: {
    httpOnly: true,
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    maxAge: 1000 * 60 * 60 * 24 * 14,
  },
}));

function createCsrfToken() {
  return randomBytes(32).toString('hex');
}

function requireCsrf(req, res, next) {
  const expected = req.session.csrfToken;
  const supplied = req.get('x-csrf-token') || '';
  if (typeof expected !== 'string' || typeof supplied !== 'string') {
    return res.status(403).json({ error: 'Your form session expired. Refresh the page and try again.' });
  }
  const expectedBytes = Buffer.from(expected);
  const suppliedBytes = Buffer.from(supplied);
  if (expectedBytes.length !== suppliedBytes.length || !timingSafeEqual(expectedBytes, suppliedBytes)) {
    return res.status(403).json({ error: 'Your form session expired. Refresh the page and try again.' });
  }
  return next();
}

function requireUser(req, res, next) {
  if (!req.session.user) return res.status(401).json({ error: 'Please sign in to continue.' });
  return next();
}

function asyncRoute(handler) {
  return (req, res, next) => Promise.resolve(handler(req, res, next)).catch(next);
}

function normalizeUser(user) {
  return { id: Number(user.id), name: user.name, email: user.email };
}

async function regenerateSession(req) {
  await new Promise((resolve, reject) => req.session.regenerate((error) => error ? reject(error) : resolve()));
  req.session.csrfToken = createCsrfToken();
}

async function getMaterials(userId = null) {
  const { rows } = await pool.query(
    `SELECT m.id, m.name, m.unit, m.quantity_factor, m.finish_sensitive, m.default_price,
            COALESCE(ur.price, m.default_price) AS price,
            (ur.price IS NOT NULL) AS override_price
     FROM materials m
     LEFT JOIN user_material_rates ur ON ur.material_id = m.id AND ur.user_id = $1
     WHERE m.is_active = 1 ORDER BY m.sort_order, m.id`,
    [userId],
  );
  return rows;
}

async function calculate(input, userId = null) {
  const materials = await getMaterials(userId);
  let response;
  try {
    response = await fetch(`${calculatorUrl}/calculate`, {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: JSON.stringify({ ...input, materials }),
      signal: AbortSignal.timeout(5000),
    });
  } catch (error) {
    throw Object.assign(new Error(`Estimate calculator is unavailable: ${error.message}`), { status: 503 });
  }
  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw Object.assign(new Error(result.error || 'Estimate calculation failed.'), { status: response.status < 500 ? response.status : 502 });
  }
  return result;
}

app.get('/api/health', asyncRoute(async (_req, res) => {
  await pool.query('SELECT 1');
  const response = await fetch(`${calculatorUrl}/health`, { signal: AbortSignal.timeout(2000) });
  if (!response.ok) return res.status(503).json({ status: 'unhealthy', calculator: response.status });
  return res.json({ status: 'ok' });
}));

app.get('/api/session', (req, res) => {
  if (!req.session.csrfToken) req.session.csrfToken = createCsrfToken();
  res.json({ user: req.session.user || null, csrfToken: req.session.csrfToken });
});

const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 10,
  standardHeaders: 'draft-8',
  legacyHeaders: false,
  message: { error: 'Too many sign-in attempts. Please wait and try again.' },
});

app.post('/api/auth/register', authLimiter, requireCsrf, asyncRoute(async (req, res) => {
  const name = typeof req.body.name === 'string' ? req.body.name.trim() : '';
  const email = typeof req.body.email === 'string' ? req.body.email.trim().toLowerCase() : '';
  const password = typeof req.body.password === 'string' ? req.body.password : '';
  if (name.length < 2 || name.length > 100) return res.status(400).json({ error: 'Enter a name between 2 and 100 characters.' });
  if (email.length > 190 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return res.status(400).json({ error: 'Enter a valid email address.' });
  if (password.length < 10 || Buffer.byteLength(password, 'utf8') > 72) return res.status(400).json({ error: 'Use a password with 10 to 72 bytes.' });
  const hash = await bcrypt.hash(password, 12);
  try {
    const { rows } = await pool.query(
      'INSERT INTO users (name, email, password_hash) VALUES ($1, $2, $3) RETURNING id, name, email',
      [name, email, hash],
    );
    await regenerateSession(req);
    req.session.user = normalizeUser(rows[0]);
    await new Promise((resolve, reject) => req.session.save((error) => error ? reject(error) : resolve()));
    return res.status(201).json({ user: req.session.user, csrfToken: req.session.csrfToken });
  } catch (error) {
    if (error.code === '23505') return res.status(409).json({ error: 'An account with that email already exists.' });
    throw error;
  }
}));

app.post('/api/auth/login', authLimiter, requireCsrf, asyncRoute(async (req, res) => {
  const email = typeof req.body.email === 'string' ? req.body.email.trim().toLowerCase() : '';
  const password = typeof req.body.password === 'string' ? req.body.password : '';
  const { rows } = await pool.query('SELECT id, name, email, password_hash FROM users WHERE email = $1 LIMIT 1', [email]);
  if (!rows[0] || !(await bcrypt.compare(password, rows[0].password_hash))) {
    return res.status(401).json({ error: 'Email or password was not recognized.' });
  }
  await regenerateSession(req);
  req.session.user = normalizeUser(rows[0]);
  await new Promise((resolve, reject) => req.session.save((error) => error ? reject(error) : resolve()));
  return res.json({ user: req.session.user, csrfToken: req.session.csrfToken });
}));

app.post('/api/auth/logout', requireCsrf, asyncRoute(async (req, res) => {
  await new Promise((resolve, reject) => req.session.destroy((error) => error ? reject(error) : resolve()));
  res.clearCookie('jenga.sid', { httpOnly: true, sameSite: 'lax', secure: process.env.NODE_ENV === 'production' });
  return res.json({ ok: true });
}));

app.post('/api/estimates/preview', requireCsrf, asyncRoute(async (req, res) => {
  return res.json(await calculate(req.body, req.session.user?.id || null));
}));

app.get('/api/rates', requireUser, asyncRoute(async (req, res) => {
  return res.json({ materials: await getMaterials(req.session.user.id) });
}));

app.put('/api/rates', requireCsrf, requireUser, asyncRoute(async (req, res) => {
  if (!req.body.rates || typeof req.body.rates !== 'object' || Array.isArray(req.body.rates)) {
    return res.status(400).json({ error: 'Submit a valid rate list.' });
  }
  const materials = await getMaterials();
  const allowedIds = new Set(materials.map((material) => String(material.id)));
  const entries = Object.entries(req.body.rates);
  if (entries.some(([id]) => !allowedIds.has(id))) return res.status(400).json({ error: 'One or more material IDs are invalid.' });
  const changes = entries.map(([id, raw]) => {
    if (raw === '' || raw === null) return { id: Number(id), price: null };
    const price = Number(raw);
    if (!Number.isFinite(price) || price <= 0 || price > 10000000) {
      throw Object.assign(new Error('Enter a valid positive rate, or leave the field blank to use the sample rate.'), { status: 400 });
    }
    return { id: Number(id), price: Math.round(price * 100) / 100 };
  });
  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    for (const change of changes) {
      if (change.price === null) {
        await client.query('DELETE FROM user_material_rates WHERE user_id = $1 AND material_id = $2', [req.session.user.id, change.id]);
      } else {
        await client.query(
          `INSERT INTO user_material_rates (user_id, material_id, price) VALUES ($1, $2, $3)
           ON CONFLICT (user_id, material_id) DO UPDATE SET price = EXCLUDED.price, updated_at = CURRENT_TIMESTAMP`,
          [req.session.user.id, change.id, change.price],
        );
      }
    }
    await client.query('COMMIT');
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
  return res.json({ materials: await getMaterials(req.session.user.id) });
}));

app.get('/api/estimates', requireUser, asyncRoute(async (req, res) => {
  const { rows } = await pool.query(
    `SELECT id, project_name, project_type, location, floor_area, floors, finish_level, total_cost, created_at
     FROM estimates WHERE user_id = $1 ORDER BY created_at DESC, id DESC`,
    [req.session.user.id],
  );
  return res.json({ estimates: rows });
}));

app.post('/api/estimates', requireCsrf, requireUser, asyncRoute(async (req, res) => {
  const estimate = await calculate(req.body, req.session.user.id);
  const input = estimate.input;
  const client = await pool.connect();
  let estimateId;
  try {
    await client.query('BEGIN');
    const { rows } = await client.query(
      `INSERT INTO estimates
       (user_id, project_name, project_type, location, floor_area, floors, finish_level, transport_percent, contingency_percent,
        materials_cost, labour_cost, transport_cost, contingency_cost, total_cost)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14) RETURNING id`,
      [req.session.user.id, input.project_name, input.project_type, input.location, input.floor_area, input.floors, input.finish_level,
        input.transport_percent, input.contingency_percent, estimate.materials_cost, estimate.labour_cost, estimate.transport_cost,
        estimate.contingency_cost, estimate.total_cost],
    );
    estimateId = rows[0].id;
    for (const item of estimate.items) {
      await client.query(
        `INSERT INTO estimate_items (estimate_id, material_id, material_name, unit, quantity, unit_price, total_cost)
         VALUES ($1, $2, $3, $4, $5, $6, $7)`,
        [estimateId, item.material_id, item.name, item.unit, item.quantity, item.unit_price, item.total_cost],
      );
    }
    await client.query('COMMIT');
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
  return res.status(201).json({ id: estimateId });
}));

app.get('/api/estimates/:id', requireUser, asyncRoute(async (req, res) => {
  if (!/^[1-9]\d*$/.test(req.params.id)) return res.status(400).json({ error: 'A valid estimate ID is required.' });
  const { rows } = await pool.query(
    `SELECT id, project_name, project_type, location, floor_area, floors, finish_level, transport_percent, contingency_percent,
            materials_cost, labour_cost, transport_cost, contingency_cost, total_cost, created_at
     FROM estimates WHERE id = $1 AND user_id = $2`,
    [req.params.id, req.session.user.id],
  );
  if (!rows[0]) return res.status(404).json({ error: 'Saved estimate not found.' });
  const { rows: items } = await pool.query(
    `SELECT material_id, material_name AS name, unit, quantity, unit_price, total_cost
     FROM estimate_items WHERE estimate_id = $1 ORDER BY id`,
    [req.params.id],
  );
  return res.json({ ...rows[0], items });
}));

app.use('/api', (_req, res) => res.status(404).json({ error: 'API endpoint not found.' }));
app.use(express.static(path.join(projectRoot, 'dist'), { index: false, maxAge: process.env.NODE_ENV === 'production' ? '1h' : 0 }));
app.get('*splat', (_req, res) => res.sendFile(path.join(projectRoot, 'dist', 'index.html')));
app.use((error, _req, res, _next) => {
  console.error(error);
  const status = Number.isInteger(error.status) && error.status >= 400 && error.status < 600 ? error.status : 500;
  return res.status(status).json({ error: status === 500 ? 'An unexpected server error occurred.' : error.message });
});

async function start() {
  await initializeDatabase();
  const server = app.listen(port, '0.0.0.0', () => console.log(`Construction Estimator listening on port ${port}`));
  const shutdown = async () => {
    server.close(async () => {
      await pool.end();
      process.exit(0);
    });
  };
  process.on('SIGINT', shutdown);
  process.on('SIGTERM', shutdown);
}

start().catch(async (error) => {
  console.error('Application startup failed:', error);
  await pool.end();
  process.exitCode = 1;
});
