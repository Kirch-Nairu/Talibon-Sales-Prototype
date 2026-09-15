import { chromium } from 'playwright';
import crypto from 'node:crypto';
import fs from 'node:fs/promises';

const BASE = process.env.QA_BASE_URL || 'http://127.0.0.1:8000';
const PASSWORD = process.env.QA_DEMO_PASSWORD;
if (!PASSWORD) throw new Error('QA_DEMO_PASSWORD is required');

const F1_REPORT_PATH = 'storage/app/qa/demo-readiness-report.json';
const F2_REPORT_PATH = 'storage/app/qa/f2-readiness-report.json';
const F3_SCREENSHOT_DIR = 'storage/app/qa/f3-screenshots';
const F3_REPORT_PATH = 'storage/app/qa/f3-readiness-report.json';

const EXPECTED_HISTORICAL = 174;
const EXPECTED_F1 = 133;
const EXPECTED_ACCOUNTS = 7;
const EXPECTED_F1_SCREENSHOTS = 7;
const EXPECTED_F2_SCREENSHOTS = 5;
const EXPECTED_F3_TARGETS = 5;
const EXPECTED_F3_SCREENSHOTS = 7;

const TARGETS = [
  {
    key: 'my-work',
    route: '/transactions',
    primaryText: 'Search work',
    commonLabels: ['Status', 'Priority'],
    activateCommon: { type: 'select', label: 'Status' },
    advancedLabel: 'Current office',
    activateAdvanced: { type: 'select', label: 'Current office' },
    clear: { type: 'aria', name: 'Clear filters' },
    resetChecks: [
      { type: 'input', label: 'Search work', value: '' },
      { type: 'select', label: 'Status', value: '' },
      { type: 'select', label: 'Priority', value: '' },
      { type: 'select', label: 'Current office', value: '' },
    ],
    desktopScreenshot: 'my-work-desktop-light.png',
    mobileScreenshot: 'my-work-mobile-filters-open.png',
  },
  {
    key: 'correspondence',
    route: '/correspondence',
    primaryText: 'Search correspondence',
    commonLabels: ['Lifecycle', 'Assigned to me', 'Action required'],
    activateCommon: { type: 'select', label: 'Lifecycle' },
    advancedLabel: 'Aging',
    activateAdvanced: { type: 'select-value', label: 'Aging', value: 'overdue' },
    clear: { type: 'role', name: 'Clear' },
    resetChecks: [
      { type: 'input', label: 'Search correspondence', value: '' },
      { type: 'select', label: 'Lifecycle', value: '' },
      { type: 'checkbox', label: 'Assigned to me', value: false },
      { type: 'checkbox', label: 'Action required', value: false },
      { type: 'select', label: 'Aging', value: '' },
    ],
    activeScreenshot: 'correspondence-desktop-active-filters.png',
  },
  {
    key: 'records',
    route: '/records',
    primaryText: 'Search records',
    commonLabels: ['Record Type', 'Status / Lifecycle'],
    activateCommon: { type: 'select', label: 'Record Type' },
    advancedLabel: 'From',
    activateAdvanced: { type: 'fill', label: 'From', value: '2026-01-01' },
    clear: { type: 'aria', name: 'Clear records filters' },
    resetChecks: [
      { type: 'input', label: 'Search records', value: '' },
      { type: 'select', label: 'Record Type', value: 'all' },
      { type: 'select', label: 'Status / Lifecycle', value: '' },
      { type: 'fill', label: 'From', value: '' },
      { type: 'fill', label: 'To', value: '' },
    ],
    mobileScreenshot: 'records-mobile-filters-open.png',
  },
  {
    key: 'travel-orders',
    route: '/travel-orders',
    primaryText: 'Search approved orders',
    commonLabels: ['Status'],
    activateCommon: { type: 'select', label: 'Status' },
    advancedLabel: 'Travel from',
    activateAdvanced: { type: 'fill', label: 'Travel from', value: '2026-01-01' },
    clear: { type: 'aria', name: 'Clear Travel Order filters' },
    resetChecks: [
      { type: 'input', label: 'Search approved orders', value: '' },
      { type: 'select', label: 'Status', value: '' },
      { type: 'fill', label: 'Travel from', value: '' },
      { type: 'fill', label: 'Travel to', value: '' },
    ],
    desktopScreenshot: 'travel-orders-desktop-light.png',
  },
  {
    key: 'reports',
    route: '/reports?report=transaction-aging',
    primaryText: 'Current report scope',
    commonLabels: ['Office', 'Status'],
    activateCommon: { type: 'select', label: 'Status' },
    advancedLabel: 'Date from',
    activateAdvanced: { type: 'fill', label: 'Date from', value: '2026-01-01' },
    clear: { type: 'role', name: 'Reset' },
    resetChecks: [
      { type: 'select', label: 'Office', value: '' },
      { type: 'select', label: 'Status', value: '' },
      { type: 'fill', label: 'Date from', value: '' },
      { type: 'fill', label: 'Date to', value: '' },
      { type: 'select', label: 'Priority', value: '' },
      { type: 'select', label: 'Transaction type', value: '' },
    ],
    desktopScreenshot: 'reports-desktop-light.png',
    mobileScreenshot: 'reports-mobile-filters-open.png',
  },
];

