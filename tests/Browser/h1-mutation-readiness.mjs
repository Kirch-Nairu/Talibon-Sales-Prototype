import { chromium } from 'playwright';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs/promises';

const BASE = process.env.QA_BASE_URL || 'http://127.0.0.1:8000';
const PASSWORD = process.env.QA_DEMO_PASSWORD;
const EXPECTED_SHA = process.env.QA_EXPECTED_SHA;
const ENVIRONMENT = process.env.QA_ENVIRONMENT || 'local-isolated-h1';
const REPORT = 'storage/app/qa/h1-mutation-readiness-report.json';
const SHOTS = 'storage/app/qa/h1-mutation-failures';
const CORRESPONDENCE_ID = '11000000-0000-4000-8000-000000000001';
const MAYOR_SEEDED_TRANSACTION = 'Road Rehabilitation Funding Request';

if (!PASSWORD || !EXPECTED_SHA) {
  throw new Error('QA_DEMO_PASSWORD and QA_EXPECTED_SHA are required');
}

const marker = EXPECTED_SHA.slice(0, 10);
const transactionTitle = `H1 mutation transaction ${marker}`;
const invalidTransactionTitle = `H1 invalid transaction ${marker}`;
const memorandumNumber = `H1-MEMO-${marker.toUpperCase()}`;
const travelReference = `H1-TO-${marker.toUpperCase()}`;
const assignTitle = `H3 assign transaction ${marker}`;
const forwardTitle = `H3 forward transaction ${marker}`;
const returnTitle = `H3 return transaction ${marker}`;
const requestInformationTitle = `H3 request information transaction ${marker}`;
const disapproveTitle = `H3 mayor disapprove transaction ${marker}`;
const cancelTravelReference = `H3-TO-CANCEL-${marker.toUpperCase()}`;
const secrets = new Set([PASSWORD]);
const runtimeByPage = new WeakMap();

const report = {
  schemaVersion: 1,
  generatedAt: new Date().toISOString(),
  environment: ENVIRONMENT,
  git: { expectedSha: EXPECTED_SHA, actualSha: '', exactHead: false },
  isolation: null,
  summary: {
    scenarios: 0,
    passed: 0,
    failed: 0,
    failH1: 0,
    deferredH2: 0,
    pageerrorCount: 0,
    server5xxCount: 0,
  },
  coverage: {
    transactions: [
      'create',
      'mark_review rapid double-click',
      'Mayor approve',
      'assign',
      'forward handoff',
      'return to origin',
      'request information',
      'Mayor disapprove',
    ],
    correspondence: ['register', 'classify', 'route rapid double-click', 'begin action'],
    memoranda: ['publish', 'recipient acknowledgement rapid double-click'],
    notifications: {
      status: 'not-exercised',
      reason: 'The current portal notification panel exposes navigation links, not a direct read/acknowledge browser action. H1B does not call hidden mutation endpoints.',
    },
    travelOrders: ['record approved order', 'complete rapid double-click', 'terminal action removal', 'cancel'],
    negatives: ['transaction validation error', 'unauthorized Travel Order create denial'],
  },
  scenarios: [],
  diagnostics: [],
  screenshots: [],
  defects: [],
  failure: null,
  completed: false,
  notes: [
    'H1 exercises browser-visible write paths only against a fresh isolated PostgreSQL database.',
    'Immediate convergence is asserted before any reload; reload is used only after the immediate-state verdict.',
    'Rapid duplicate interaction is generated through real browser click events; no mutation endpoint is called directly by the harness.',
    'H3 prerequisite actions use the real browser UI and are evidence for the requested scenario, not hidden endpoint setup.',
    'Database inspection is test-only and emits counts/status identifiers only. It never emits database credentials, passwords, TOTP values, cookies, CSRF tokens, authorization headers, health payloads, document bodies, or private HR payloads.',
  ],
};

function clean(value) {
  let text = String(value ?? '');
  for (const secret of secrets) text = text.replaceAll(secret, '[MASKED]');
  return text;
}

function pathOnly(value) {
  try {
    const url = new URL(value, BASE);
    return `${url.pathname}${url.search}`;
  } catch {
    return '[unavailable]';
  }
}

async function writeReport() {
  await fs.mkdir('storage/app/qa', { recursive: true });
  await fs.writeFile(REPORT, JSON.stringify(report, null, 2));
}

function probe(command, ...args) {
  const output = execFileSync('php', ['tests/Browser/h1-mutation-probe.php', command, ...args.map(String)], {
    encoding: 'utf8',
    env: process.env,
  }).trim();
  return JSON.parse(output);
}

function monitor(page, label) {
  const events = [];
  const push = (type, detail, extra = {}) => {
    const row = {
      at: new Date().toISOString(),
      actor: label,
      path: pathOnly(page.url()),
      type,
      detail: clean(detail),
      ...extra,
    };
    events.push(row);
    report.diagnostics.push(row);
    if (report.diagnostics.length > 300) report.diagnostics.shift();
  };

  page.on('pageerror', (error) => push('pageerror', error.message));
  page.on('response', (response) => {
    if (response.status() >= 500) {
      push('server-5xx', `${response.status()} ${pathOnly(response.url())}`, { status: response.status() });
    }
  });
  page.on('console', (message) => {
    if (message.type() !== 'error') return;
    const fatal = /(Uncaught|TypeError|ReferenceError|SyntaxError|ChunkLoadError|dynamically imported module)/i.test(message.text());
    if (fatal) push('console-runtime-error', message.text());
  });
  page.on('requestfailed', (request) => {
    if (request.isNavigationRequest()) push('navigation-failure', `${request.method()} ${pathOnly(request.url())}`);
  });

  runtimeByPage.set(page, events);
  return events;
}

async function appReady(page, timeout = 5000) {
  await page.waitForFunction(() => {
    const root = document.getElementById('app');
    const body = document.body?.innerText?.replace(/\s+/g, ' ').trim() || '';
    const rootText = root?.innerText?.replace(/\s+/g, ' ').trim() || '';
    return document.readyState !== 'loading'
      && !!root
      && root.childElementCount > 0
      && body.length >= 20
      && rootText.length >= 10
      && !!document.querySelector('main, h1, form, nav[aria-label]');
  }, null, { timeout });

  return page.evaluate(() => {
    const root = document.getElementById('app');
    const body = document.body?.innerText?.replace(/\s+/g, ' ').trim() || '';
    const rootText = root?.innerText?.replace(/\s+/g, ' ').trim() || '';
    return {
      rootPresent: !!root,
      rootChildren: root?.childElementCount || 0,
      rootTextLength: rootText.length,
      bodyLength: body.length,
    };
  });
}

function base32(input) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  let bits = '';
  for (const character of input.replace(/=+$/g, '').replace(/\s+/g, '').toUpperCase()) {
    const index = alphabet.indexOf(character);
    if (index < 0) throw new Error('Invalid MFA secret');
    bits += index.toString(2).padStart(5, '0');
  }
  const bytes = [];
  for (let index = 0; index + 8 <= bits.length; index += 8) {
    bytes.push(parseInt(bits.slice(index, index + 8), 2));
  }
  return Buffer.from(bytes);
}

function totp(secret) {
  const counter = Buffer.alloc(8);
  counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
  const digest = crypto.createHmac('sha1', base32(secret)).update(counter).digest();
  const offset = digest.at(-1) & 15;
  const number = ((digest[offset] & 127) << 24)
    | (digest[offset + 1] << 16)
    | (digest[offset + 2] << 8)
    | digest[offset + 3];
  return String(number % 1_000_000).padStart(6, '0');
}

async function login(browser, spec) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 850 } });
  const page = await context.newPage();
  monitor(page, spec.label);

  const response = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
  if (response?.status() !== 200) throw new Error(`${spec.label} login page returned ${response?.status()}`);
  await appReady(page);
  await page.getByLabel('Email').fill(spec.email);
  await page.getByLabel('Password').fill(PASSWORD);

  const destinations = spec.privileged
    ? ['/dashboard', '/security/mfa/enroll', '/security/mfa/challenge']
    : ['/dashboard'];

  await Promise.all([
    page.waitForURL((url) => destinations.includes(url.pathname), { timeout: 15000 }),
    page.getByRole('button', { name: 'Sign In' }).click(),
  ]);

  let current = new URL(page.url()).pathname;
  if (current === '/security/mfa/challenge') {
    throw new Error(`${spec.label} unexpectedly required an existing MFA challenge in a fresh isolated database`);
  }

  if (current === '/security/mfa/enroll') {
    const secret = (await page.locator('code').first().innerText()).trim();
    secrets.add(secret);
    const code = totp(secret);
    secrets.add(code);
    await page.getByLabel('Six-digit verification code').fill(code);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/security/mfa/recovery-codes', { timeout: 15000 }),
      page.getByRole('button', { name: /Confirm MFA enrollment/i }).click(),
    ]);
    const recovery = page.locator('pre').first();
    if (await recovery.count()) {
      for (const recoveryCode of (await recovery.innerText()).trim().split(/\s+/)) secrets.add(recoveryCode);
    }
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/dashboard', { timeout: 15000 }),
      page.getByRole('link', { name: /Continue to portal/i }).click(),
    ]);
    current = new URL(page.url()).pathname;
  }

  if (current !== '/dashboard') throw new Error(`${spec.label} did not reach the dashboard`);
  await appReady(page);
  return { context, page };
}

