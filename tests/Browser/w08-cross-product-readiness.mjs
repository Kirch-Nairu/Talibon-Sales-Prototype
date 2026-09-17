import { chromium } from 'playwright';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs/promises';

const BASE = process.env.QA_BASE_URL || 'http://127.0.0.1:8000';
const PASSWORD = process.env.QA_DEMO_PASSWORD;
const EXPECTED_SHA = process.env.QA_EXPECTED_SHA;
const PRODUCT_ANCHOR = process.env.QA_PRODUCT_ANCHOR || 'db286142cdc5d9e793680fac933a8462deb8390d';
const ENVIRONMENT = process.env.QA_ENVIRONMENT || 'local-w08-cross-product';
const REPORT_PATH = 'storage/app/qa/w08-cross-product-readiness-report.json';
const SCREENSHOT_DIR = 'storage/app/qa/w08-screenshots';
const CORRESPONDENCE_ID = '11000000-0000-4000-8000-000000000001';

if (!PASSWORD || !EXPECTED_SHA) throw new Error('QA_DEMO_PASSWORD and QA_EXPECTED_SHA are required');

const marker = EXPECTED_SHA.slice(0, 10);
const assignTitle = `H3 assign transaction ${marker}`;
const cancelTravelReference = `H3-TO-CANCEL-${marker.toUpperCase()}`;
const secrets = new Set([PASSWORD]);
const runtimeByPage = new WeakMap();

const report = {
  schemaVersion: 1,
  generatedAt: new Date().toISOString(),
  environment: ENVIRONMENT,
  git: {
    expectedSha: EXPECTED_SHA,
    actualSha: '',
    exactHead: false,
    productAnchor: PRODUCT_ANCHOR,
    productAnchorAncestor: false,
  },
  summary: {
    scenarios: 0,
    passed: 0,
    failed: 0,
    checks: 0,
    screenshots: 0,
    pageerrorCount: 0,
    server5xxCount: 0,
    fatalConsoleCount: 0,
  },
  coverage: {
    personas: [
      'Municipal Executive',
      'Department Head — Engineering',
      'Department Head — Budget',
      'Employee',
      'Human Resources',
      'Legislative Office',
      'System Administration',
    ],
    viewports: ['1440x900', '1280x800', '768x1024', '430x932', '390x844', '360x800', '1600x900'],
    themes: ['light', 'dark'],
    waves: ['W01', 'W02', 'W03', 'W04', 'W05', 'W06', 'W07'],
  },
  scenarios: [],
  screenshots: [],
  diagnostics: [],
  defects: [],
  limitations: [
    'No screen-reader or dedicated assistive-technology execution is claimed by this harness.',
    'Browser zoom is represented by narrow/reflow viewport checks; native browser UI zoom is not programmatically driven by Playwright.',
    'UAT, deployment, and production runtime acceptance are outside W08 QA authority.',
  ],
  completed: false,
  failure: null,
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
  await fs.writeFile(REPORT_PATH, JSON.stringify(report, null, 2));
}

function probe(command, ...args) {
  const output = execFileSync('php', ['tests/Browser/h1-mutation-probe.php', command, ...args.map(String)], {
    encoding: 'utf8',
    env: process.env,
  }).trim();
  return JSON.parse(output);
}