const report = {
  generatedAt: new Date().toISOString(),
  completed: false,
  checks: [],
  targets: [],
  screenshots: [],
  diagnostics: [],
  baseline: null,
  failure: null,
  notes: [
    'F3 QA is additive and runs after the accepted historical/F1 and F2 browser gates.',
    'demo-readiness.mjs and f2-readiness.mjs remain unchanged.',
    'A fresh synthetic seed is applied before this independent F3 presentation run.',
    'F3 verifies progressive disclosure only; backend, authorization, route, workflow, and query semantics are not changed.',
    'Runtime password and MFA enrollment material are masked and never written to evidence.',
  ],
};

const sensitiveValues = new Set([PASSWORD]);
const runtimeErrors = [];
let currentStage = 'bootstrap';
let currentUrl = BASE;
let activePage = null;

function rememberSensitive(...values) {
  for (const value of values.flat()) {
    if (typeof value === 'string' && value.length > 0) sensitiveValues.add(value);
  }
}

function clean(value) {
  let output = String(value ?? '');
  for (const secret of sensitiveValues) output = output.replaceAll(secret, '[MASKED]');
  return output;
}

function sanitize(value) {
  if (typeof value === 'string') return clean(value);
  if (Array.isArray(value)) return value.map(sanitize);
  if (value && typeof value === 'object') {
    return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, sanitize(item)]));
  }
  return value;
}

const reportJson = () => JSON.stringify(sanitize(report), null, 2);

function safePath(value) {
  try {
    return new URL(value, BASE).pathname;
  } catch {
    return '[unavailable]';
  }
}

function checkpoint(stage, page = activePage) {
  currentStage = stage;
  if (page) currentUrl = page.url();
}

function diagnostic(type, details) {
  report.diagnostics.push({
    at: new Date().toISOString(),
    stage: clean(currentStage),
    path: clean(safePath(activePage?.url?.() || currentUrl)),
    type: clean(type),
    details: clean(details),
  });
  if (report.diagnostics.length > 100) report.diagnostics.shift();
}

function f3(name, ok, details = '', severity = 'P1') {
  const row = { name: `F3: ${clean(name)}`, ok: Boolean(ok), details: clean(details), severity };
  report.checks.push(row);
  if (!row.ok) {
    diagnostic('check-failure', `${row.name}: ${row.details}`);
    throw new Error(`${row.name}: ${row.details}`);
  }
}

async function writeReport() {
  await fs.mkdir('storage/app/qa', { recursive: true });
  await fs.writeFile(F3_REPORT_PATH, reportJson());
}

function monitor(page) {
  page.on('pageerror', (error) => {
    const item = { type: 'pageerror', path: safePath(page.url()), details: error.message };
    runtimeErrors.push(item);
    diagnostic(item.type, item.details);
  });
  page.on('console', (message) => {
    if (message.type() === 'error') diagnostic('console.error', message.text());
  });
  page.on('response', (response) => {
    if (response.status() >= 500) {
      const item = { type: 'server-5xx', path: safePath(response.url()), details: `${response.status()} ${safePath(response.url())}` };
      runtimeErrors.push(item);
      diagnostic(item.type, item.details);
    }
  });
  page.on('requestfailed', (request) => {
    diagnostic('requestfailed', `${request.resourceType()} ${safePath(request.url())} ${request.failure()?.errorText || 'request failed'}`);
  });
}

function decodeBase32(input) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  let bits = '';
  for (const char of input.replace(/=+$/g, '').replace(/\s+/g, '').toUpperCase()) {
    const index = alphabet.indexOf(char);
    if (index < 0) throw new Error('Invalid base32 MFA secret');
    bits += index.toString(2).padStart(5, '0');
  }
  const bytes = [];
  for (let i = 0; i + 8 <= bits.length; i += 8) bytes.push(parseInt(bits.slice(i, i + 8), 2));
  return Buffer.from(bytes);
}