function requestMatches(request, method, pathMatcher) {
  if (request.method() !== method) return false;
  const path = new URL(request.url()).pathname;
  return typeof pathMatcher === 'string' ? path === pathMatcher : pathMatcher.test(path);
}

async function browserMutation(page, { method = 'POST', pathMatcher, action }) {
  const requests = [];
  const responses = [];
  const requestListener = (request) => {
    if (requestMatches(request, method, pathMatcher)) requests.push({ method: request.method(), path: pathOnly(request.url()) });
  };
  const responseListener = (response) => {
    if (requestMatches(response.request(), method, pathMatcher)) responses.push({ status: response.status(), path: pathOnly(response.url()) });
  };
  page.on('request', requestListener);
  page.on('response', responseListener);

  try {
    const firstResponse = page.waitForResponse(
      (response) => requestMatches(response.request(), method, pathMatcher),
      { timeout: 12000 },
    ).catch(() => null);
    await action();
    await firstResponse;
    await page.waitForTimeout(650);
  } finally {
    page.off('request', requestListener);
    page.off('response', responseListener);
  }

  return { requests, responses };
}

function newRow(meta) {
  return {
    exactSha: EXPECTED_SHA,
    environment: ENVIRONMENT,
    scenario: meta.scenario,
    actorRoleLabel: meta.actor,
    mutationName: meta.mutation,
    disposition: meta.disposition || 'H1',
    requestCount: 0,
    responseStatus: null,
    responseStatuses: [],
    finalUrl: null,
    visibleResult: null,
    immediateConvergence: 'FAIL',
    reloadConsistency: 'NOT_RUN',
    duplicateMutationCount: null,
    pageerrorCount: 0,
    http5xxCount: 0,
    result: 'FAIL',
    checks: {},
    database: {},
    failureReason: null,
    screenshot: null,
    _failures: [],
  };
}

function check(row, key, ok, detail = '') {
  row.checks[key] = { pass: Boolean(ok), detail: clean(detail) };
  if (!ok) row._failures.push(detail ? `${key}: ${clean(detail)}` : key);
  return Boolean(ok);
}

function setMutationEvidence(row, evidence) {
  row.requestCount = evidence.requests.length;
  row.responseStatuses = evidence.responses.map((item) => item.status);
  row.responseStatus = evidence.responses[0]?.status ?? null;
  check(row, 'one-request-emitted', row.requestCount === 1, `observed ${row.requestCount} matching requests`);
  check(
    row,
    'expected-response-status',
    row.responseStatus !== null && row.responseStatus >= 200 && row.responseStatus < 400,
    `status=${row.responseStatus}`,
  );
}

async function selectByText(locator, pattern) {
  const options = await locator.locator('option').evaluateAll((nodes) => nodes.map((node) => ({
    value: node.value,
    label: node.textContent?.replace(/\s+/g, ' ').trim() || '',
  })));
  const match = options.find((option) => option.value && pattern.test(option.label));
  if (!match) throw new Error(`No option matched ${pattern}`);
  await locator.selectOption(match.value);
  return match;
}

async function selectFirstValue(locator) {
  const options = await locator.locator('option').evaluateAll((nodes) => nodes.map((node) => ({
    value: node.value,
    label: node.textContent?.replace(/\s+/g, ' ').trim() || '',
  })));
  const match = options.find((option) => option.value);
  if (!match) throw new Error('No selectable option was available');
  await locator.selectOption(match.value);
  return match;
}

async function safeScreenshot(page, row) {
  await fs.mkdir(SHOTS, { recursive: true });
  const slug = row.scenario.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
  const file = `${SHOTS}/${slug}.png`;
  try {
    await page.screenshot({ path: file, animations: 'disabled', fullPage: false });
    report.screenshots.push(file);
    row.screenshot = file;
  } catch (error) {
    report.diagnostics.push({
      at: new Date().toISOString(),
      actor: row.actorRoleLabel,
      path: pathOnly(page.url()),
      type: 'screenshot-failure',
      detail: clean(error.message),
    });
  }
}

async function runScenario(page, meta, body) {
  const row = newRow(meta);
  const runtime = runtimeByPage.get(page) || [];
  const runtimeStart = runtime.length;
  report.scenarios.push(row);
  report.summary.scenarios++;

  try {
    await body(row);
  } catch (error) {
    row._failures.push(`interaction: ${clean(error.message)}`);
  }

  row.finalUrl = pathOnly(page.url());
  const delta = runtime.slice(runtimeStart);
  row.pageerrorCount = delta.filter((item) => item.type === 'pageerror').length;
  row.http5xxCount = delta.filter((item) => item.type === 'server-5xx').length;
  check(row, 'no-pageerror', row.pageerrorCount === 0, `pageerrorCount=${row.pageerrorCount}`);
  check(row, 'no-http-5xx', row.http5xxCount === 0, `http5xxCount=${row.http5xxCount}`);
  const fatalConsole = delta.filter((item) => item.type === 'console-runtime-error').length;
  check(row, 'no-fatal-console-runtime-error', fatalConsole === 0, `fatalConsole=${fatalConsole}`);

  row.failureReason = row._failures.length ? row._failures.join(' | ') : null;
  const deferredH2 = meta.disposition === 'DEFERRED_H2';
  row.result = deferredH2 ? 'DEFERRED_H2' : (row.failureReason ? 'FAIL' : 'PASS');
  delete row._failures;

  if (row.result === 'PASS') {
    report.summary.passed++;
  } else if (row.result === 'DEFERRED_H2') {
    report.summary.deferredH2++;
    report.defects.push({ scenario: row.scenario, disposition: 'DEFERRED_H2', reason: row.failureReason });
    if (row.failureReason && meta.safeScreenshot !== false) await safeScreenshot(page, row);
  } else {
    report.summary.failed++;
    report.summary.failH1++;
    report.defects.push({ scenario: row.scenario, disposition: 'H1', reason: row.failureReason });
    if (meta.safeScreenshot !== false) await safeScreenshot(page, row);
  }

  report.summary.pageerrorCount += row.pageerrorCount;
  report.summary.server5xxCount += row.http5xxCount;
  await writeReport();
  return row;
}

async function reloadAndVerify(page, row, verify) {
  if (row.immediateConvergence === 'NOT_RUN') throw new Error('Reload attempted before immediate-state assertion');
  try {
    await page.reload({ waitUntil: 'domcontentloaded', timeout: 15000 });
    await appReady(page, 6000);
    const ok = await verify();
    row.reloadConsistency = ok ? 'PASS' : 'FAIL';
    check(row, 'reload-consistency', ok, `final=${pathOnly(page.url())}`);
  } catch (error) {
    row.reloadConsistency = 'FAIL';
    check(row, 'reload-consistency', false, error.message);
  }
}

