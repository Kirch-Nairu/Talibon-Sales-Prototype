import { chromium } from 'playwright';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs/promises';

const BASE = process.env.QA_BASE_URL || 'http://127.0.0.1:8000';
const PASSWORD = process.env.QA_DEMO_PASSWORD;
const EXPECTED_SHA = process.env.QA_EXPECTED_SHA;
const ENVIRONMENT = process.env.QA_ENVIRONMENT || 'local-isolated';
const REPORT = 'storage/app/qa/h0-runtime-readiness-report.json';
const SHOTS = 'storage/app/qa/h0-runtime-failures';
if (!PASSWORD || !EXPECTED_SHA) throw new Error('QA_DEMO_PASSWORD and QA_EXPECTED_SHA are required');

const secrets = new Set([PASSWORD]);
const state = {
  schemaVersion: 1,
  generatedAt: new Date().toISOString(),
  environment: ENVIRONMENT,
  git: { expectedSha: EXPECTED_SHA, actualSha: '', exactHead: false },
  summary: { targets: 0, passed: 0, failed: 0, pageerrorCount: 0, server5xxCount: 0, consoleDiagnosticCount: 0, navigationFailureCount: 0 },
  targets: [], diagnostics: [], screenshots: [], failure: null, completed: false,
  notes: [
    'H0 is a narrow catastrophic-render smoke; historical F1-F8 acceptance remains separate.',
    'Fresh PostgreSQL data and repository synthetic demo identities only.',
    'No passwords, MFA/TOTP values, recovery codes, cookies, CSRF/auth headers, response bodies, or private document/employee payloads are written.',
    'Application navigation is never reloaded or retried to manufacture a pass.',
  ],
};
let stage = 'bootstrap';

const clean = (value) => {
  let text = String(value ?? '');
  for (const secret of secrets) text = text.replaceAll(secret, '[MASKED]');
  return text;
};
const pathOnly = (value) => { try { const u = new URL(value, BASE); return `${u.pathname}${u.search}`; } catch { return '[unavailable]'; } };
const addDiag = (type, detail, page, extra = {}) => {
  state.diagnostics.push({ at: new Date().toISOString(), stage, path: pathOnly(page?.url?.() || BASE), type, detail: clean(detail), ...extra });
  if (state.diagnostics.length > 200) state.diagnostics.shift();
};
const writeReport = async () => {
  await fs.mkdir('storage/app/qa', { recursive: true });
  await fs.writeFile(REPORT, JSON.stringify(state, null, 2));
};

function watch(page) {
  const events = [];
  page.on('pageerror', (e) => { events.push(['pageerror', e.message]); state.summary.pageerrorCount++; addDiag('pageerror', e.message, page); });
  page.on('console', (m) => {
    if (m.type() !== 'error') return;
    const fatal = /(Uncaught|TypeError|ReferenceError|SyntaxError|ChunkLoadError|dynamically imported module)/i.test(m.text());
    events.push([fatal ? 'console-runtime-error' : 'console-diagnostic', m.text()]);
    state.summary.consoleDiagnosticCount++;
    addDiag('console-error', m.text(), page, { classification: fatal ? 'runtime-error' : 'diagnostic-only' });
  });
  page.on('response', (r) => {
    if (r.status() >= 500) {
      events.push(['server-5xx', `${r.status()} ${pathOnly(r.url())}`]);
      state.summary.server5xxCount++;
      addDiag('server-5xx', `${r.status()} ${pathOnly(r.url())}`, page, { status: r.status() });
    } else if (r.status() >= 400 && ['script', 'stylesheet'].includes(r.request().resourceType())) {
      events.push(['asset-http-error', `${r.status()} ${pathOnly(r.url())}`]);
      addDiag('asset-http-error', `${r.status()} ${pathOnly(r.url())}`, page, { status: r.status() });
    }
  });
  page.on('requestfailed', (r) => {
    if (!r.isNavigationRequest()) return;
    events.push(['navigation-failure', `${r.method()} ${pathOnly(r.url())}`]);
    state.summary.navigationFailureCount++;
    addDiag('navigation-failure', `${r.method()} ${pathOnly(r.url())} ${r.failure()?.errorText || ''}`, page);
  });
  return events;
}

function base32(input) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; let bits = '';
  for (const c of input.replace(/=+$/g, '').replace(/\s+/g, '').toUpperCase()) {
    const i = alphabet.indexOf(c); if (i < 0) throw new Error('Invalid MFA secret'); bits += i.toString(2).padStart(5, '0');
  }
  const bytes = []; for (let i = 0; i + 8 <= bits.length; i += 8) bytes.push(parseInt(bits.slice(i, i + 8), 2));
  return Buffer.from(bytes);
}
function totp(secret) {
  const counter = Buffer.alloc(8); counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
  const digest = crypto.createHmac('sha1', base32(secret)).update(counter).digest(); const o = digest.at(-1) & 15;
  const n = ((digest[o] & 127) << 24) | (digest[o + 1] << 16) | (digest[o + 2] << 8) | digest[o + 3];
  return String(n % 1_000_000).padStart(6, '0');
}