function totp(secret) {
  const counter = Buffer.alloc(8);
  counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
  const digest = crypto.createHmac('sha1', decodeBase32(secret)).update(counter).digest();
  const offset = digest.at(-1) & 0x0f;
  const value = ((digest[offset] & 0x7f) << 24)
    | (digest[offset + 1] << 16)
    | (digest[offset + 2] << 8)
    | digest[offset + 3];
  return String(value % 1_000_000).padStart(6, '0');
}

async function loadAcceptedBaselines() {
  checkpoint('accepted Browser baselines');
  const acceptedF1 = JSON.parse(await fs.readFile(F1_REPORT_PATH, 'utf8'));
  const acceptedF2 = JSON.parse(await fs.readFile(F2_REPORT_PATH, 'utf8'));

  const f1Checks = acceptedF1.checks.filter((row) => row.name.startsWith('F1:')).length;
  const historicalChecks = acceptedF1.checks.length - f1Checks;
  const accounts = acceptedF1.accounts.length;
  const f1Screenshots = acceptedF1.screenshots.length;
  const f2Checks = acceptedF2.checks.filter((row) => row.name.startsWith('F2:')).length;
  const f2Failures = acceptedF2.checks.filter((row) => row.ok === false).length;
  const f2Screenshots = acceptedF2.screenshots.length;

  report.baseline = {
    historicalChecks,
    f1Checks,
    accounts,
    f1Screenshots,
    f2Checks,
    f2Failures,
    f2Screenshots,
    f2Completed: acceptedF2.completed === true,
  };

  f3('historical Browser matrix remains exactly 174 checks', historicalChecks === EXPECTED_HISTORICAL, `historical=${historicalChecks}`, 'P0');
  f3('F1 Browser matrix remains exactly 133 checks', f1Checks === EXPECTED_F1, `f1=${f1Checks}`, 'P0');
  f3('representative account matrix remains 7/7', accounts === EXPECTED_ACCOUNTS, `accounts=${accounts}`, 'P0');
  f3('F1 screenshot set remains 7/7', f1Screenshots === EXPECTED_F1_SCREENSHOTS, `screenshots=${f1Screenshots}`, 'P0');
  f3('F2 acceptance report is completed', acceptedF2.completed === true, `completed=${acceptedF2.completed}`, 'P0');
  f3('F2 acceptance has zero failures', f2Failures === 0, `failures=${f2Failures}`, 'P0');
  f3('F2 screenshot set remains 5/5', f2Screenshots === EXPECTED_F2_SCREENSHOTS, `screenshots=${f2Screenshots}`, 'P0');
  f3(
    'F2 baseline still points to accepted historical/F1 authority',
    acceptedF2.baseline?.historicalChecks === EXPECTED_HISTORICAL
      && acceptedF2.baseline?.f1Checks === EXPECTED_F1
      && acceptedF2.baseline?.accounts === EXPECTED_ACCOUNTS
      && acceptedF2.baseline?.screenshots === EXPECTED_F1_SCREENSHOTS,
    JSON.stringify(acceptedF2.baseline),
    'P0',
  );
}

async function loginAndEnrollDepartmentHead(page) {
  checkpoint('F3 department-head authentication', page);
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.getByLabel('Email').fill('engineering@talibon.demo');
  await page.getByLabel('Password').fill(PASSWORD);
  await Promise.all([
    page.waitForURL((url) => ['/dashboard', '/security/mfa/enroll', '/security/mfa/challenge'].includes(url.pathname)),
    page.getByRole('button', { name: 'Sign In' }).click(),
  ]);

  const path = safePath(page.url());
  f3('fresh F3 department-head login does not reuse an MFA challenge', path !== '/security/mfa/challenge', `path=${path}`, 'P0');

  if (path === '/security/mfa/enroll') {
    const secret = (await page.locator('code').first().innerText()).trim();
    rememberSensitive(secret);
    f3('F3 department-head uses normal MFA enrollment flow', secret.length >= 16, 'MFA enrollment secret unavailable', 'P0');
    await page.getByLabel('Six-digit verification code').fill(totp(secret));
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/security/mfa/recovery-codes'),
      page.getByRole('button', { name: /Confirm MFA enrollment/i }).click(),
    ]);
    const codes = (await page.locator('pre').innerText()).trim().split(/\s+/).filter(Boolean);
    rememberSensitive(codes);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/dashboard'),
      page.getByRole('link', { name: /Continue to portal/i }).click(),
    ]);
  }

  f3('F3 department-head reaches dashboard after authentication', safePath(page.url()) === '/dashboard', `path=${safePath(page.url())}`, 'P0');
}