function monitor(page, actor) {
  const events = [];
  const push = (type, detail, extra = {}) => {
    const row = {
      at: new Date().toISOString(),
      actor,
      path: pathOnly(page.url()),
      type,
      detail: clean(detail),
      ...extra,
    };
    events.push(row);
    report.diagnostics.push(row);
    if (report.diagnostics.length > 500) report.diagnostics.shift();
  };

  page.on('pageerror', (error) => push('pageerror', error.message));
  page.on('response', (response) => {
    if (response.status() >= 500) push('server-5xx', `${response.status()} ${pathOnly(response.url())}`, { status: response.status() });
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

async function appReady(page, timeout = 8000) {
  await page.waitForFunction(() => {
    const root = document.getElementById('app');
    const text = root?.innerText?.replace(/\s+/g, ' ').trim() || '';
    return document.readyState !== 'loading'
      && !!root
      && root.childElementCount > 0
      && text.length >= 10
      && !!document.querySelector('main, h1, form, nav[aria-label]');
  }, null, { timeout });
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
  for (let index = 0; index + 8 <= bits.length; index += 8) bytes.push(parseInt(bits.slice(index, index + 8), 2));
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
  const context = await browser.newContext({ viewport: spec.viewport || { width: 1280, height: 800 } });
  const page = await context.newPage();
  monitor(page, spec.label);
  const response = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
  if (response?.status() !== 200) throw new Error(`${spec.label} login page returned ${response?.status()}`);
  await appReady(page);
  await page.getByLabel('Email').fill(spec.email);
  await page.getByLabel('Password').fill(PASSWORD);
  await Promise.all([
    page.waitForURL((url) => ['/dashboard', '/security/mfa/enroll', '/security/mfa/challenge'].includes(url.pathname), { timeout: 15000 }),
    page.getByRole('button', { name: 'Sign In' }).click(),
  ]);

  let current = new URL(page.url()).pathname;
  if (current === '/security/mfa/challenge') throw new Error(`${spec.label} unexpectedly required a pre-existing MFA challenge in a fresh QA database`);
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
  if (current !== '/dashboard') throw new Error(`${spec.label} did not reach dashboard`);
  await appReady(page);
  return { context, page };
}

function newScenario(name, meta = {}) {
  return {
    name,
    ...meta,
    result: 'FAIL',
    checks: [],
    diagnostics: [],
    failure: null,
  };
}

function check(row, name, ok, detail = '') {
  const item = { name, pass: Boolean(ok), detail: clean(detail) };
  row.checks.push(item);
  report.summary.checks++;
  if (!ok) throw new Error(`${name}${detail ? `: ${clean(detail)}` : ''}`);
}

async function scenario(name, meta, body) {
  const row = newScenario(name, meta);
  report.scenarios.push(row);
  report.summary.scenarios++;
  const page = meta.page;
  const events = page ? runtimeByPage.get(page) || [] : [];
  const start = events.length;
  try {
    await body(row);
    const delta = events.slice(start);
    row.diagnostics = delta;
    const pageErrors = delta.filter((item) => item.type === 'pageerror').length;
    const server5xx = delta.filter((item) => item.type === 'server-5xx').length;
    const fatalConsole = delta.filter((item) => item.type === 'console-runtime-error').length;
    check(row, 'no page errors', pageErrors === 0, `pageErrors=${pageErrors}`);
    check(row, 'no server 5xx', server5xx === 0, `server5xx=${server5xx}`);
    check(row, 'no fatal console runtime errors', fatalConsole === 0, `fatalConsole=${fatalConsole}`);
    report.summary.pageerrorCount += pageErrors;
    report.summary.server5xxCount += server5xx;
    report.summary.fatalConsoleCount += fatalConsole;
    row.result = 'PASS';
    report.summary.passed++;
  } catch (error) {
    row.result = 'FAIL';
    row.failure = clean(error?.stack || error);
    report.summary.failed++;
    report.defects.push({ scenario: name, classification: 'unresolved-observation', detail: clean(error?.message || error) });
  }
  await writeReport();
  return row;
}

async function shot(page, { name, persona, route, viewport, theme, purpose }) {
  await fs.mkdir(SCREENSHOT_DIR, { recursive: true });
  const path = `${SCREENSHOT_DIR}/${name}`;
  await page.screenshot({ path, animations: 'disabled', fullPage: false });
  report.screenshots.push({ path, persona, route, viewport, theme, purpose });
  report.summary.screenshots = report.screenshots.length;
  return path;
}

async function navigate(page, route, expectedStatus = 200) {
  const response = await page.goto(`${BASE}${route}`, { waitUntil: 'domcontentloaded', timeout: 20000 });
  await appReady(page);
  return { response, status: response?.status() ?? null };
}

async function setAppearance(page, label, value) {
  let group = page.locator('[role="group"][aria-label="Appearance"]:visible').first();
  if (!(await group.isVisible().catch(() => false))) {
    const trigger = page.locator('summary[title="Appearance"]:visible').first();
    await trigger.click();
    group = page.locator('[role="group"][aria-label="Appearance"]:visible').first();
  }
  await group.waitFor({ state: 'visible', timeout: 5000 });
  await group.getByRole('button', { name: label, exact: true }).click();
  await page.waitForFunction((expected) => document.documentElement.dataset.appearance === expected, value, { timeout: 5000 });
}

async function noRootOverflow(page) {
  return page.evaluate(() => ({
    viewport: window.innerWidth,
    html: document.documentElement.scrollWidth,
    body: document.body.scrollWidth,
    pass: document.documentElement.scrollWidth <= window.innerWidth + 1 && document.body.scrollWidth <= window.innerWidth + 1,
  }));
}

async function semantics(page) {
  return page.evaluate(() => {
    const visible = (node) => {
      const style = getComputedStyle(node);
      const rect = node.getBoundingClientRect();
      return style.visibility !== 'hidden' && style.display !== 'none' && rect.width > 0 && rect.height > 0;
    };
    const controls = [...document.querySelectorAll('input, select, textarea')].filter(visible);
    const unlabeled = controls.filter((node) => !(node.labels?.length || node.getAttribute('aria-label') || node.getAttribute('aria-labelledby')))
      .map((node) => `${node.tagName}:${node.getAttribute('name') || node.getAttribute('placeholder') || ''}`);
    const ids = [...document.querySelectorAll('[id]')].map((node) => node.id).filter(Boolean);
    const duplicates = [...new Set(ids.filter((id, index) => ids.indexOf(id) !== index))];
    const brokenAria = [...document.querySelectorAll('[aria-labelledby]')].flatMap((node) =>
      (node.getAttribute('aria-labelledby') || '').split(/\s+/).filter(Boolean).filter((id) => !document.getElementById(id)));
    return {
      h1: document.querySelectorAll('h1').length,
      main: document.querySelectorAll('main').length,
      unlabeled,
      duplicates,
      brokenAria,
    };
  });
}

async function focusProbe(page) {
  await page.locator('body').focus().catch(() => {});
  await page.keyboard.press('Tab');
  return page.evaluate(() => {
    const node = document.activeElement;
    if (!(node instanceof HTMLElement)) return null;
    const style = getComputedStyle(node);
    return {
      tag: node.tagName,
      label: node.getAttribute('aria-label') || node.textContent?.trim().slice(0, 80) || '',
      focusVisible: node.matches(':focus-visible'),
      outline: `${style.outlineStyle} ${style.outlineWidth}`,
      boxShadow: style.boxShadow,
    };
  });
}

async function contrastOfHeading(page) {
  return page.evaluate(() => {
    const parse = (value) => {
      const match = value.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
      return match ? [+match[1], +match[2], +match[3]] : null;
    };
    const luminance = (rgb) => {
      const f = (value) => {
        const n = value / 255;
        return n <= 0.04045 ? n / 12.92 : ((n + 0.055) / 1.055) ** 2.4;
      };
      return 0.2126 * f(rgb[0]) + 0.7152 * f(rgb[1]) + 0.0722 * f(rgb[2]);
    };
    const heading = document.querySelector('h1');
    if (!heading) return null;
    const fg = parse(getComputedStyle(heading).color);
    let cursor = heading;
    let bg = null;
    while (cursor) {
      const raw = getComputedStyle(cursor).backgroundColor;
      const candidate = parse(raw);
      if (candidate && raw !== 'rgba(0, 0, 0, 0)') { bg = candidate; break; }
      cursor = cursor.parentElement;
    }
    if (!fg || !bg) return null;
    const a = luminance(fg);
    const b = luminance(bg);
    return { fg, bg, ratio: (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05) };
  });
}

async function verifyPageBasics(row, page, label) {
  const sem = await semantics(page);
  check(row, `${label}: one h1`, sem.h1 === 1, JSON.stringify(sem));
  check(row, `${label}: main landmark exists`, sem.main >= 1, JSON.stringify(sem));
  check(row, `${label}: visible form controls are named`, sem.unlabeled.length === 0, JSON.stringify(sem.unlabeled));
  check(row, `${label}: DOM ids are unique`, sem.duplicates.length === 0, JSON.stringify(sem.duplicates));
  check(row, `${label}: aria-labelledby ownership valid`, sem.brokenAria.length === 0, JSON.stringify(sem.brokenAria));
  const overflow = await noRootOverflow(page);
  check(row, `${label}: no root horizontal overflow`, overflow.pass, JSON.stringify(overflow));
}

async function requestMutation(page, pathMatcher, action) {
  const responses = [];
  const listener = (response) => {
    const path = new URL(response.url()).pathname;
    if (response.request().method() === 'POST' && pathMatcher.test(path)) responses.push(response.status());
  };
  page.on('response', listener);
  try {
    await action();
    await page.waitForTimeout(900);
  } finally {
    page.off('response', listener);
  }
  return responses;
}

async function showcaseShell(browser) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await context.newPage();
  monitor(page, 'Showcase shell');
  await scenario('W01 showcase shell continuity', { wave: 'W01', persona: 'Showcase municipal executive', page }, async (row) => {
    const { status } = await navigate(page, '/login');
    check(row, 'showcase login loads', status === 200, `status=${status}`);
    await page.getByRole('button', { name: 'Enter Workspace' }).click();
    const dialog = page.getByRole('dialog', { name: 'Choose your workspace' });
    await dialog.waitFor({ state: 'visible', timeout: 5000 });
    check(row, 'workspace selector traps into dialog', await dialog.isVisible(), 'dialog not visible');
    const focused = await page.evaluate(() => document.activeElement?.getAttribute('aria-label') || '');
    check(row, 'workspace selector initial focus is actionable', focused === 'Close workspace selector', `focused=${focused}`);
    await page.getByRole('button', { name: 'Municipal Executive' }).click();
    await page.waitForURL((url) => url.pathname === '/dashboard', { timeout: 15000 });
    await appReady(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByRole('button', { name: 'Open navigation' }).click();
    const mobileNav = page.getByRole('navigation', { name: 'Primary navigation' }).last();
    await mobileNav.waitFor({ state: 'visible', timeout: 5000 });
    check(row, 'mobile shell exposes Sign out', await page.getByRole('button', { name: 'Sign out' }).last().isVisible(), 'Sign out not visible');
    check(row, 'mobile shell exposes Switch Workspace', await page.getByRole('button', { name: 'Switch Workspace' }).last().isVisible(), 'Switch Workspace not visible');
    check(row, 'mobile shell exposes Appearance', await page.getByText('Appearance', { exact: true }).last().isVisible(), 'Appearance not visible');
    const footer = page.getByText('Municipality of Talibon · Province of Bohol', { exact: true });
    await footer.scrollIntoViewIfNeeded();
    check(row, 'application footer remains reachable', await footer.isVisible(), 'footer not reachable');
    const overflow = await noRootOverflow(page);
    check(row, 'mobile shell constrained without root overflow', overflow.pass, JSON.stringify(overflow));
    await shot(page, { name: 'w01-showcase-shell-390-light.png', persona: 'Showcase municipal executive', route: '/dashboard', viewport: '390x844', theme: 'light', purpose: 'W01 mobile shell controls and footer reachability' });
    await page.getByRole('button', { name: 'Switch Workspace' }).last().click();
    await page.waitForURL((url) => url.pathname === '/login', { timeout: 15000 });
    await appReady(page);
    check(row, 'Switch Workspace returns to workspace gateway', new URL(page.url()).pathname === '/login', pathOnly(page.url()));
  });
  await context.close();
}

async function dashboardHierarchy(page, persona, screenshotName) {
  await scenario(`W02 dashboard hierarchy — ${persona}`, { wave: 'W02', persona, page }, async (row) => {
    const { status } = await navigate(page, '/dashboard');
    check(row, 'dashboard loads', status === 200, `status=${status}`);
    const labels = ['Act now', 'Next / soon', 'Current operating picture', 'Reference / history'];
    for (const label of labels) check(row, `dashboard contains ${label}`, await page.getByText(label, { exact: true }).first().isVisible(), label);
    const order = await page.evaluate((needles) => needles.map((needle) => document.body.innerText.indexOf(needle)), labels);
    check(row, 'dashboard priority sections preserve accepted order', order.every((value) => value >= 0) && order.every((value, index) => index === 0 || value > order[index - 1]), JSON.stringify(order));
    await verifyPageBasics(row, page, `${persona} dashboard`);
    await shot(page, { name: screenshotName, persona, route: '/dashboard', viewport: `${await page.evaluate(() => innerWidth)}x${await page.evaluate(() => innerHeight)}`, theme: await page.evaluate(() => document.documentElement.dataset.appearance || 'system'), purpose: 'W02 dashboard hierarchy' });
  });
}

async function planningChecks(engineering) {
  const page = engineering.page;
  await scenario('W04 planning responsive locality', { wave: 'W04', persona: 'Department Head — Engineering', page }, async (row) => {
    await page.setViewportSize({ width: 360, height: 800 });
    await setAppearance(page, 'Light', 'light');
    let result = await navigate(page, '/development-plans');
    check(row, 'Plans loads at 360', result.status === 200, `status=${result.status}`);
    await verifyPageBasics(row, page, 'Plans 360');
    const plansButton = page.getByRole('button', { name: 'View details' }).first();
    await plansButton.click();
    check(row, 'Plan details expand locally', await page.getByText('Required action', { exact: true }).first().isVisible(), 'Plan details not local');
    await shot(page, { name: 'w04-plans-360-light.png', persona: 'Department Head — Engineering', route: '/development-plans', viewport: '360x800', theme: 'light', purpose: 'W04 narrow plan action/detail locality' });

    await page.setViewportSize({ width: 430, height: 932 });
    result = await navigate(page, '/ppas');
    check(row, 'PPAs loads at 430', result.status === 200, `status=${result.status}`);
    await verifyPageBasics(row, page, 'PPAs 430');
    const ppaButton = page.getByRole('button', { name: 'View details' }).first();
    await ppaButton.click();
    check(row, 'PPA details expand adjacent to selected record', await page.getByText('Implementation profile', { exact: true }).first().isVisible().catch(() => false) || await page.getByText('Responsible office', { exact: true }).first().isVisible().catch(() => false), 'PPA detail panel not visible');
    await shot(page, { name: 'w04-ppas-430-light.png', persona: 'Department Head — Engineering', route: '/ppas', viewport: '430x932', theme: 'light', purpose: 'W04 PPA responsive action/detail locality' });

    await page.setViewportSize({ width: 768, height: 1024 });
    result = await navigate(page, '/operations');
    check(row, 'Project monitoring loads at 768', result.status === 200, `status=${result.status}`);
    await verifyPageBasics(row, page, 'Operations 768');
    await shot(page, { name: 'w04-operations-768-light.png', persona: 'Department Head — Engineering', route: '/operations', viewport: '768x1024', theme: 'light', purpose: 'W04 project monitoring tablet reflow' });
  });
}

async function utilityChecks(engineering) {
  const page = engineering.page;
  await scenario('W05 mandatory utility breakpoint regression', { wave: 'W05', persona: 'Department Head — Engineering', page }, async (row) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await navigate(page, '/dashboard');
    await setAppearance(page, 'Dark', 'dark');
    const trigger = page.getByRole('button', { name: 'Open municipal utilities' });
    check(row, 'compact utility trigger visible below 1536', await trigger.isVisible(), 'trigger hidden');
    await trigger.focus();
    await trigger.click();
    const dialog = page.getByRole('dialog', { name: 'Municipal utilities' });
    await dialog.waitFor({ state: 'visible', timeout: 5000 });
    const before = await page.evaluate(() => ({ overflow: document.body.style.overflow, ids: [...document.querySelectorAll('[id]')].map((node) => node.id) }));
    check(row, 'utility drawer locks body scroll while open', before.overflow === 'hidden', JSON.stringify(before));
    const duplicateBefore = before.ids.filter((id, index) => id && before.ids.indexOf(id) !== index);
    check(row, 'utility drawer and hidden rail have unique IDs', duplicateBefore.length === 0, JSON.stringify(duplicateBefore));
    await shot(page, { name: 'w05-utilities-drawer-1280-dark.png', persona: 'Department Head — Engineering', route: '/dashboard', viewport: '1280x800', theme: 'dark', purpose: 'W05 compact utility drawer before 2xl transition' });

    await page.setViewportSize({ width: 1600, height: 900 });
    await page.waitForTimeout(350);
    check(row, 'drawer closes crossing above 1536', await dialog.count() === 0 || !(await dialog.isVisible().catch(() => false)), 'dialog remains visible');
    const wide = await page.evaluate(() => ({ overflow: document.body.style.overflow, activeLabel: document.activeElement?.getAttribute('aria-label') || '', activeVisible: document.activeElement instanceof HTMLElement ? !!(document.activeElement.offsetWidth || document.activeElement.offsetHeight || document.activeElement.getClientRects().length) : false }));
    check(row, 'body scroll restored after 2xl transition', wide.overflow !== 'hidden', JSON.stringify(wide));
    check(row, 'focus not returned to hidden utility trigger', !(wide.activeLabel === 'Open municipal utilities' && !wide.activeVisible), JSON.stringify(wide));
    const rail = page.getByRole('complementary', { name: 'Municipal utilities' });
    check(row, 'persistent utility rail visible above 1536', await rail.isVisible(), 'rail hidden');
    const wideSemantics = await semantics(page);
    check(row, 'persistent utility rail keeps unique IDs', wideSemantics.duplicates.length === 0, JSON.stringify(wideSemantics.duplicates));
    await shot(page, { name: 'w05-utilities-rail-1600-dark.png', persona: 'Department Head — Engineering', route: '/dashboard', viewport: '1600x900', theme: 'dark', purpose: 'W05 persistent utility rail after 2xl transition' });

    await page.setViewportSize({ width: 1280, height: 800 });
    await page.waitForTimeout(250);
    check(row, 'compact utility trigger usable after returning below 1536', await trigger.isVisible(), 'trigger not visible after return');
    await trigger.click();
    await dialog.waitFor({ state: 'visible', timeout: 5000 });
    check(row, 'no invisible modal state after breakpoint roundtrip', await dialog.isVisible(), 'drawer failed to reopen');
    await page.getByRole('button', { name: 'Close municipal utilities' }).click();
    check(row, 'body scroll restored after explicit close', await page.evaluate(() => document.body.style.overflow !== 'hidden'), 'body remains locked');

    const calendar = await navigate(page, '/calendar');
    check(row, 'Calendar opens from utility surface domain', calendar.status === 200, `status=${calendar.status}`);
    check(row, 'Calendar exposes planning reference schedule', await page.getByRole('region', { name: 'Municipal planning reference schedule' }).isVisible(), 'planning schedule not visible');
    const calendarText = await page.locator('body').innerText();
    check(row, 'Calendar exposes schedule time/date semantics', /All day|\d{1,2}:\d{2}|AM|PM/i.test(calendarText), 'schedule time semantics absent');
    await verifyPageBasics(row, page, 'Calendar 1280 dark');
  });
}

async function messagingChecks(engineering) {
  const page = engineering.page;
  await scenario('W06 read-only quick messaging', { wave: 'W06', persona: 'Department Head — Engineering', page }, async (row) => {
    await page.setViewportSize({ width: 430, height: 932 });
    const result = await navigate(page, '/messages');
    check(row, 'Messages loads', result.status === 200, `status=${result.status}`);
    check(row, 'Messages declares read-only history', await page.getByText(/Read-only inter-office coordination history/i).isVisible(), 'read-only description missing');
    check(row, 'Messages exposes no compose control', await page.getByRole('button', { name: /compose|new message|send message/i }).count() === 0, 'fake compose/send control present');
    await verifyPageBasics(row, page, 'Messages 430');
    await shot(page, { name: 'w06-messages-430-dark.png', persona: 'Department Head — Engineering', route: '/messages', viewport: '430x932', theme: 'dark', purpose: 'W06 read-only full Messages experience' });
  });
}

async function completionChecks(sessions) {
  const targets = [
    ['Human Resources', sessions.hr.page, '/hris', 'w07-hris-390-dark.png'],
    ['Legislative Office', sessions.legislative.page, '/legislation', 'w07-legislation-390-light.png'],
    ['System Administration', sessions.admin.page, '/admin', 'w07-admin-1440-dark.png'],
    ['Employee', sessions.employee.page, '/employees', 'w07-employees-430-light.png'],
  ];
  for (const [persona, page, route, file] of targets) {
    await scenario(`W07 role completion — ${persona}`, { wave: 'W07', persona, page }, async (row) => {
      const viewport = persona === 'System Administration' ? { width: 1440, height: 900 } : persona === 'Employee' ? { width: 430, height: 932 } : { width: 390, height: 844 };
      await page.setViewportSize(viewport);
      const theme = ['Human Resources', 'System Administration'].includes(persona) ? ['Dark', 'dark'] : ['Light', 'light'];
      await navigate(page, '/dashboard');
      await setAppearance(page, theme[0], theme[1]);
      const result = await navigate(page, route);
      check(row, `${route} loads`, result.status === 200, `status=${result.status}`);
      await verifyPageBasics(row, page, `${persona} ${route}`);
      const contrast = await contrastOfHeading(page);
      check(row, `${persona} heading contrast >= 4.5`, !!contrast && contrast.ratio >= 4.5, JSON.stringify(contrast));
      await shot(page, { name: file, persona, route, viewport: `${viewport.width}x${viewport.height}`, theme: theme[1], purpose: 'W07 representative role/completion surface' });
    });
  }

  const employeePage = sessions.employee.page;
  await scenario('W07 normalized forbidden error', { wave: 'W07', persona: 'Employee', page: employeePage }, async (row) => {
    await employeePage.setViewportSize({ width: 390, height: 844 });
    const response = await employeePage.goto(`${BASE}/hris/admin`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    await appReady(employeePage);
    check(row, 'forbidden HR admin route returns 403', response?.status() === 403, `status=${response?.status()}`);
    check(row, 'normalized error presents Access restricted', await employeePage.getByText('Access restricted', { exact: true }).isVisible(), 'Access restricted label missing');
    await verifyPageBasics(row, employeePage, '403 normalized error');
    await shot(employeePage, { name: 'w07-error-403-390-light.png', persona: 'Employee', route: '/hris/admin', viewport: '390x844', theme: 'light', purpose: 'W07 normalized 403 error surface' });
  });
}

async function contextContinuityChecks(sessions) {
  const budget = sessions.budget.page;
  await scenario('W03 transaction mutation return continuity', { wave: 'W03', persona: 'Department Head — Budget', page: budget }, async (row) => {
    await budget.setViewportSize({ width: 1280, height: 800 });
    const search = encodeURIComponent(assignTitle);
    const listTarget = `/transactions?view=all&search=${search}`;
    const result = await navigate(budget, listTarget);
    check(row, 'filtered transaction queue loads', result.status === 200, `status=${result.status}`);
    const open = budget.getByRole('link', { name: /Open work item/i }).first();
    await open.waitFor({ state: 'visible', timeout: 5000 });
    await open.click();
    await budget.waitForURL((url) => /^\/transactions\/\d+$/.test(url.pathname), { timeout: 10000 });
    await appReady(budget);
    const beforeReturn = new URL(budget.url()).searchParams.get('return_to');
    check(row, 'detail carries filtered transaction return target', beforeReturn === listTarget, `return_to=${beforeReturn}`);
    const select = budget.locator('select').filter({ has: budget.locator('option') }).filter({ hasText: /Choose employee/ }).first();
    await select.selectOption({ index: 1 });
    const responses = await requestMutation(budget, /\/transactions\/\d+\/transition$/, () => budget.getByRole('button', { name: 'Assign', exact: true }).click());
    check(row, 'transaction mutation emitted successful response', responses.some((status) => status >= 200 && status < 400), JSON.stringify(responses));
    await appReady(budget);
    const afterReturn = new URL(budget.url()).searchParams.get('return_to');
    check(row, 'transaction mutation preserves return target', afterReturn === listTarget, `return_to=${afterReturn}`);
    await budget.getByRole('link', { name: '← Back to My Work' }).click();
    await budget.waitForURL((url) => `${url.pathname}${url.search}` === listTarget, { timeout: 10000 });
    check(row, 'transaction back returns exact filtered queue', pathOnly(budget.url()) === listTarget, pathOnly(budget.url()));
  });

  const engineering = sessions.engineering.page;
  await scenario('W03 correspondence mutation return continuity', { wave: 'W03', persona: 'Department Head — Engineering', page: engineering }, async (row) => {
    await engineering.setViewportSize({ width: 1280, height: 800 });
    const listTarget = '/correspondence?search=H1%20mutation%20correspondence%20acceptance';
    const result = await navigate(engineering, listTarget);
    check(row, 'filtered correspondence queue loads', result.status === 200, `status=${result.status}`);
    const open = engineering.getByRole('link', { name: /Open correspondence/i }).first();
    await open.waitFor({ state: 'visible', timeout: 5000 });
    await open.click();
    await engineering.waitForURL((url) => url.pathname === `/correspondence/${CORRESPONDENCE_ID}/workspace`, { timeout: 10000 });
    await appReady(engineering);
    const beforeReturn = new URL(engineering.url()).searchParams.get('return_to');
    check(row, 'detail carries filtered correspondence return target', beforeReturn === listTarget, `return_to=${beforeReturn}`);
    engineering.on('dialog', (dialog) => dialog.accept());
    const responses = await requestMutation(engineering, /\/correspondence\/.+\/workspace\/register$/, () => engineering.getByRole('button', { name: 'Register correspondence' }).click());
    check(row, 'correspondence mutation emitted successful response', responses.some((status) => status >= 200 && status < 400), JSON.stringify(responses));
    await appReady(engineering);
    const afterReturn = new URL(engineering.url()).searchParams.get('return_to');
    check(row, 'correspondence mutation preserves return target', afterReturn === listTarget, `return_to=${afterReturn}`);
    await engineering.getByRole('link', { name: /Back to Correspondence Inbox/i }).click();
    await engineering.waitForURL((url) => `${url.pathname}${url.search}` === listTarget, { timeout: 10000 });
    check(row, 'correspondence back returns exact filtered inbox', pathOnly(engineering.url()) === listTarget, pathOnly(engineering.url()));
  });

  const mayor = sessions.mayor.page;
  await scenario('W03 Travel Order mutation return continuity', { wave: 'W03', persona: 'Municipal Executive', page: mayor }, async (row) => {
    await mayor.setViewportSize({ width: 1280, height: 800 });
    const listTarget = `/travel-orders?search=${encodeURIComponent(cancelTravelReference)}`;
    const result = await navigate(mayor, listTarget);
    check(row, 'filtered Travel Order registry loads', result.status === 200, `status=${result.status}`);
    const open = mayor.getByRole('link', { name: /Open travel order/i }).first();
    await open.waitFor({ state: 'visible', timeout: 5000 });
    await open.click();
    await mayor.waitForURL((url) => /^\/travel-orders\/.+/.test(url.pathname), { timeout: 10000 });
    await appReady(mayor);
    const beforeReturn = new URL(mayor.url()).searchParams.get('return_to');
    check(row, 'detail carries filtered Travel Order return target', beforeReturn === listTarget, `return_to=${beforeReturn}`);
    await mayor.getByLabel('New status').selectOption('cancelled');
    const responses = await requestMutation(mayor, /\/travel-orders\/.+\/status$/, () => mayor.getByRole('button', { name: 'Update status' }).click());
    check(row, 'Travel Order mutation emitted successful response', responses.some((status) => status >= 200 && status < 400), JSON.stringify(responses));
    await appReady(mayor);
    const afterReturn = new URL(mayor.url()).searchParams.get('return_to');
    check(row, 'Travel Order mutation preserves return target', afterReturn === listTarget, `return_to=${afterReturn}`);
    await mayor.getByRole('link', { name: /Approved Travel Orders/i }).first().click();
    await mayor.waitForURL((url) => `${url.pathname}${url.search}` === listTarget, { timeout: 10000 });
    check(row, 'Travel Order back returns exact filtered registry', pathOnly(mayor.url()) === listTarget, pathOnly(mayor.url()));
  });
}

async function accessibilityMatrix(sessions) {
  const matrix = [
    ['Department Head — Engineering', sessions.engineering.page, '/dashboard', { width: 1440, height: 900 }, ['Light', 'light']],
    ['Department Head — Budget', sessions.budget.page, '/transactions', { width: 1280, height: 800 }, ['Dark', 'dark']],
    ['Municipal Executive', sessions.mayor.page, '/mayor-office', { width: 768, height: 1024 }, ['Light', 'light']],
    ['Employee', sessions.employee.page, '/dashboard', { width: 430, height: 932 }, ['Light', 'light']],
    ['Human Resources', sessions.hr.page, '/hris', { width: 390, height: 844 }, ['Dark', 'dark']],
    ['Legislative Office', sessions.legislative.page, '/legislation', { width: 360, height: 800 }, ['Light', 'light']],
    ['System Administration', sessions.admin.page, '/admin', { width: 1600, height: 900 }, ['Dark', 'dark']],
  ];

  for (const [persona, page, route, viewport, theme] of matrix) {
    await scenario(`Accessibility mechanics — ${persona} ${viewport.width}x${viewport.height} ${theme[1]}`, { wave: 'cross-wave', persona, page }, async (row) => {
      await page.setViewportSize(viewport);
      await navigate(page, '/dashboard');
      if (viewport.width >= 1024) await setAppearance(page, theme[0], theme[1]);
      const result = await navigate(page, route);
      check(row, `${route} loads`, result.status === 200, `status=${result.status}`);
      await verifyPageBasics(row, page, `${persona} ${route}`);
      const focus = await focusProbe(page);
      check(row, 'keyboard Tab reaches semantic control', !!focus && ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'SUMMARY'].includes(focus.tag), JSON.stringify(focus));
      check(row, 'keyboard focus is programmatically visible', !!focus && focus.focusVisible && (focus.outline !== 'none 0px' || focus.boxShadow !== 'none'), JSON.stringify(focus));
      const contrast = await contrastOfHeading(page);
      check(row, 'primary heading contrast >= 4.5', !!contrast && contrast.ratio >= 4.5, JSON.stringify(contrast));
      await shot(page, {
        name: `matrix-${persona.toLowerCase().replace(/[^a-z0-9]+/g, '-')}-${viewport.width}x${viewport.height}-${theme[1]}.png`,
        persona,
        route,
        viewport: `${viewport.width}x${viewport.height}`,
        theme: theme[1],
        purpose: 'Representative persona/viewport/theme/accessibility mechanics matrix',
      });
    });
  }
}

async function main() {
  report.git.actualSha = execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
  report.git.exactHead = report.git.actualSha === EXPECTED_SHA;
  report.git.productAnchorAncestor = execFileSync('git', ['merge-base', '--is-ancestor', PRODUCT_ANCHOR, 'HEAD'], { encoding: 'utf8' }) === '';
  if (!report.git.exactHead || !report.git.productAnchorAncestor) {
    report.failure = { stage: 'authority', summary: 'Exact QA head or integrated product-source ancestry could not be proven.' };
    await writeReport();
    process.exitCode = 1;
    return;
  }

  const isolation = probe('isolation');
  if (!isolation.isolated || isolation.database !== 'talibon_h1_mutations') {
    report.failure = { stage: 'isolation', summary: `Expected isolated testing database talibon_h1_mutations; got ${JSON.stringify(isolation)}` };
    await writeReport();
    process.exitCode = 1;
    return;
  }
  probe('setup', marker);

  const browser = await chromium.launch({ headless: true });
  const sessions = {};
  try {
    await showcaseShell(browser);

    sessions.engineering = await login(browser, { email: 'engineering@talibon.demo', label: 'Department Head — Engineering' });
    sessions.budget = await login(browser, { email: 'budget@talibon.demo', label: 'Department Head — Budget' });
    sessions.mayor = await login(browser, { email: 'mayor@talibon.demo', label: 'Municipal Executive' });
    sessions.employee = await login(browser, { email: 'employee@talibon.demo', label: 'Employee' });
    sessions.hr = await login(browser, { email: 'hr@talibon.demo', label: 'Human Resources' });
    sessions.legislative = await login(browser, { email: 'legislative@talibon.demo', label: 'Legislative Office' });
    sessions.admin = await login(browser, { email: 'admin@talibon.demo', label: 'System Administration' });

    await dashboardHierarchy(sessions.engineering.page, 'Department Head — Engineering', 'w02-dashboard-engineering-1440-light.png');
    await dashboardHierarchy(sessions.mayor.page, 'Municipal Executive', 'w02-dashboard-executive-1280-light.png');

    await contextContinuityChecks(sessions);
    await planningChecks(sessions.engineering);
    await utilityChecks(sessions.engineering);
    await messagingChecks(sessions.engineering);
    await completionChecks(sessions);
    await accessibilityMatrix(sessions);

    report.completed = report.summary.failed === 0;
    report.failure = report.completed ? null : { stage: 'matrix', summary: `${report.summary.failed} W08 scenario(s) failed.` };
    await writeReport();
    if (!report.completed) process.exitCode = 1;
    else console.log(`W08_BROWSER_QA_PASS scenarios=${report.summary.scenarios} checks=${report.summary.checks} screenshots=${report.summary.screenshots}`);
  } catch (error) {
    report.completed = false;
    report.failure = { stage: 'fatal-harness', summary: clean(error?.stack || error) };
    await writeReport();
    console.error(`W08_BROWSER_QA_FAIL ${clean(error?.message || error)}`);
    process.exitCode = 1;
  } finally {
    for (const session of Object.values(sessions)) await session.context.close().catch(() => {});
    await browser.close();
  }
}

await writeReport();
await main();