async function ready(page) {
  await page.waitForFunction(() => {
    const root = document.getElementById('app');
    const body = document.body?.innerText?.replace(/\s+/g, ' ').trim() || '';
    const rootText = root?.innerText?.replace(/\s+/g, ' ').trim() || '';
    return document.readyState !== 'loading' && !!root && (root.childElementCount > 0) && body.length >= 20 && rootText.length >= 10
      && !!document.querySelector('main, h1, form, nav[aria-label]');
  }, null, { timeout: 10000 });
  return page.evaluate(() => {
    const root = document.getElementById('app');
    const body = document.body?.innerText?.replace(/\s+/g, ' ').trim() || '';
    const rootText = root?.innerText?.replace(/\s+/g, ' ').trim() || '';
    return { bodyLength: body.length, rootPresent: !!root, rootChildren: root?.childElementCount || 0, rootTextLength: rootText.length };
  });
}
async function shot(page, role, key, safe) {
  if (!safe) return null;
  await fs.mkdir(SHOTS, { recursive: true });
  const file = `${SHOTS}/${role}-${key}.png`;
  try { await page.screenshot({ path: file, animations: 'disabled' }); state.screenshots.push(file); return file; }
  catch (e) { addDiag('screenshot-failure', e.message, page); return null; }
}

async function target(page, events, spec) {
  stage = `${spec.role}:${spec.key}`;
  const start = events.length;
  const row = { role: spec.role, key: spec.key, route: spec.route, finalUrl: null, status: null, result: 'fail', checks: {}, counts: {}, failureStage: null, screenshot: null };
  state.targets.push(row); state.summary.targets++;
  try {
    const response = await page.goto(`${BASE}${spec.route}`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    row.status = response?.status() ?? null; row.finalUrl = pathOnly(page.url());
    row.checks.navigation200 = row.status === 200;
    row.checks.finalPath = new URL(page.url()).pathname === (spec.expectedPath || spec.route.split('?')[0]);
    const snap = await ready(page);
    row.checks.appRoot = snap.rootPresent && snap.rootChildren > 0 && snap.rootTextLength >= 10;
    row.checks.nonBlankBody = snap.bodyLength >= 20;
    row.checks.semanticMarker = !spec.marker || await page.locator('body').getByText(spec.marker, { exact: false }).first().isVisible().catch(() => false);
    row.checks.requiredLinkVisible = !spec.requiredLink || await page.locator(`a[href="${spec.requiredLink}"]`).first().isVisible().catch(() => false);
    const delta = events.slice(start); const fatal = delta.filter(([type]) => type !== 'console-diagnostic');
    row.counts = {
      pageerrors: delta.filter(([t]) => t === 'pageerror').length,
      server5xx: delta.filter(([t]) => t === 'server-5xx').length,
      consoleDiagnostics: delta.filter(([t]) => t.startsWith('console-')).length,
      navigationFailures: delta.filter(([t]) => t === 'navigation-failure').length,
    };
    row.checks.runtimeClean = fatal.length === 0;
    const ok = Object.values(row.checks).every(Boolean);
    if (!ok) throw new Error(`runtime/presentation check failed: ${JSON.stringify(row.checks)}`);
    row.result = 'pass'; state.summary.passed++; return true;
  } catch (e) {
    row.finalUrl = pathOnly(page.url()); row.failureStage = stage; row.failure = clean(e.message); row.screenshot = await shot(page, spec.role, spec.key, spec.screenshotSafe);
    state.summary.failed++; addDiag('target-failure', e.message, page, { role: spec.role, key: spec.key }); return false;
  }
}

async function login(page, events, spec) {
  stage = `${spec.role}:login`; const start = events.length;
  const row = { role: spec.role, key: 'login', route: '/login', finalUrl: null, result: 'fail', checks: {}, counts: {}, failureStage: null, screenshot: null };
  state.targets.push(row); state.summary.targets++;
  try {
    const r = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    if (r?.status() !== 200) throw new Error(`login status ${r?.status()}`); await ready(page);
    await page.getByLabel('Email').fill(spec.email); await page.getByLabel('Password').fill(PASSWORD);
    const destinations = spec.privileged ? ['/dashboard', '/security/mfa/enroll', '/security/mfa/challenge'] : ['/dashboard'];
    await Promise.all([page.waitForURL((u) => destinations.includes(u.pathname), { timeout: 15000 }), page.getByRole('button', { name: 'Sign In' }).click()]);
    let p = new URL(page.url()).pathname;
    if (p === '/security/mfa/challenge') throw new Error('fresh privileged account unexpectedly requires existing MFA challenge');
    if (p === '/security/mfa/enroll') {
      const secret = (await page.locator('code').first().innerText()).trim(); secrets.add(secret);
      const code = totp(secret); secrets.add(code); await page.getByLabel('Six-digit verification code').fill(code);
      await Promise.all([page.waitForURL((u) => u.pathname === '/security/mfa/recovery-codes', { timeout: 15000 }), page.getByRole('button', { name: /Confirm MFA enrollment/i }).click()]);
      const recovery = page.locator('pre').first(); if (await recovery.count()) for (const c of (await recovery.innerText()).trim().split(/\s+/)) secrets.add(c);
      await Promise.all([page.waitForURL((u) => u.pathname === '/dashboard', { timeout: 15000 }), page.getByRole('link', { name: /Continue to portal/i }).click()]);
      p = new URL(page.url()).pathname;
    }
    const delta = events.slice(start); const fatal = delta.filter(([type]) => type !== 'console-diagnostic');
    row.finalUrl = pathOnly(page.url()); row.checks.authentication = p === '/dashboard'; row.checks.runtimeClean = fatal.length === 0;
    row.counts = { pageerrors: delta.filter(([t]) => t === 'pageerror').length, server5xx: delta.filter(([t]) => t === 'server-5xx').length, consoleDiagnostics: delta.filter(([t]) => t.startsWith('console-')).length, navigationFailures: delta.filter(([t]) => t === 'navigation-failure').length };
    if (!row.checks.authentication || !row.checks.runtimeClean) throw new Error('authentication did not complete cleanly on first attempt');
    row.result = 'pass'; state.summary.passed++; return true;
  } catch (e) {
    row.finalUrl = pathOnly(page.url()); row.failureStage = stage; row.failure = clean(e.message); state.summary.failed++; addDiag('login-failure', e.message, page, { role: spec.role }); return false;
  }
}

async function role(browser, spec) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } }); const page = await context.newPage(); const events = watch(page);
  if (await login(page, events, spec)) for (const t of spec.targets) await target(page, events, { ...t, role: spec.role });
  await context.close();
}