async function chooseAppearance(page, label, expectedPreference, expectedResolved) {
  const group = page.locator('[role="group"][aria-label="Appearance"]:visible').first();
  await group.waitFor({ state: 'visible', timeout: 10000 });
  const button = group.getByRole('button', { name: label, exact: true });
  await button.click();
  await page.waitForFunction(
    ({ preference, resolved }) => {
      const root = document.documentElement;
      return window.localStorage.getItem('talibon.appearance') === preference
        && root.dataset.appearance === preference
        && root.style.colorScheme === resolved
        && root.classList.contains('dark') === (resolved === 'dark');
    },
    { preference: expectedPreference, resolved: expectedResolved },
  );
}

function filterButton(page) {
  return page.getByRole('button', { name: /^Filters(?: \d+)?$/ }).first();
}

async function filterForm(page) {
  const button = filterButton(page);
  await button.waitFor({ state: 'visible' });
  return button.locator('xpath=ancestor::form[1]');
}

async function controlByCaption(form, caption) {
  const labels = form.locator('label');
  const index = await labels.evaluateAll((nodes, wanted) => nodes.findIndex((label) => {
    const normalize = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const directText = normalize(Array.from(label.childNodes)
      .filter((node) => node.nodeType === Node.TEXT_NODE)
      .map((node) => node.textContent || '')
      .join(' '));
    const firstElement = label.firstElementChild;
    const firstElementCaption = firstElement?.tagName === 'SPAN'
      ? normalize(firstElement.textContent)
      : '';
    return directText === wanted || firstElementCaption === wanted;
  }), caption);

  if (index < 0) throw new Error(`Unable to locate filter label caption: ${caption}`);

  const control = labels.nth(index).locator('input, select, textarea').first();
  if (await control.count() !== 1) throw new Error(`Unable to locate filter control for caption: ${caption}`);
  return control;
}

async function controlledPanel(page) {
  const button = filterButton(page);
  const id = await button.getAttribute('aria-controls');
  f3(`${currentStage}: Filters aria-controls is present`, Boolean(id), `aria-controls=${id}`);
  const escaped = String(id).replaceAll('\\', '\\\\').replaceAll('"', '\\"');
  const root = page.locator(`[id="${escaped}"]`).first();
  f3(`${currentStage}: aria-controls points to an existing filter panel`, await root.count() === 1, `id=${id}`);
  return { button, root, id };
}

async function overflowMetrics(page) {
  return page.evaluate(() => ({
    viewport: window.innerWidth,
    root: document.documentElement.scrollWidth,
    body: document.body?.scrollWidth || 0,
  }));
}

async function verifyNoHorizontalOverflow(page, label) {
  const metrics = await overflowMetrics(page);
  f3(
    `${label}: no horizontal overflow`,
    metrics.root <= metrics.viewport + 1 && metrics.body <= metrics.viewport + 1,
    JSON.stringify(metrics),
    'P2',
  );
}

async function verifyNoRuntimeErrorsSince(label, startIndex) {
  const errors = runtimeErrors.slice(startIndex);
  f3(`${label}: no page errors or server 5xx`, errors.length === 0, JSON.stringify(errors), 'P1');
}

async function saveScreenshot(page, fileName) {
  await fs.mkdir(F3_SCREENSHOT_DIR, { recursive: true });
  const path = `${F3_SCREENSHOT_DIR}/${fileName}`;
  await page.screenshot({ path, fullPage: false, animations: 'disabled' });
  report.screenshots.push(path);
  f3(`sanitized F3 screenshot captured: ${fileName}`, true, path, 'P2');
}

async function selectFirstRealOption(locator, label) {
  const options = await locator.locator('option').evaluateAll((nodes) => nodes.map((node) => ({
    value: node.value,
    text: node.textContent?.trim() || '',
  })));
  const option = options.find((item) => item.value !== '' && item.value !== 'all');
  f3(`${currentStage}: ${label} has an activatable option`, Boolean(option), JSON.stringify(options));
  await locator.selectOption(option.value);
  return option;
}