async function main() {
  report.git.actualSha = execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
  report.git.exactHead = report.git.actualSha === EXPECTED_SHA;
  if (!report.git.exactHead) {
    report.failure = { stage: 'exact-head', summary: 'checked-out SHA differs from QA_EXPECTED_SHA' };
    await writeReport();
    process.exitCode = 1;
    return;
  }

  report.isolation = probe('isolation');
  if (!report.isolation.isolated || report.isolation.database !== 'talibon_h1_mutations') {
    report.failure = { stage: 'isolation', summary: 'H1 destructive QA isolation could not be proven' };
    await writeReport();
    process.exitCode = 1;
    return;
  }
  probe('setup', marker);

  const browser = await chromium.launch({ headless: true });
  const sessions = [];
  const shared = {};

  try {
    const engineering = await login(browser, { email: 'engineering@talibon.demo', label: 'Engineering Department Head', privileged: true });
    const budget = await login(browser, { email: 'budget@talibon.demo', label: 'Budget Department Head', privileged: true });
    const mayor = await login(browser, { email: 'mayor@talibon.demo', label: 'Mayor Approver', privileged: true });
    const employee = await login(browser, { email: 'employee@talibon.demo', label: 'Employee', privileged: false });
    sessions.push(engineering, budget, mayor, employee);

    await runScenario(engineering.page, {
      scenario: 'transactions-create',
      actor: 'Engineering Department Head',
      mutation: 'Create and route transaction',
    }, async (row) => {
      const before = probe('transaction', transactionTitle);
      row.database.before = before;
      const response = await engineering.page.goto(`${BASE}/transactions/create`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(engineering.page);
      await engineering.page.getByLabel('Subject').fill(transactionTitle);
      await engineering.page.getByLabel('Description').fill('Synthetic H1 browser mutation acceptance transaction.');
      await selectByText(engineering.page.getByLabel('Receiving office'), /Budget/i);

      const evidence = await browserMutation(engineering.page, {
        pathMatcher: '/transactions',
        action: () => engineering.page.getByRole('button', { name: /Route transaction/i }).click(),
      });
      setMutationEvidence(row, evidence);
      await engineering.page.getByRole('heading', { name: transactionTitle }).waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(engineering.page, 2500).catch(() => null);
      const after = probe('transaction', transactionTitle);
      row.database.after = after;
      shared.transactionId = after.id;
      shared.transactionUrl = after.id ? `/transactions/${after.id}` : null;
      const visible = await engineering.page.getByRole('heading', { name: transactionTitle }).isVisible().catch(() => false);
      const flashVisible = await engineering.page.getByText(/was routed successfully/i).isVisible().catch(() => false);
      row.visibleResult = visible && flashVisible ? 'Created transaction and success message are visible immediately.' : 'Expected created transaction/success message not visible immediately.';
      const immediate = visible
        && flashVisible
        && !!ready
        && /^\/transactions\/\d+$/.test(pathOnly(engineering.page.url()))
        && before.count === 0
        && after.count === 1
        && after.eventCount === 1
        && after.status === 'submitted'
        && after.currentDepartmentCode === 'BUDGET';
      row.duplicateMutationCount = Math.max(0, after.count - before.count - 1);
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'create-action-left-behind', await engineering.page.getByRole('button', { name: /Route transaction/i }).count() === 0, 'create form action should not remain on detail page');

      await reloadAndVerify(engineering.page, row, async () => {
        const reloadState = probe('transaction', transactionTitle);
        row.database.reload = reloadState;
        return await engineering.page.getByRole('heading', { name: transactionTitle }).isVisible().catch(() => false)
          && reloadState.count === 1
          && reloadState.eventCount === 1
          && reloadState.status === 'submitted';
      });
    });

    await runScenario(budget.page, {
      scenario: 'transactions-mark-review-double-click',
      actor: 'Budget Department Head',
      mutation: 'Mark transaction for review with rapid duplicate interaction',
    }, async (row) => {
      if (!shared.transactionUrl) throw new Error('Dependency failed: created transaction URL unavailable');
      const response = await budget.page.goto(`${BASE}${shared.transactionUrl}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(budget.page);
      const button = budget.page.getByRole('button', { name: /Mark for Review/i });
      await button.waitFor({ state: 'visible', timeout: 5000 });
      const before = probe('transaction', transactionTitle);
      row.database.before = before;

      const evidence = await browserMutation(budget.page, {
        pathMatcher: new RegExp(`^/transactions/${shared.transactionId}/transition$`),
        action: () => button.click({ clickCount: 2, delay: 0 }),
      });
      setMutationEvidence(row, evidence);
      await budget.page.getByText('for review', { exact: true }).first().waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(budget.page, 2500).catch(() => null);
      const after = probe('transaction', transactionTitle);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const visible = await budget.page.getByText('for review', { exact: true }).first().isVisible().catch(() => false);
      row.visibleResult = visible ? 'For review state is visible immediately.' : 'For review state is not visible immediately.';
      const immediate = visible && !!ready && after.status === 'for_review' && after.currentDepartmentCode === 'BUDGET';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(budget.page, row, async () => {
        const reloadState = probe('transaction', transactionTitle);
        row.database.reload = reloadState;
        return await budget.page.getByText('for review', { exact: true }).first().isVisible().catch(() => false)
          && reloadState.status === 'for_review'
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(mayor.page, {
      scenario: 'transactions-mayor-approve',
      actor: 'Mayor Approver',
      mutation: 'Approve deterministic seeded transaction',
    }, async (row) => {
      const before = probe('transaction', MAYOR_SEEDED_TRANSACTION);
      row.database.before = before;
      if (!before.id) throw new Error('Seeded Mayor-decision transaction missing');
      const response = await mayor.page.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(mayor.page);
      const button = mayor.page.getByRole('button', { name: 'Approve', exact: true });
      await button.waitFor({ state: 'visible', timeout: 5000 });

      const evidence = await browserMutation(mayor.page, {
        pathMatcher: new RegExp(`^/transactions/${before.id}/transition$`),
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      await mayor.page.getByText('approved', { exact: true }).first().waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(mayor.page, 2500).catch(() => null);
      const after = probe('transaction', MAYOR_SEEDED_TRANSACTION);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const visible = await mayor.page.getByText('approved', { exact: true }).first().isVisible().catch(() => false);
      const actionGone = await mayor.page.getByRole('button', { name: 'Approve', exact: true }).count() === 0;
      row.visibleResult = visible ? 'Approved terminal state is visible immediately.' : 'Approved terminal state is not visible immediately.';
      const immediate = visible && actionGone && !!ready && after.status === 'approved' && eventDelta === 1;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'terminal-action-removed', actionGone, 'Approve action remains available after terminal approval');
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);

      await reloadAndVerify(mayor.page, row, async () => {
        const reloadState = probe('transaction', MAYOR_SEEDED_TRANSACTION);
        row.database.reload = reloadState;
        return await mayor.page.getByText('approved', { exact: true }).first().isVisible().catch(() => false)
          && await mayor.page.getByRole('button', { name: 'Approve', exact: true }).count() === 0
          && reloadState.status === 'approved'
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(budget.page, {
      scenario: 'transactions-assign',
      actor: 'Budget Department Head',
      mutation: 'Assign responsible same-office employee',
    }, async (row) => {
      const before = probe('transaction', assignTitle);
      row.database.before = before;
      if (!before.id) throw new Error('H3 assignment fixture missing');
      const response = await budget.page.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(budget.page);
      const assignmentSelect = budget.page.locator('select').filter({ hasText: 'Choose employee' }).first();
      await assignmentSelect.waitFor({ state: 'visible', timeout: 5000 });
      const selected = await selectFirstValue(assignmentSelect);
      const button = budget.page.getByRole('button', { name: 'Assign', exact: true });

      const evidence = await browserMutation(budget.page, {
        pathMatcher: new RegExp(`^/transactions/${before.id}/transition$`),
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      const ready = await appReady(budget.page, 2500).catch(() => null);
      const after = probe('transaction', assignTitle);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const officerBlock = budget.page.getByText('Responsible Officer', { exact: true }).locator('..');
      const assignedVisible = !(await officerBlock.innerText().catch(() => 'Unassigned')).includes('Unassigned');
      const eventTruth = after.latestEvent?.action === 'assign'
        && after.latestEvent?.previousStatus === 'for_review'
        && after.latestEvent?.newStatus === 'for_review'
        && after.latestEvent?.fromDepartmentCode === 'BUDGET'
        && after.latestEvent?.toDepartmentCode === 'BUDGET';
      const immediate = assignedVisible
        && !!ready
        && after.status === 'for_review'
        && after.currentDepartmentCode === 'BUDGET'
        && after.assignedEmployeeId === Number(selected.value)
        && eventDelta === 1
        && eventTruth;
      row.visibleResult = assignedVisible ? 'Assigned responsible officer is visible immediately.' : 'Responsible officer did not converge immediately.';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'same-office-assignment-authoritative', immediate, JSON.stringify(after));
      check(row, 'office-unchanged', before.currentDepartmentCode === 'BUDGET' && after.currentDepartmentCode === before.currentDepartmentCode, `before=${before.currentDepartmentCode} after=${after.currentDepartmentCode}`);
      check(row, 'assignee-changed-correctly', before.assignedEmployeeId === null && after.assignedEmployeeId === Number(selected.value), `selected=${selected.value} assigned=${after.assignedEmployeeId}`);
      check(row, 'truthful-assignment-event', eventTruth, JSON.stringify(after.latestEvent));
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(budget.page, row, async () => {
        const reloadState = probe('transaction', assignTitle);
        row.database.reload = reloadState;
        const block = budget.page.getByText('Responsible Officer', { exact: true }).locator('..');
        return !(await block.innerText().catch(() => 'Unassigned')).includes('Unassigned')
          && reloadState.currentDepartmentCode === 'BUDGET'
          && reloadState.assignedEmployeeId === Number(selected.value)
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(budget.page, {
      scenario: 'transactions-forward-handoff',
      actor: 'Budget Department Head',
      mutation: 'Forward transaction to another office',
    }, async (row) => {
      const before = probe('transaction', forwardTitle);
      row.database.before = before;
      if (!before.id) throw new Error('H3 forward fixture missing');
      const response = await budget.page.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(budget.page);
      const targetSelect = budget.page.locator('select').filter({ hasText: 'Forward to department' }).first();
      await selectByText(targetSelect, /Mayor/i);
      const button = budget.page.getByRole('button', { name: 'Forward', exact: true });

      const evidence = await browserMutation(budget.page, {
        pathMatcher: new RegExp(`^/transactions/${before.id}/transition$`),
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      const ready = await appReady(budget.page, 2500).catch(() => null);
      const after = probe('transaction', forwardTitle);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const eventTruth = after.latestEvent?.action === 'forward'
        && after.latestEvent?.previousStatus === 'for_review'
        && after.latestEvent?.newStatus === 'submitted'
        && after.latestEvent?.fromDepartmentCode === 'BUDGET'
        && after.latestEvent?.toDepartmentCode === 'MAYOR';
      const authorizedDestination = pathOnly(budget.page.url()) === '/transactions';
      const formerOfficePage = await budget.context.newPage();
      const formerOfficeResponse = await formerOfficePage.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const formerOfficeDenied = formerOfficeResponse?.status() === 403;
      await formerOfficePage.close();
      const targetResponse = await mayor.page.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const targetReady = await appReady(mayor.page, 2500).catch(() => null);
      const targetVisible = await mayor.page.getByRole('heading', { name: forwardTitle }).isVisible().catch(() => false);
      const immediate = !!ready
        && authorizedDestination
        && formerOfficeDenied
        && targetResponse?.status() === 200
        && !!targetReady
        && targetVisible
        && after.status === 'submitted'
        && after.currentDepartmentCode === 'MAYOR'
        && after.assignedEmployeeId === null
        && after.receivedAt !== before.receivedAt
        && eventDelta === 1
        && eventTruth;
      row.visibleResult = immediate ? 'Forward handoff converged to the target office with truthful access immediately.' : 'Forward handoff did not fully converge.';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'handoff-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'assignment-cleared', after.assignedEmployeeId === null, `assignedEmployeeId=${after.assignedEmployeeId}`);
      check(row, 'received-at-refreshed', !!after.receivedAt && after.receivedAt !== before.receivedAt, `before=${before.receivedAt} after=${after.receivedAt}`);
      check(row, 'truthful-forward-event', eventTruth, JSON.stringify(after.latestEvent));
      check(row, 'previous-current-access-truthful', formerOfficeDenied, `status=${formerOfficeResponse?.status()}`);
      check(row, 'target-access-truthful', targetResponse?.status() === 200 && targetVisible, `status=${targetResponse?.status()}`);
      check(row, 'authorized-resulting-url', authorizedDestination, `path=${pathOnly(budget.page.url())}`);
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(mayor.page, row, async () => {
        const reloadState = probe('transaction', forwardTitle);
        row.database.reload = reloadState;
        return await mayor.page.getByRole('heading', { name: forwardTitle }).isVisible().catch(() => false)
          && reloadState.status === 'submitted'
          && reloadState.currentDepartmentCode === 'MAYOR'
          && reloadState.assignedEmployeeId === null
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(budget.page, {
      scenario: 'transactions-return-origin',
      actor: 'Budget Department Head',
      mutation: 'Return transaction to origin office',
    }, async (row) => {
      const before = probe('transaction', returnTitle);
      row.database.before = before;
      if (!before.id) throw new Error('H3 return fixture missing');
      const response = await budget.page.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(budget.page);
      const button = budget.page.getByRole('button', { name: /Return to Origin/i });
      await button.waitFor({ state: 'visible', timeout: 5000 });

      const evidence = await browserMutation(budget.page, {
        pathMatcher: new RegExp(`^/transactions/${before.id}/transition$`),
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      const ready = await appReady(budget.page, 2500).catch(() => null);
      const after = probe('transaction', returnTitle);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const eventTruth = after.latestEvent?.action === 'return_origin'
        && after.latestEvent?.previousStatus === 'for_review'
        && after.latestEvent?.newStatus === 'returned'
        && after.latestEvent?.fromDepartmentCode === 'BUDGET'
        && after.latestEvent?.toDepartmentCode === 'ENG';
      const authorizedDestination = pathOnly(budget.page.url()) === '/transactions';
      const formerOfficePage = await budget.context.newPage();
      const formerOfficeResponse = await formerOfficePage.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const formerOfficeDenied = formerOfficeResponse?.status() === 403;
      await formerOfficePage.close();
      const originResponse = await engineering.page.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const originReady = await appReady(engineering.page, 2500).catch(() => null);
      const originVisible = await engineering.page.getByRole('heading', { name: returnTitle }).isVisible().catch(() => false);
      const immediate = !!ready
        && authorizedDestination
        && formerOfficeDenied
        && originResponse?.status() === 200
        && !!originReady
        && originVisible
        && after.status === 'returned'
        && after.currentDepartmentCode === 'ENG'
        && after.assignedEmployeeId === null
        && after.receivedAt !== before.receivedAt
        && eventDelta === 1
        && eventTruth;
      row.visibleResult = immediate ? 'Return-to-origin handoff restored origin accountability immediately.' : 'Return-to-origin handoff did not fully converge.';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'return-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'assignment-cleared', after.assignedEmployeeId === null, `assignedEmployeeId=${after.assignedEmployeeId}`);
      check(row, 'received-at-refreshed', !!after.receivedAt && after.receivedAt !== before.receivedAt, `before=${before.receivedAt} after=${after.receivedAt}`);
      check(row, 'truthful-return-event', eventTruth, JSON.stringify(after.latestEvent));
      check(row, 'previous-current-access-truthful', formerOfficeDenied, `status=${formerOfficeResponse?.status()}`);
      check(row, 'origin-visibility-truthful', originResponse?.status() === 200 && originVisible, `status=${originResponse?.status()}`);
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(engineering.page, row, async () => {
        const reloadState = probe('transaction', returnTitle);
        row.database.reload = reloadState;
        return await engineering.page.getByRole('heading', { name: returnTitle }).isVisible().catch(() => false)
          && reloadState.status === 'returned'
          && reloadState.currentDepartmentCode === 'ENG'
          && reloadState.assignedEmployeeId === null
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(mayor.page, {
      scenario: 'transactions-request-information',
      actor: 'Mayor Approver',
      mutation: 'Request information after legitimate browser prerequisites',
    }, async (row) => {
      const initial = probe('transaction', requestInformationTitle);
      row.database.before = initial;
      check(row, 'fresh-request-information-record', initial.count === 0, `count=${initial.count}`);

      await engineering.page.goto(`${BASE}/transactions/create`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await appReady(engineering.page);
      await engineering.page.getByLabel('Subject').fill(requestInformationTitle);
      await engineering.page.getByLabel('Description').fill('Synthetic H3 request-information acceptance transaction.');
      await selectByText(engineering.page.getByLabel('Receiving office'), /Budget/i);
      const createEvidence = await browserMutation(engineering.page, {
        pathMatcher: '/transactions',
        action: () => engineering.page.getByRole('button', { name: /Route transaction/i }).click(),
      });
      check(row, 'prerequisite-create-one-request', createEvidence.requests.length === 1, `requests=${createEvidence.requests.length}`);
      check(row, 'prerequisite-create-success', createEvidence.responses[0]?.status >= 200 && createEvidence.responses[0]?.status < 400, `status=${createEvidence.responses[0]?.status ?? null}`);
      await appReady(engineering.page, 2500);
      const created = probe('transaction', requestInformationTitle);
      if (!created.id) throw new Error('Request-information prerequisite create failed');
      check(row, 'prerequisite-created-submitted', created.count === 1 && created.status === 'submitted' && created.currentDepartmentCode === 'BUDGET' && created.eventCount === 1, JSON.stringify(created));

      await budget.page.goto(`${BASE}/transactions/${created.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await appReady(budget.page);
      const reviewButton = budget.page.getByRole('button', { name: /Mark for Review/i });
      await reviewButton.waitFor({ state: 'visible', timeout: 5000 });
      const reviewEvidence = await browserMutation(budget.page, {
        pathMatcher: new RegExp(`^/transactions/${created.id}/transition$`),
        action: () => reviewButton.click(),
      });
      check(row, 'prerequisite-review-one-request', reviewEvidence.requests.length === 1, `requests=${reviewEvidence.requests.length}`);
      check(row, 'prerequisite-review-success', reviewEvidence.responses[0]?.status >= 200 && reviewEvidence.responses[0]?.status < 400, `status=${reviewEvidence.responses[0]?.status ?? null}`);
      await budget.page.getByText('for review', { exact: true }).first().waitFor({ state: 'visible', timeout: 2500 }).catch(() => {});
      await appReady(budget.page, 2500);
      const reviewed = probe('transaction', requestInformationTitle);
      check(row, 'prerequisite-reviewed', reviewed.status === 'for_review' && reviewed.currentDepartmentCode === 'BUDGET' && reviewed.eventCount === created.eventCount + 1, JSON.stringify(reviewed));

      const sendButton = budget.page.getByRole('button', { name: "Send to Mayor's Office", exact: true });
      await sendButton.waitFor({ state: 'visible', timeout: 5000 });
      const sendEvidence = await browserMutation(budget.page, {
        pathMatcher: new RegExp(`^/transactions/${created.id}/transition$`),
        action: () => sendButton.click(),
      });
      check(row, 'prerequisite-mayor-route-one-request', sendEvidence.requests.length === 1, `requests=${sendEvidence.requests.length}`);
      check(row, 'prerequisite-mayor-route-success', sendEvidence.responses[0]?.status >= 200 && sendEvidence.responses[0]?.status < 400, `status=${sendEvidence.responses[0]?.status ?? null}`);
      await appReady(budget.page, 2500).catch(() => null);
      const before = probe('transaction', requestInformationTitle);
      row.database.prerequisite = before;
      check(row, 'prerequisite-for-mayor-decision', before.status === 'for_approval' && before.currentDepartmentCode === 'MAYOR' && before.assignedEmployeeId === null && before.eventCount === reviewed.eventCount + 1, JSON.stringify(before));

      const receivedBoundary = Date.parse(before.receivedAt || '');
      if (Number.isFinite(receivedBoundary)) {
        await mayor.page.waitForFunction((threshold) => Date.now() > threshold, receivedBoundary + 1000, { timeout: 2500 });
      }

      const mayorResponse = await mayor.page.goto(`${BASE}/transactions/${created.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'mayor-starting-page-200', mayorResponse?.status() === 200, `status=${mayorResponse?.status()}`);
      await appReady(mayor.page);
      const button = mayor.page.getByRole('button', { name: 'Request Information', exact: true });
      await button.waitFor({ state: 'visible', timeout: 5000 });

      const evidence = await browserMutation(mayor.page, {
        pathMatcher: new RegExp(`^/transactions/${created.id}/transition$`),
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      const mayorReady = await appReady(mayor.page, 2500).catch(() => null);
      const after = probe('transaction', requestInformationTitle);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const eventTruth = after.latestEvent?.action === 'request_information'
        && after.latestEvent?.previousStatus === 'for_approval'
        && after.latestEvent?.newStatus === 'information_requested'
        && after.latestEvent?.fromDepartmentCode === 'MAYOR'
        && after.latestEvent?.toDepartmentCode === 'ENG';
      const originResponse = await engineering.page.goto(`${BASE}/transactions/${created.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const originReady = await appReady(engineering.page, 2500).catch(() => null);
      const originVisible = await engineering.page.getByRole('heading', { name: requestInformationTitle }).isVisible().catch(() => false);
      const originStatusVisible = await engineering.page.getByText('information requested', { exact: true }).first().isVisible().catch(() => false);
      const immediate = !!mayorReady
        && originResponse?.status() === 200
        && !!originReady
        && originVisible
        && originStatusVisible
        && after.status === 'information_requested'
        && after.currentDepartmentCode === 'ENG'
        && after.assignedEmployeeId === null
        && after.receivedAt !== before.receivedAt
        && eventDelta === 1
        && eventTruth;
      row.visibleResult = immediate ? 'Information request returned accountability to origin immediately.' : 'Information-request accountability did not fully converge.';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'information-request-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'assignment-cleared', after.assignedEmployeeId === null, `assignedEmployeeId=${after.assignedEmployeeId}`);
      check(row, 'received-at-refreshed', !!after.receivedAt && after.receivedAt !== before.receivedAt, `before=${before.receivedAt} after=${after.receivedAt}`);
      check(row, 'origin-regained-accountability', originResponse?.status() === 200 && originVisible && originStatusVisible, `status=${originResponse?.status()}`);
      check(row, 'truthful-request-information-event', eventTruth, JSON.stringify(after.latestEvent));
      check(row, 'exactly-one-request-information-event', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(engineering.page, row, async () => {
        const reloadState = probe('transaction', requestInformationTitle);
        row.database.reload = reloadState;
        return await engineering.page.getByRole('heading', { name: requestInformationTitle }).isVisible().catch(() => false)
          && await engineering.page.getByText('information requested', { exact: true }).first().isVisible().catch(() => false)
          && reloadState.status === 'information_requested'
          && reloadState.currentDepartmentCode === 'ENG'
          && reloadState.assignedEmployeeId === null
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(mayor.page, {
      scenario: 'transactions-mayor-disapprove',
      actor: 'Mayor Approver',
      mutation: 'Disapprove deterministic H3 transaction',
    }, async (row) => {
      const before = probe('transaction', disapproveTitle);
      row.database.before = before;
      if (!before.id) throw new Error('H3 Mayor disapprove fixture missing');
      const response = await mayor.page.goto(`${BASE}/transactions/${before.id}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(mayor.page);
      const button = mayor.page.getByRole('button', { name: 'Disapprove', exact: true });
      await button.waitFor({ state: 'visible', timeout: 5000 });

      const evidence = await browserMutation(mayor.page, {
        pathMatcher: new RegExp(`^/transactions/${before.id}/transition$`),
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      await mayor.page.getByText('disapproved', { exact: true }).first().waitFor({ state: 'visible', timeout: 2500 }).catch(() => {});
      const ready = await appReady(mayor.page, 2500).catch(() => null);
      const after = probe('transaction', disapproveTitle);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const visible = await mayor.page.getByText('disapproved', { exact: true }).first().isVisible().catch(() => false);
      const approveGone = await mayor.page.getByRole('button', { name: 'Approve', exact: true }).count() === 0;
      const disapproveGone = await mayor.page.getByRole('button', { name: 'Disapprove', exact: true }).count() === 0;
      const requestGone = await mayor.page.getByRole('button', { name: 'Request Information', exact: true }).count() === 0;
      const eventTruth = after.latestEvent?.action === 'disapprove'
        && after.latestEvent?.previousStatus === 'for_approval'
        && after.latestEvent?.newStatus === 'disapproved'
        && after.latestEvent?.fromDepartmentCode === 'MAYOR'
        && after.latestEvent?.toDepartmentCode === 'MAYOR';
      const immediate = visible
        && approveGone
        && disapproveGone
        && requestGone
        && !!ready
        && after.status === 'disapproved'
        && !!after.completedAt
        && eventDelta === 1
        && eventTruth;
      row.visibleResult = visible ? 'Disapproved terminal state is visible immediately.' : 'Disapproved terminal state is not visible immediately.';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'completed-at-populated', !!after.completedAt, `completedAt=${after.completedAt}`);
      check(row, 'truthful-disapprove-event', eventTruth, JSON.stringify(after.latestEvent));
      check(row, 'terminal-controls-removed', approveGone && disapproveGone && requestGone, `approve=${approveGone} disapprove=${disapproveGone} request=${requestGone}`);
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(mayor.page, row, async () => {
        const reloadState = probe('transaction', disapproveTitle);
        row.database.reload = reloadState;
        return await mayor.page.getByText('disapproved', { exact: true }).first().isVisible().catch(() => false)
          && await mayor.page.getByRole('button', { name: 'Approve', exact: true }).count() === 0
          && await mayor.page.getByRole('button', { name: 'Disapprove', exact: true }).count() === 0
          && await mayor.page.getByRole('button', { name: 'Request Information', exact: true }).count() === 0
          && reloadState.status === 'disapproved'
          && !!reloadState.completedAt
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(engineering.page, {
      scenario: 'correspondence-register',
      actor: 'Engineering Department Head',
      mutation: 'Register received correspondence',
    }, async (row) => {
      const url = `/correspondence/${CORRESPONDENCE_ID}/workspace`;
      const response = await engineering.page.goto(`${BASE}${url}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(engineering.page);
      const before = probe('correspondence', CORRESPONDENCE_ID);
      row.database.before = before;
      const button = engineering.page.getByRole('button', { name: 'Register Correspondence', exact: true });
      await button.waitFor({ state: 'visible', timeout: 5000 });
      engineering.page.once('dialog', (dialog) => dialog.accept());
      const evidence = await browserMutation(engineering.page, {
        pathMatcher: `/correspondence/${CORRESPONDENCE_ID}/workspace/register`,
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      await engineering.page.getByRole('heading', { name: 'Classify Correspondence' }).waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(engineering.page, 2500).catch(() => null);
      const after = probe('correspondence', CORRESPONDENCE_ID);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const nextAction = await engineering.page.getByRole('heading', { name: 'Classify Correspondence' }).isVisible().catch(() => false);
      const priorGone = await engineering.page.getByRole('button', { name: 'Register Correspondence', exact: true }).count() === 0;
      row.visibleResult = nextAction ? 'Registered state converged to the Classify Correspondence action immediately.' : 'Registered state did not converge immediately.';
      const immediate = nextAction && priorGone && !!ready && after.lifecycle === 'registered' && eventDelta === 1;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'previous-action-removed', priorGone, 'Register action remains available');
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);

      await reloadAndVerify(engineering.page, row, async () => {
        const reloadState = probe('correspondence', CORRESPONDENCE_ID);
        row.database.reload = reloadState;
        return await engineering.page.getByRole('heading', { name: 'Classify Correspondence' }).isVisible().catch(() => false)
          && reloadState.lifecycle === 'registered'
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(engineering.page, {
      scenario: 'correspondence-classify',
      actor: 'Engineering Department Head',
      mutation: 'Classify registered correspondence',
    }, async (row) => {
      const before = probe('correspondence', CORRESPONDENCE_ID);
      row.database.before = before;
      const classify = engineering.page.getByRole('heading', { name: 'Classify Correspondence' });
      if (!await classify.isVisible().catch(() => false)) {
        await engineering.page.goto(`${BASE}/correspondence/${CORRESPONDENCE_ID}/workspace`, { waitUntil: 'domcontentloaded' });
        await appReady(engineering.page);
      }
      await engineering.page.getByLabel('Classification').selectOption('internal');
      const button = engineering.page.getByRole('button', { name: 'Save Classification', exact: true });
      const evidence = await browserMutation(engineering.page, {
        pathMatcher: `/correspondence/${CORRESPONDENCE_ID}/workspace/classify`,
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      await engineering.page.getByRole('heading', { name: 'Route Correspondence' }).waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(engineering.page, 2500).catch(() => null);
      const after = probe('correspondence', CORRESPONDENCE_ID);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const nextAction = await engineering.page.getByRole('heading', { name: 'Route Correspondence' }).isVisible().catch(() => false);
      const priorGone = await engineering.page.getByRole('button', { name: 'Save Classification', exact: true }).count() === 0;
      row.visibleResult = nextAction ? 'Classified state converged to the Route Correspondence action immediately.' : 'Classified state did not converge immediately.';
      const immediate = nextAction && priorGone && !!ready && after.lifecycle === 'classified' && after.classification === 'internal' && eventDelta === 1;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'previous-action-removed', priorGone, 'Classify action remains available');
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);

      await reloadAndVerify(engineering.page, row, async () => {
        const reloadState = probe('correspondence', CORRESPONDENCE_ID);
        row.database.reload = reloadState;
        return await engineering.page.getByRole('heading', { name: 'Route Correspondence' }).isVisible().catch(() => false)
          && reloadState.lifecycle === 'classified'
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(engineering.page, {
      scenario: 'correspondence-route-double-click',
      actor: 'Engineering Department Head',
      mutation: 'Route correspondence with rapid duplicate interaction',
    }, async (row) => {
      const before = probe('correspondence', CORRESPONDENCE_ID);
      row.database.before = before;
      if (!await engineering.page.getByRole('heading', { name: 'Route Correspondence' }).isVisible().catch(() => false)) {
        await engineering.page.goto(`${BASE}/correspondence/${CORRESPONDENCE_ID}/workspace`, { waitUntil: 'domcontentloaded' });
        await appReady(engineering.page);
      }
      await selectByText(engineering.page.getByLabel('Destination Office'), /Budget/i);
      const button = engineering.page.getByRole('button', { name: 'Route Correspondence', exact: true });
      const evidence = await browserMutation(engineering.page, {
        pathMatcher: `/correspondence/${CORRESPONDENCE_ID}/workspace/route`,
        action: () => button.click({ clickCount: 2, delay: 0 }),
      });
      setMutationEvidence(row, evidence);
      await engineering.page.getByText('Correspondence routed successfully.', { exact: true }).waitFor({ state: 'visible', timeout: 2500 }).catch(() => {});
      const ready = await appReady(engineering.page, 2500).catch(() => null);
      const after = probe('correspondence', CORRESPONDENCE_ID);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      const workflowEventDelta = after.workflowEventCount - before.workflowEventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1, workflowEventDelta - 1);

      const successVisible = await engineering.page.getByText('Correspondence routed successfully.', { exact: true }).isVisible().catch(() => false);
      const authorizedDestination = pathOnly(engineering.page.url()) === '/correspondence';
      const denialVisible = await engineering.page.getByRole('heading', { name: /cannot open this page/i }).isVisible().catch(() => false);
      const originStillListsRecord = await engineering.page.getByText('H1 mutation correspondence acceptance', { exact: true }).count() > 0;
      const originReopen = await engineering.context.request.get(`${BASE}/correspondence/${CORRESPONDENCE_ID}/workspace`);

      const budgetRuntime = runtimeByPage.get(budget.page) || [];
      const budgetRuntimeStart = budgetRuntime.length;
      const destinationResponse = await budget.page.goto(`${BASE}/correspondence/${CORRESPONDENCE_ID}/workspace`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      const destinationReady = await appReady(budget.page, 2500).catch(() => null);
      const destinationRouted = await budget.page.getByText('Routed', { exact: true }).first().isVisible().catch(() => false);
      const destinationWorkflow = after.workflowReference
        ? await budget.page.getByText(after.workflowReference, { exact: true }).first().isVisible().catch(() => false)
        : false;
      const preparationVisible = await budget.page.getByText('Prepare the linked workflow for action', { exact: true }).isVisible().catch(() => false);
      const destinationRuntimeDelta = budgetRuntime.slice(budgetRuntimeStart);
      const destinationRuntimeClean = destinationRuntimeDelta.every((item) => !['pageerror', 'server-5xx', 'console-runtime-error'].includes(item.type));

      const authoritative = after.lifecycle === 'routed'
        && after.workflowId !== null
        && after.workflowStatus === 'submitted'
        && after.workflowDepartmentCode === 'BUDGET'
        && eventDelta === 1
        && workflowEventDelta === 1;
      const immediate = successVisible
        && authorizedDestination
        && !denialVisible
        && !originStillListsRecord
        && originReopen.status() === 403
        && destinationResponse?.status() === 200
        && destinationRouted
        && destinationWorkflow
        && preparationVisible
        && destinationRuntimeClean
        && !!destinationReady
        && !!ready
        && authoritative;

      row.visibleResult = immediate
        ? 'Routing returns the origin actor to the authorized correspondence inbox while the Budget office immediately owns and can open the routed record.'
        : 'Post-route ownership, authorized destination, or destination-office visibility did not converge immediately.';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'database-routed-exactly-once', authoritative, JSON.stringify(after));
      check(row, 'origin-post-route-destination-authorized', authorizedDestination && successVisible && !denialVisible, `path=${pathOnly(engineering.page.url())}`);
      check(row, 'origin-no-longer-owns-current-work', !originStillListsRecord && originReopen.status() === 403, `reopenStatus=${originReopen.status()}`);
      check(row, 'destination-office-can-open-routed-record', destinationResponse?.status() === 200 && destinationRouted && destinationWorkflow && preparationVisible && !!destinationReady, `status=${destinationResponse?.status()}`);
      check(row, 'destination-runtime-clean', destinationRuntimeClean, `diagnostics=${destinationRuntimeDelta.length}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(engineering.page, row, async () => {
        const reloadState = probe('correspondence', CORRESPONDENCE_ID);
        row.database.reload = reloadState;
        const stillAuthorized = pathOnly(engineering.page.url()) === '/correspondence';
        const stillAbsent = await engineering.page.getByText('H1 mutation correspondence acceptance', { exact: true }).count() === 0;
        return stillAuthorized
          && stillAbsent
          && reloadState.lifecycle === 'routed'
          && reloadState.workflowStatus === 'submitted'
          && reloadState.workflowDepartmentCode === 'BUDGET'
          && reloadState.eventCount === after.eventCount
          && reloadState.workflowEventCount === after.workflowEventCount;
      });
    });

    await runScenario(budget.page, {
      scenario: 'correspondence-begin-action',
      actor: 'Budget Department Head',
      mutation: 'Prepare linked workflow and begin action on routed correspondence',
    }, async (row) => {
      const routed = probe('correspondence', CORRESPONDENCE_ID);
      row.database.before = routed;
      if (routed.lifecycle !== 'routed') throw new Error(`Dependency failed: correspondence lifecycle is ${routed.lifecycle}`);
      if (!routed.workflowId) throw new Error('Dependency failed: linked workflow missing');

      const response = await budget.page.goto(`${BASE}/correspondence/${CORRESPONDENCE_ID}/workspace`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(budget.page);

      const startUnavailable = await budget.page.getByRole('button', { name: 'Start Action', exact: true }).count() === 0;
      const preparationVisible = await budget.page.getByText('Prepare the linked workflow for action', { exact: true }).isVisible().catch(() => false);
      const linkedWorkflow = budget.page.getByRole('link', { name: 'Open linked workflow', exact: true }).first();
      const linkVisible = await linkedWorkflow.isVisible().catch(() => false);
      check(row, 'start-action-unavailable-before-prerequisite', startUnavailable && preparationVisible && linkVisible && routed.workflowStatus === 'submitted', JSON.stringify(routed));

      await Promise.all([
        budget.page.waitForURL((url) => url.pathname === `/transactions/${routed.workflowId}`, { timeout: 10000 }),
        linkedWorkflow.click(),
      ]);
      await appReady(budget.page);
      const reviewButton = budget.page.getByRole('button', { name: /Mark for Review/i });
      await reviewButton.waitFor({ state: 'visible', timeout: 5000 });

      const prerequisiteEvidence = await browserMutation(budget.page, {
        pathMatcher: new RegExp(`^/transactions/${routed.workflowId}/transition$`),
        action: () => reviewButton.click(),
      });
      check(row, 'prerequisite-one-request-emitted', prerequisiteEvidence.requests.length === 1, `observed ${prerequisiteEvidence.requests.length} requests`);
      check(row, 'prerequisite-response-success', prerequisiteEvidence.responses[0]?.status >= 200 && prerequisiteEvidence.responses[0]?.status < 400, `status=${prerequisiteEvidence.responses[0]?.status ?? null}`);
      await budget.page.getByText('for review', { exact: true }).first().waitFor({ state: 'visible', timeout: 2500 }).catch(() => {});
      await appReady(budget.page, 2500);

      const prepared = probe('correspondence', CORRESPONDENCE_ID);
      row.database.prerequisite = prepared;
      const prerequisiteWorkflowDelta = prepared.workflowEventCount - routed.workflowEventCount;
      const workflowPrepared = prepared.lifecycle === 'routed'
        && prepared.workflowStatus === 'for_review'
        && prepared.workflowDepartmentCode === 'BUDGET'
        && prerequisiteWorkflowDelta === 1;
      check(row, 'linked-workflow-legitimately-actionable', workflowPrepared, JSON.stringify(prepared));

      await budget.page.goto(`${BASE}/correspondence/${CORRESPONDENCE_ID}/workspace`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await appReady(budget.page);
      const button = budget.page.getByRole('button', { name: 'Start Action', exact: true });
      await button.waitFor({ state: 'visible', timeout: 5000 });
      const beforeAct = probe('correspondence', CORRESPONDENCE_ID);

      const evidence = await browserMutation(budget.page, {
        pathMatcher: `/correspondence/${CORRESPONDENCE_ID}/workspace/act`,
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      await budget.page.getByText('In Action', { exact: true }).first().waitFor({ state: 'visible', timeout: 2500 }).catch(() => {});
      const ready = await appReady(budget.page, 2500).catch(() => null);
      const after = probe('correspondence', CORRESPONDENCE_ID);
      row.database.after = after;
      const eventDelta = after.eventCount - beforeAct.eventCount;
      const workflowEventDelta = after.workflowEventCount - beforeAct.workflowEventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1, workflowEventDelta);
      const visible = await budget.page.getByText('In Action', { exact: true }).first().isVisible().catch(() => false);
      const actionGone = await budget.page.getByRole('button', { name: 'Start Action', exact: true }).count() === 0;
      const immediate = visible
        && actionGone
        && !!ready
        && after.lifecycle === 'in_action'
        && !!after.actionStartedAt
        && after.workflowStatus === 'for_review'
        && after.workflowDepartmentCode === 'BUDGET'
        && eventDelta === 1
        && workflowEventDelta === 0;

      row.visibleResult = visible ? 'In Action lifecycle is visible immediately after the legitimate linked-workflow prerequisite.' : 'In Action lifecycle is not visible immediately.';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'previous-action-removed', actionGone, 'Start Action remains available');
      check(row, 'action-started-at-populated', !!after.actionStartedAt, `actionStartedAt=${after.actionStartedAt}`);
      check(row, 'exactly-one-correspondence-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'linked-workflow-remains-consistent', after.workflowStatus === 'for_review' && after.workflowDepartmentCode === 'BUDGET' && workflowEventDelta === 0, `workflowEventDelta=${workflowEventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(budget.page, row, async () => {
        const reloadState = probe('correspondence', CORRESPONDENCE_ID);
        row.database.reload = reloadState;
        return await budget.page.getByText('In Action', { exact: true }).first().isVisible().catch(() => false)
          && await budget.page.getByRole('button', { name: 'Start Action', exact: true }).count() === 0
          && reloadState.lifecycle === 'in_action'
          && !!reloadState.actionStartedAt
          && reloadState.workflowStatus === 'for_review'
          && reloadState.workflowDepartmentCode === 'BUDGET'
          && reloadState.eventCount === after.eventCount
          && reloadState.workflowEventCount === after.workflowEventCount;
      });
    });

    await runScenario(mayor.page, {
      scenario: 'memorandum-publish',
      actor: 'Mayor Approver',
      mutation: 'Publish memorandum to all employees',
    }, async (row) => {
      const before = probe('memorandum', memorandumNumber, 'employee@talibon.demo');
      row.database.before = before;
      const response = await mayor.page.goto(`${BASE}/memoranda/create`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(mayor.page);
      await mayor.page.getByLabel('Memo number').fill(memorandumNumber);
      await mayor.page.getByLabel('Title').fill(`H1 mutation memorandum ${marker}`);
      await mayor.page.getByLabel('Memorandum content').fill('Synthetic H1 browser mutation acceptance memorandum.');
      const evidence = await browserMutation(mayor.page, {
        pathMatcher: '/memoranda',
        action: () => mayor.page.getByRole('button', { name: /Publish & Deliver/i }).click(),
      });
      setMutationEvidence(row, evidence);
      await mayor.page.getByText(memorandumNumber, { exact: true }).waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(mayor.page, 2500).catch(() => null);
      const after = probe('memorandum', memorandumNumber, 'employee@talibon.demo');
      row.database.after = after;
      shared.memorandumId = after.id;
      row.duplicateMutationCount = Math.max(0, after.count - before.count - 1);
      const visible = await mayor.page.getByText(new RegExp(memorandumNumber, 'i')).first().isVisible().catch(() => false);
      row.visibleResult = visible ? 'Published memorandum detail is visible immediately.' : 'Published memorandum did not converge visibly immediately.';
      const immediate = visible && !!ready && before.count === 0 && after.count === 1 && after.recipientCount > 0;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(mayor.page, row, async () => {
        const reloadState = probe('memorandum', memorandumNumber, 'employee@talibon.demo');
        row.database.reload = reloadState;
        return await mayor.page.getByText(new RegExp(memorandumNumber, 'i')).first().isVisible().catch(() => false)
          && reloadState.count === 1
          && reloadState.recipientCount === after.recipientCount;
      });
    });

    await runScenario(employee.page, {
      scenario: 'memorandum-acknowledge-double-click',
      actor: 'Employee',
      mutation: 'Acknowledge memorandum with rapid duplicate interaction',
    }, async (row) => {
      if (!shared.memorandumId) throw new Error('Dependency failed: published memorandum ID unavailable');
      const response = await employee.page.goto(`${BASE}/memoranda/${shared.memorandumId}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(employee.page);
      const before = probe('memorandum', memorandumNumber, 'employee@talibon.demo');
      row.database.before = before;
      const button = employee.page.getByRole('button', { name: 'I acknowledge receipt', exact: true });
      await button.waitFor({ state: 'visible', timeout: 5000 });
      const evidence = await browserMutation(employee.page, {
        pathMatcher: `/memoranda/${shared.memorandumId}/acknowledge`,
        action: () => button.click({ clickCount: 2, delay: 0 }),
      });
      setMutationEvidence(row, evidence);
      await employee.page.getByText(/Acknowledged/i).first().waitFor({ state: 'visible', timeout: 4000 }).catch(() => {});
      const ready = await appReady(employee.page, 2500).catch(() => null);
      const after = probe('memorandum', memorandumNumber, 'employee@talibon.demo');
      row.database.after = after;
      const auditDelta = after.ackAuditCount - before.ackAuditCount;
      row.duplicateMutationCount = Math.max(0, auditDelta - 1);
      const visible = await employee.page.getByText(/Acknowledged/i).first().isVisible().catch(() => false);
      const actionGone = await employee.page.getByRole('button', { name: 'I acknowledge receipt', exact: true }).count() === 0;
      row.visibleResult = visible ? 'Acknowledged state is visible immediately.' : 'Acknowledged state is not visible immediately.';
      const immediate = visible && actionGone && !!ready && after.targetAcknowledged && auditDelta === 1;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'acknowledgement-action-removed', actionGone, 'Acknowledgement action remains available');
      check(row, 'exactly-one-audit-append', auditDelta === 1, `auditDelta=${auditDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(employee.page, row, async () => {
        const reloadState = probe('memorandum', memorandumNumber, 'employee@talibon.demo');
        row.database.reload = reloadState;
        return await employee.page.getByText(/Acknowledged/i).first().isVisible().catch(() => false)
          && reloadState.targetAcknowledged
          && reloadState.ackAuditCount === after.ackAuditCount;
      });
    });

    await runScenario(mayor.page, {
      scenario: 'travel-order-record-approved',
      actor: 'Mayor Approver',
      mutation: 'Record approved Travel Order',
    }, async (row) => {
      const before = probe('travel', travelReference);
      row.database.before = before;
      const response = await mayor.page.goto(`${BASE}/travel-orders/create`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(mayor.page);
      await mayor.page.getByLabel('Official reference number').fill(travelReference);
      await mayor.page.getByLabel('Issuance date').fill('2026-09-13');
      await mayor.page.getByLabel('Purpose / subject').fill('Synthetic H1 exactly-once Travel Order acceptance');
      await mayor.page.getByLabel('Destination / location').fill('Synthetic QA destination');
      await selectByText(mayor.page.getByLabel('Responsible office'), /Engineering/i);
      await mayor.page.getByLabel('Inclusive travel start').fill('2026-09-15');
      await mayor.page.getByLabel('Inclusive travel end').fill('2026-09-16');
      await mayor.page.getByLabel('Issued-to employee numbers').fill('DEMO-0003');
      const evidence = await browserMutation(mayor.page, {
        pathMatcher: '/travel-orders',
        action: () => mayor.page.getByRole('button', { name: /Record approved order/i }).click(),
      });
      setMutationEvidence(row, evidence);
      await mayor.page.getByRole('heading', { name: travelReference }).waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(mayor.page, 2500).catch(() => null);
      const after = probe('travel', travelReference);
      row.database.after = after;
      shared.travelPublicId = after.publicId;
      row.duplicateMutationCount = Math.max(0, after.count - before.count - 1);
      const visible = await mayor.page.getByRole('heading', { name: travelReference }).isVisible().catch(() => false);
      const approved = await mayor.page.getByText('Approved', { exact: true }).first().isVisible().catch(() => false);
      row.visibleResult = visible && approved ? 'Approved Travel Order is visible immediately.' : 'Approved Travel Order did not converge visibly immediately.';
      const immediate = visible && approved && !!ready && before.count === 0 && after.count === 1 && after.status === 'approved' && after.eventCount === 1 && after.issuedToCount === 1;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'exactly-one-record-event', after.eventCount === 1, `eventCount=${after.eventCount}`);

      await reloadAndVerify(mayor.page, row, async () => {
        const reloadState = probe('travel', travelReference);
        row.database.reload = reloadState;
        return await mayor.page.getByRole('heading', { name: travelReference }).isVisible().catch(() => false)
          && reloadState.status === 'approved'
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(mayor.page, {
      scenario: 'travel-order-complete-double-click',
      actor: 'Mayor Approver',
      mutation: 'Complete Travel Order with rapid duplicate interaction',
    }, async (row) => {
      if (!shared.travelPublicId) throw new Error('Dependency failed: Travel Order public ID unavailable');
      const before = probe('travel', travelReference);
      row.database.before = before;
      if (!await mayor.page.getByRole('button', { name: 'Update status', exact: true }).isVisible().catch(() => false)) {
        await mayor.page.goto(`${BASE}/travel-orders/${shared.travelPublicId}`, { waitUntil: 'domcontentloaded' });
        await appReady(mayor.page);
      }
      await mayor.page.getByLabel('New status').selectOption('completed');
      await mayor.page.getByLabel('Remarks').fill('Synthetic H1 completion acceptance.');
      const button = mayor.page.getByRole('button', { name: 'Update status', exact: true });
      const evidence = await browserMutation(mayor.page, {
        pathMatcher: `/travel-orders/${shared.travelPublicId}/status`,
        action: () => button.click({ clickCount: 2, delay: 0 }),
      });
      setMutationEvidence(row, evidence);
      await mayor.page.getByText('Completed', { exact: true }).first().waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(mayor.page, 2500).catch(() => null);
      const after = probe('travel', travelReference);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const visible = await mayor.page.getByText('Completed', { exact: true }).first().isVisible().catch(() => false);
      const actionGone = await mayor.page.getByRole('button', { name: 'Update status', exact: true }).count() === 0;
      row.visibleResult = visible ? 'Completed Travel Order state is visible immediately.' : 'Completed Travel Order state is not visible immediately.';
      const immediate = visible && actionGone && !!ready && after.status === 'completed' && eventDelta === 1;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'terminal-action-removed', actionGone, 'Update status remains available after terminal completion');
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(mayor.page, row, async () => {
        const reloadState = probe('travel', travelReference);
        row.database.reload = reloadState;
        return await mayor.page.getByText('Completed', { exact: true }).first().isVisible().catch(() => false)
          && await mayor.page.getByRole('button', { name: 'Update status', exact: true }).count() === 0
          && reloadState.status === 'completed'
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(mayor.page, {
      scenario: 'travel-order-terminal-action-unavailable',
      actor: 'Mayor Approver',
      mutation: 'Terminal-state repeat prevention',
    }, async (row) => {
      const before = probe('travel', travelReference);
      row.database.before = before;
      const actionGone = await mayor.page.getByRole('button', { name: 'Update status', exact: true }).count() === 0;
      const after = probe('travel', travelReference);
      row.database.after = after;
      row.requestCount = 0;
      row.responseStatus = null;
      row.responseStatuses = [];
      row.duplicateMutationCount = 0;
      row.visibleResult = actionGone ? 'Terminal Travel Order exposes no repeat status mutation action.' : 'Terminal Travel Order still exposes a repeat status mutation action.';
      const immediate = actionGone && before.status === 'completed' && after.eventCount === before.eventCount;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'terminal-action-unavailable', immediate, row.visibleResult);

      await reloadAndVerify(mayor.page, row, async () => {
        const reloadState = probe('travel', travelReference);
        row.database.reload = reloadState;
        return await mayor.page.getByRole('button', { name: 'Update status', exact: true }).count() === 0
          && reloadState.status === 'completed'
          && reloadState.eventCount === before.eventCount;
      });
    });

    await runScenario(mayor.page, {
      scenario: 'travel-order-cancel',
      actor: 'Mayor Approver',
      mutation: 'Cancel independent approved Travel Order',
    }, async (row) => {
      const before = probe('travel', cancelTravelReference);
      row.database.before = before;
      if (!before.publicId) throw new Error('H3 cancellation Travel Order fixture missing');
      const response = await mayor.page.goto(`${BASE}/travel-orders/${before.publicId}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      check(row, 'starting-page-200', response?.status() === 200, `status=${response?.status()}`);
      await appReady(mayor.page);
      await mayor.page.getByLabel('New status').selectOption('cancelled');
      await mayor.page.getByLabel('Remarks').fill('Synthetic H3 cancellation acceptance.');
      const button = mayor.page.getByRole('button', { name: 'Update status', exact: true });
      await button.waitFor({ state: 'visible', timeout: 5000 });
      const evidence = await browserMutation(mayor.page, {
        pathMatcher: `/travel-orders/${before.publicId}/status`,
        action: () => button.click(),
      });
      setMutationEvidence(row, evidence);
      await mayor.page.getByText('Cancelled', { exact: true }).first().waitFor({ state: 'visible', timeout: 2500 }).catch(() => {});
      const ready = await appReady(mayor.page, 2500).catch(() => null);
      const after = probe('travel', cancelTravelReference);
      row.database.after = after;
      const eventDelta = after.eventCount - before.eventCount;
      row.duplicateMutationCount = Math.max(0, eventDelta - 1);
      const visible = await mayor.page.getByText('Cancelled', { exact: true }).first().isVisible().catch(() => false);
      const actionGone = await mayor.page.getByRole('button', { name: 'Update status', exact: true }).count() === 0;
      const eventTruth = after.latestEvent?.event === 'status_changed'
        && after.latestEvent?.fromStatus === 'approved'
        && after.latestEvent?.toStatus === 'cancelled';
      const immediate = visible
        && actionGone
        && !!ready
        && after.status === 'cancelled'
        && eventDelta === 1
        && eventTruth;
      row.visibleResult = visible ? 'Cancelled Travel Order state is visible immediately.' : 'Cancelled Travel Order state is not visible immediately.';
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'immediate-authoritative-state', immediate, JSON.stringify(after));
      check(row, 'truthful-cancel-event', eventTruth, JSON.stringify(after.latestEvent));
      check(row, 'terminal-action-removed', actionGone, 'Update status remains available after terminal cancellation');
      check(row, 'exactly-one-event-appended', eventDelta === 1, `eventDelta=${eventDelta}`);
      check(row, 'no-duplicate-effective-mutation', row.duplicateMutationCount === 0, `duplicateMutationCount=${row.duplicateMutationCount}`);

      await reloadAndVerify(mayor.page, row, async () => {
        const reloadState = probe('travel', cancelTravelReference);
        row.database.reload = reloadState;
        return await mayor.page.getByText('Cancelled', { exact: true }).first().isVisible().catch(() => false)
          && await mayor.page.getByRole('button', { name: 'Update status', exact: true }).count() === 0
          && reloadState.status === 'cancelled'
          && reloadState.eventCount === after.eventCount;
      });
    });

    await runScenario(engineering.page, {
      scenario: 'transaction-validation-visible',
      actor: 'Engineering Department Head',
      mutation: 'Submit invalid transaction and present validation',
    }, async (row) => {
      const before = probe('totals');
      row.database.before = before;
      await engineering.page.goto(`${BASE}/transactions/create`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await appReady(engineering.page);
      const pendingMemoLater = engineering.page.getByRole('button', { name: 'Later', exact: true });
      if (await pendingMemoLater.isVisible().catch(() => false)) {
        await pendingMemoLater.click();
      }
      await engineering.page.getByLabel('Description').fill(invalidTransactionTitle);
      const evidence = await browserMutation(engineering.page, {
        pathMatcher: '/transactions',
        action: () => engineering.page.getByRole('button', { name: /Route transaction/i }).click(),
      });
      setMutationEvidence(row, evidence);
      await engineering.page.getByText(/title field is required/i).first().waitFor({ state: 'visible', timeout: 2000 }).catch(() => {});
      const ready = await appReady(engineering.page, 2500).catch(() => null);
      const after = probe('totals');
      row.database.after = after;
      row.duplicateMutationCount = 0;
      const visible = await engineering.page.getByText(/title field is required/i).first().isVisible().catch(() => false);
      row.visibleResult = visible ? 'Validation error remains visible without navigation or reload.' : 'Expected title validation error is not visible.';
      const immediate = visible && !!ready && pathOnly(engineering.page.url()) === '/transactions/create' && after.transactions === before.transactions;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      row.reloadConsistency = 'NOT_APPLICABLE';
      check(row, 'visible-validation-error', immediate, `transactions before=${before.transactions} after=${after.transactions}`);
      check(row, 'invalid-submit-did-not-mutate', after.transactions === before.transactions, 'transaction count changed on denied validation');
    });

    await runScenario(employee.page, {
      scenario: 'travel-order-unauthorized-create-denial',
      actor: 'Employee',
      mutation: 'Unauthorized Travel Order creation denial',
    }, async (row) => {
      const before = probe('totals');
      row.database.before = before;
      const response = await employee.page.goto(`${BASE}/travel-orders/create`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      row.responseStatus = response?.status() ?? null;
      row.responseStatuses = row.responseStatus === null ? [] : [row.responseStatus];
      row.requestCount = 0;
      const ready = await appReady(employee.page, 2500).catch(() => null);
      const after = probe('totals');
      row.database.after = after;
      row.duplicateMutationCount = 0;
      const denied = await employee.page.getByRole('heading', { name: /cannot open this page/i }).isVisible().catch(() => false);
      row.visibleResult = denied ? 'Controlled access-denied presentation is visible.' : 'Controlled access-denied presentation is missing.';
      const immediate = denied && !!ready && row.responseStatus === 403 && after.travelOrders === before.travelOrders;
      row.immediateConvergence = immediate ? 'PASS' : 'FAIL';
      check(row, 'controlled-403-denial', immediate, `status=${row.responseStatus}`);
      check(row, 'denial-did-not-mutate', after.travelOrders === before.travelOrders, 'Travel Order count changed on denied access');

      await reloadAndVerify(employee.page, row, async () => {
        const reloadState = probe('totals');
        row.database.reload = reloadState;
        return await employee.page.getByRole('heading', { name: /cannot open this page/i }).isVisible().catch(() => false)
          && reloadState.travelOrders === before.travelOrders;
      });
    });
  } finally {
    for (const session of sessions) await session.context.close().catch(() => {});
    await browser.close().catch(() => {});
  }

  report.completed = report.summary.failH1 === 0 && report.summary.scenarios === report.summary.passed + report.summary.deferredH2 && report.git.exactHead;
  if (!report.completed) {
    report.failure = {
      stage: 'mutation-acceptance',
      summary: `${report.summary.failH1} H1 failures; ${report.summary.deferredH2} scenarios deferred to H2`,
    };
  }
  await writeReport();

  if (report.completed) {
    console.log(`H1_MUTATION_QA_PASS sha=${report.git.actualSha} scenarios=${report.summary.scenarios}`);
  } else {
    console.error(`H1_MUTATION_QA_FAIL sha=${report.git.actualSha} failures=${report.summary.failed}`);
    process.exitCode = 1;
  }
}

main().catch(async (error) => {
  report.failure = { stage: 'harness', summary: clean(error.message) };
  await writeReport().catch(() => {});
  console.error(`H1_MUTATION_QA_FATAL ${clean(error.message)}`);
  process.exitCode = 1;
});