async function main() {
  state.git.actualSha = execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
  state.git.exactHead = state.git.actualSha === EXPECTED_SHA;
  if (!state.git.exactHead) { state.failure = { stage: 'exact-head', summary: 'checked-out SHA differs from QA_EXPECTED_SHA' }; await writeReport(); process.exitCode = 1; return; }

  const browser = await chromium.launch({ headless: true });
  try {
    const guestContext = await browser.newContext({ viewport: { width: 1280, height: 800 } }); const guest = await guestContext.newPage(); const guestEvents = watch(guest);
    await target(guest, guestEvents, { role: 'guest', key: 'public-home', route: '/', marker: 'ONE TALIBON', screenshotSafe: true });
    await target(guest, guestEvents, { role: 'guest', key: 'login-page', route: '/login', marker: 'Employee Portal', screenshotSafe: true }); await guestContext.close();

    await role(browser, { role: 'employee', email: 'employee@talibon.demo', privileged: false, targets: [
      { key: 'dashboard', route: '/dashboard' }, { key: 'my-work', route: '/transactions' }, { key: 'records', route: '/records', marker: 'Records' },
    ] });
    await role(browser, { role: 'department-head', email: 'engineering@talibon.demo', privileged: true, targets: [
      { key: 'dashboard', route: '/dashboard' }, { key: 'office-work', route: '/transactions?view=office_queue', expectedPath: '/transactions', marker: 'Office Work' },
      { key: 'correspondence', route: '/correspondence' }, { key: 'reports', route: '/reports?report=transaction-aging', expectedPath: '/reports' },
      { key: 'departments', route: '/departments', marker: 'Municipal Offices' },
    ] });
    await role(browser, { role: 'executive', email: 'mayor@talibon.demo', privileged: true, targets: [
      { key: 'dashboard', route: '/dashboard' }, { key: 'mayor-workspace', route: '/mayor-office', marker: 'Items requiring executive attention' }, { key: 'records', route: '/records', marker: 'Records' },
    ] });
    await role(browser, { role: 'system-admin', email: 'admin@talibon.demo', privileged: true, targets: [
      { key: 'dashboard', route: '/dashboard', requiredLink: '/mayor-office' }, { key: 'mayor-workspace', route: '/mayor-office', marker: 'Items requiring executive attention' },
      { key: 'admin', route: '/admin', marker: 'Accounts & Access' }, { key: 'audit', route: '/audit', marker: 'Audit & Security' },
      { key: 'calendar', route: '/calendar', marker: 'Events, deadlines & schedules' }, { key: 'employee-directory', route: '/employees', marker: 'Employee Directory' },
    ] });
  } finally { await browser.close().catch(() => {}); }

  state.completed = state.summary.failed === 0 && state.summary.targets === state.summary.passed && state.git.exactHead;
  if (!state.completed) state.failure = { stage, summary: `${state.summary.failed} of ${state.summary.targets} H0 targets failed` };
  await writeReport();
  if (state.completed) console.log(`H0_RUNTIME_QA_PASS sha=${state.git.actualSha} targets=${state.summary.targets}`);
  else { console.error(`H0_RUNTIME_QA_FAIL sha=${state.git.actualSha} failures=${state.summary.failed}`); process.exitCode = 1; }
}

main().catch(async (e) => {
  state.failure = { stage, summary: clean(e.message) }; addDiag('h0-abort', e.stack || e, null); await writeReport().catch(() => {});
  console.error(`H0_RUNTIME_QA_ABORT stage=${stage} error=${clean(e.message)}`); process.exitCode = 1;
});