async function activateControl(form, spec) {
  const control = await controlByCaption(form, spec.label);
  await control.waitFor({ state: 'visible' });
  if (spec.type === 'select') {
    await selectFirstRealOption(control, spec.label);
  } else if (spec.type === 'select-value') {
    await control.selectOption(spec.value);
  } else if (spec.type === 'fill') {
    await control.fill(spec.value);
  } else if (spec.type === 'checkbox') {
    await control.check();
  } else {
    throw new Error(`Unsupported activation type: ${spec.type}`);
  }
}

async function activeFilterSnapshot(form, page) {
  const group = form.getByLabel('Active filters');
  const groupVisible = await group.count() > 0 && await group.isVisible();
  const chips = groupVisible
    ? await group.locator('span').evaluateAll((nodes) => nodes.map((node) => {
        const style = getComputedStyle(node);
        const rect = node.getBoundingClientRect();
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const rgb = (value) => {
          ctx.clearRect(0, 0, 1, 1);
          ctx.fillStyle = '#000';
          ctx.fillStyle = value;
          ctx.fillRect(0, 0, 1, 1);
          const data = ctx.getImageData(0, 0, 1, 1).data;
          return [data[0], data[1], data[2]];
        };
        const luminance = ([r, g, b]) => {
          const channel = (value) => {
            const normalized = value / 255;
            return normalized <= 0.04045 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
          };
          return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
        };
        const foreground = luminance(rgb(style.color));
        const background = luminance(rgb(style.backgroundColor));
        const ratio = (Math.max(foreground, background) + 0.05) / (Math.min(foreground, background) + 0.05);
        return {
          text: node.textContent?.trim() || '',
          ratio,
          width: rect.width,
          height: rect.height,
          clipped: node.scrollWidth > node.clientWidth + 1,
        };
      }))
    : [];
  const buttonText = (await filterButton(page).innerText()).trim();
  return { chips, buttonText };
}

async function verifyActiveFilterState(form, page, label, minimum = 1) {
  await form.getByLabel('Active filters').waitFor({ state: 'visible' });
  const snapshot = await activeFilterSnapshot(form, page);
  const count = snapshot.chips.length;
  f3(`${label}: active filter chips are present`, count >= minimum, JSON.stringify(snapshot));
  f3(
    `${label}: active filter chips are readable`,
    snapshot.chips.every((chip) => chip.text.length > 0 && chip.ratio >= 4.5 && !chip.clipped && chip.width > 0 && chip.height >= 16),
    JSON.stringify(snapshot.chips),
    'P2',
  );
  f3(`${label}: Filters N accurately reflects active state`, snapshot.buttonText === `Filters ${count}`, JSON.stringify(snapshot));
  return count;
}

async function verifyResetState(form, target) {
  for (const check of target.resetChecks) {
    const control = await controlByCaption(form, check.label);
    if (check.type === 'checkbox') {
      f3(`${target.key}: reset clears ${check.label}`, await control.isChecked() === check.value, `checked=${await control.isChecked()}`);
    } else {
      f3(`${target.key}: reset clears ${check.label}`, await control.inputValue() === check.value, `value=${await control.inputValue()}`);
    }
  }
}

async function clearTarget(page, target) {
  const form = await filterForm(page);
  if (target.clear.type === 'aria') {
    await form.getByRole('button', { name: target.clear.name, exact: true }).click();
  } else {
    await form.getByRole('button', { name: target.clear.name, exact: true }).click();
  }

  await page.waitForFunction(() => {
    const buttons = Array.from(document.querySelectorAll('button'));
    return buttons.some((button) => button.textContent?.trim() === 'Filters')
      && !document.querySelector('[aria-label="Active filters"]');
  });

  const resetForm = await filterForm(page);
  f3(`${target.key}: Clear/reset removes active chips`, await resetForm.getByLabel('Active filters').count() === 0, 'active chips remained');
  f3(`${target.key}: Clear/reset restores Filters without count`, (await filterButton(page).innerText()).trim() === 'Filters', await filterButton(page).innerText());
  await verifyResetState(resetForm, target);
}

async function verifyInitialProgressiveState(page, target, form) {
  f3(`${target.key}: primary control remains visible`, await form.getByText(target.primaryText, { exact: true }).first().isVisible(), target.primaryText);
  for (const label of target.commonLabels) {
    const commonControl = await controlByCaption(form, label);
    f3(`${target.key}: common filter remains visible: ${label}`, await commonControl.isVisible(), label);
  }

  const { button, root } = await controlledPanel(page);
  f3(`${target.key}: Filters control exists`, await button.isVisible(), 'Filters button not visible');
  f3(`${target.key}: aria-expanded=false initially`, await button.getAttribute('aria-expanded') === 'false', `aria-expanded=${await button.getAttribute('aria-expanded')}`);
  f3(`${target.key}: advanced controls are hidden initially`, !(await root.isVisible()), `panelVisible=${await root.isVisible()}`);
  const advancedControl = await controlByCaption(form, target.advancedLabel);
  f3(`${target.key}: advanced control is hidden initially`, !(await advancedControl.isVisible()), target.advancedLabel);
}

async function openDesktopFilters(page, target, form) {
  const { button, root } = await controlledPanel(page);
  await button.click();
  await page.waitForFunction(() => {
    const candidates = Array.from(document.querySelectorAll('button'));
    return candidates.some((item) => item.getAttribute('aria-expanded') === 'true' && item.textContent?.trim()?.startsWith('Filters'));
  });
  await root.waitFor({ state: 'visible' });
  const advancedControl = await controlByCaption(form, target.advancedLabel);
  f3(`${target.key}: Filters opens advanced controls`, await advancedControl.isVisible(), target.advancedLabel);
  const metrics = await root.evaluate((element) => ({
    position: getComputedStyle(element).position,
    display: getComputedStyle(element).display,
  }));
  f3(`${target.key}: desktop advanced filters expand inline/static`, metrics.position === 'static' && metrics.display !== 'none', JSON.stringify(metrics), 'P2');
  const visibleCloseControls = await root.locator('button[aria-label="Close filters"]').evaluateAll((buttons) => buttons.filter((button) => {
    const style = getComputedStyle(button);
    return style.display !== 'none' && style.visibility !== 'hidden' && button.getBoundingClientRect().width > 0;
  }).length);
  f3(`${target.key}: desktop does not expose mobile Close filters controls`, visibleCloseControls === 0, `visible=${visibleCloseControls}`, 'P2');
}

async function closeFilters(page) {
  const button = filterButton(page);
  if (await button.getAttribute('aria-expanded') === 'true') {
    await button.click();
    await page.waitForFunction(() => Array.from(document.querySelectorAll('button')).some((item) => (
      item.textContent?.trim()?.startsWith('Filters') && item.getAttribute('aria-expanded') === 'false'
    )));
  }
}

async function exerciseDesktop(page, target) {
  checkpoint(`${target.key} desktop`);
  const errorStart = runtimeErrors.length;
  await page.setViewportSize({ width: 1440, height: 900 });
  const response = await page.goto(`${BASE}${target.route}`, { waitUntil: 'domcontentloaded' });
  currentUrl = page.url();
  f3(`${target.key}: desktop authorized page loads`, response?.status() === 200, `status=${response?.status()} path=${safePath(page.url())}`, 'P1');

  const form = await filterForm(page);
  await verifyInitialProgressiveState(page, target, form);
  await verifyNoHorizontalOverflow(page, `${target.key} desktop`);

  if (target.desktopScreenshot) await saveScreenshot(page, target.desktopScreenshot);

  await activateControl(form, target.activateCommon);
  const commonCount = await verifyActiveFilterState(form, page, `${target.key} common active filter`);

  await openDesktopFilters(page, target, form);
  await activateControl(form, target.activateAdvanced);
  const advancedCount = await verifyActiveFilterState(form, page, `${target.key} advanced active filter`, commonCount + 1);
  f3(`${target.key}: advanced selection increases active filter count`, advancedCount > commonCount, `common=${commonCount} advanced=${advancedCount}`);

  if (target.activeScreenshot) {
    await closeFilters(page);
    await saveScreenshot(page, target.activeScreenshot);
  } else {
    await closeFilters(page);
  }

  await clearTarget(page, target);
  await verifyNoHorizontalOverflow(page, `${target.key} desktop after reset`);
  await verifyNoRuntimeErrorsSince(`${target.key} desktop`, errorStart);
}

async function exerciseMobile(page, target) {
  checkpoint(`${target.key} mobile`);
  const errorStart = runtimeErrors.length;
  await page.setViewportSize({ width: 390, height: 844 });
  const response = await page.goto(`${BASE}${target.route}`, { waitUntil: 'domcontentloaded' });
  currentUrl = page.url();
  f3(`${target.key}: mobile authorized page loads`, response?.status() === 200, `status=${response?.status()} path=${safePath(page.url())}`, 'P1');

  const form = await filterForm(page);
  f3(`${target.key}: mobile primary page remains structurally intact`, await form.getByText(target.primaryText, { exact: true }).first().isVisible(), target.primaryText);
  const { button, root } = await controlledPanel(page);
  f3(`${target.key}: mobile aria-expanded=false initially`, await button.getAttribute('aria-expanded') === 'false', `aria-expanded=${await button.getAttribute('aria-expanded')}`);

  await activateControl(form, target.activateCommon);
  await verifyActiveFilterState(form, page, `${target.key} mobile active filter`);

  await filterButton(page).click();
  await root.waitFor({ state: 'visible' });
  const advancedControl = await controlByCaption(form, target.advancedLabel);
  f3(`${target.key}: mobile Filters opens advanced controls`, await advancedControl.isVisible(), target.advancedLabel);

  const rootMetrics = await root.evaluate((element) => ({
    position: getComputedStyle(element).position,
    left: element.getBoundingClientRect().left,
    right: element.getBoundingClientRect().right,
    width: element.getBoundingClientRect().width,
  }));
  f3(`${target.key}: mobile advanced filters use fixed bottom-sheet layer`, rootMetrics.position === 'fixed', JSON.stringify(rootMetrics), 'P2');

  const backdrop = root.locator('button[aria-label="Close filters"].absolute').first();
  const explicitClose = root.locator('button[aria-label="Close filters"]:not(.absolute)').first();
  f3(`${target.key}: mobile backdrop is visible`, await backdrop.isVisible(), 'backdrop hidden', 'P2');
  f3(`${target.key}: mobile Close filters control is visible`, await explicitClose.isVisible(), 'explicit close hidden', 'P2');

  const dialog = root.getByRole('dialog').first();
  const dialogMetrics = await dialog.evaluate((element) => {
    const rect = element.getBoundingClientRect();
    return {
      left: rect.left,
      right: rect.right,
      top: rect.top,
      bottom: rect.bottom,
      width: rect.width,
      height: rect.height,
      viewportWidth: window.innerWidth,
      viewportHeight: window.innerHeight,
      overflowY: getComputedStyle(element).overflowY,
    };
  });
  f3(
    `${target.key}: mobile filter panel fits 390x844 viewport`,
    dialogMetrics.left >= -1
      && dialogMetrics.right <= dialogMetrics.viewportWidth + 1
      && dialogMetrics.top >= -1
      && dialogMetrics.bottom <= dialogMetrics.viewportHeight + 1
      && dialogMetrics.width <= dialogMetrics.viewportWidth + 1
      && dialogMetrics.height <= dialogMetrics.viewportHeight + 1,
    JSON.stringify(dialogMetrics),
    'P2',
  );
  await verifyNoHorizontalOverflow(page, `${target.key} mobile filters open`);

  if (target.mobileScreenshot) await saveScreenshot(page, target.mobileScreenshot);

  await explicitClose.click();
  await page.waitForFunction(() => Array.from(document.querySelectorAll('button')).some((item) => (
    item.textContent?.trim()?.startsWith('Filters') && item.getAttribute('aria-expanded') === 'false'
  )));
  f3(`${target.key}: mobile explicit Close filters collapses panel`, !(await root.isVisible()), `panelVisible=${await root.isVisible()}`, 'P2');

  await clearTarget(page, target);
  await verifyNoRuntimeErrorsSince(`${target.key} mobile`, errorStart);
}

async function verifyDarkContrastRegression(page) {
  checkpoint('F3 dark appearance contrast');
  const errorStart = runtimeErrors.length;
  await page.setViewportSize({ width: 1440, height: 900 });
  const response = await page.goto(`${BASE}/transactions`, { waitUntil: 'domcontentloaded' });
  f3('dark contrast target loads', response?.status() === 200, `status=${response?.status()}`);
  await chooseAppearance(page, 'Dark', 'dark', 'dark');

  const form = await filterForm(page);
  const search = form.locator('input').first();
  const button = filterButton(page);
  const metrics = await page.evaluate(({ searchSelector }) => {
    const searchElement = document.querySelector(searchSelector);
    const filterButton = Array.from(document.querySelectorAll('button')).find((item) => item.textContent?.trim()?.startsWith('Filters'));
    const contrast = (element) => {
      const style = getComputedStyle(element);
      const canvas = document.createElement('canvas');
      canvas.width = 1;
      canvas.height = 1;
      const ctx = canvas.getContext('2d', { willReadFrequently: true });
      const rgb = (value) => {
        ctx.clearRect(0, 0, 1, 1);
        ctx.fillStyle = '#000';
        ctx.fillStyle = value;
        ctx.fillRect(0, 0, 1, 1);
        const data = ctx.getImageData(0, 0, 1, 1).data;
        return [data[0], data[1], data[2]];
      };
      const luminance = ([r, g, b]) => {
        const channel = (value) => {
          const normalized = value / 255;
          return normalized <= 0.04045 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
      };
      const foreground = luminance(rgb(style.color));
      const background = luminance(rgb(style.backgroundColor));
      return {
        color: style.color,
        backgroundColor: style.backgroundColor,
        ratio: (Math.max(foreground, background) + 0.05) / (Math.min(foreground, background) + 0.05),
      };
    };
    return {
      rootDark: document.documentElement.classList.contains('dark'),
      search: searchElement ? contrast(searchElement) : null,
      filters: filterButton ? contrast(filterButton) : null,
    };
  }, { searchSelector: 'form input' });

  f3('Dark appearance is active on an F3 surface', metrics.rootDark === true, JSON.stringify(metrics), 'P1');
  f3('Dark appearance preserves readable primary search contrast', metrics.search?.ratio >= 4.5, JSON.stringify(metrics.search), 'P1');
  f3('Dark appearance preserves readable Filters control contrast', metrics.filters?.ratio >= 4.5, JSON.stringify(metrics.filters), 'P1');
  f3('Dark appearance leaves F3 primary search visible', await search.isVisible(), 'search hidden');
  f3('Dark appearance leaves F3 Filters control visible', await button.isVisible(), 'Filters hidden');
  await verifyNoHorizontalOverflow(page, 'F3 dark appearance');
  await verifyNoRuntimeErrorsSince('F3 dark appearance', errorStart);
  await chooseAppearance(page, 'Light', 'light', 'light');
}

async function main() {
  await loadAcceptedBaselines();

  const browser = await chromium.launch({ headless: true });
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    activePage = page;
    monitor(page);

    await loginAndEnrollDepartmentHead(page);
    await chooseAppearance(page, 'Light', 'light', 'light');

    for (const target of TARGETS) {
      await exerciseDesktop(page, target);
      await exerciseMobile(page, target);
      report.targets.push({ key: target.key, route: target.route, result: 'pass' });
    }

    await verifyDarkContrastRegression(page);

    f3('F3 target matrix completed 5/5', report.targets.length === EXPECTED_F3_TARGETS, `targets=${report.targets.length}`, 'P0');
    f3('F3 screenshot set completed exactly 7/7', report.screenshots.length === EXPECTED_F3_SCREENSHOTS, `screenshots=${report.screenshots.length}`, 'P0');

    const uniqueNames = report.screenshots.map((path) => path.split('/').at(-1));
    const expectedNames = [
      'my-work-desktop-light.png',
      'my-work-mobile-filters-open.png',
      'correspondence-desktop-active-filters.png',
      'records-mobile-filters-open.png',
      'travel-orders-desktop-light.png',
      'reports-desktop-light.png',
      'reports-mobile-filters-open.png',
    ];
    f3('F3 screenshot filenames match the locked evidence set', JSON.stringify(uniqueNames) === JSON.stringify(expectedNames), `actual=${JSON.stringify(uniqueNames)}`, 'P0');

    report.completed = true;
    report.failure = null;
    await writeReport();

    const failures = report.checks.filter((row) => row.ok === false).length;
    console.log(
      `F3_BROWSER_QA_PASS historical=${report.baseline.historicalChecks} f1=${report.baseline.f1Checks} f2=${report.baseline.f2Checks} f3=${report.checks.length} accounts=${report.baseline.accounts} f1_screenshots=${report.baseline.f1Screenshots} f2_screenshots=${report.baseline.f2Screenshots} f3_targets=${report.targets.length} f3_screenshots=${report.screenshots.length} failures=${failures}`,
    );

    await context.close();
  } finally {
    await browser.close();
  }
}

main().catch(async (error) => {
  report.completed = false;
  report.failure = {
    stage: clean(currentStage),
    path: clean(safePath(activePage?.url?.() || currentUrl)),
    error: clean(error?.message || error),
  };
  diagnostic('fatal', error?.stack || error?.message || String(error));
  await writeReport().catch(() => {});
  console.error(`F3_BROWSER_QA_FAIL stage=${clean(currentStage)} path=${clean(safePath(activePage?.url?.() || currentUrl))} error=${clean(error?.message || error)}`);
  process.exitCode = 1;
});
