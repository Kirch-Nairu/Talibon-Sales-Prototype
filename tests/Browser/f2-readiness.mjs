import { chromium } from 'playwright';
import crypto from 'node:crypto';
import fs from 'node:fs/promises';

const BASE = process.env.QA_BASE_URL || 'http://127.0.0.1:8000';
const PASSWORD = process.env.QA_DEMO_PASSWORD;
if (!PASSWORD) throw new Error('QA_DEMO_PASSWORD is required');

const F1_REPORT_PATH = 'storage/app/qa/demo-readiness-report.json';
const F2_SCREENSHOT_DIR = 'storage/app/qa/f2-screenshots';
const F2_REPORT_PATH = 'storage/app/qa/f2-readiness-report.json';
const EXPECTED_HISTORICAL = 174;
const EXPECTED_F1 = 133;
const EXPECTED_ACCOUNTS = 7;
const EXPECTED_F1_SCREENSHOTS = 7;

const TARGETS = [
  { key: 'calendar', route: '/calendar', title: 'Events, deadlines & schedules', widthClass: 'max-w-6xl' },
  { key: 'departments', route: '/departments', title: 'Municipal Offices', widthClass: 'max-w-7xl' },
  { key: 'employees', route: '/employees', title: 'Employee Directory', widthClass: 'max-w-7xl' },
  { key: 'audit', route: '/audit', title: 'Audit & Security', widthClass: 'max-w-7xl' },
];

const EXPECTED_ADMIN_NAV = [
  { label: 'Home', items: [{ label: 'System Overview', href: '/dashboard' }] },
  { label: 'Platform', items: [{ label: 'Accounts & Access', href: '/admin' }] },
  { label: 'Control', items: [{ label: 'Audit & Security', href: '/audit' }] },
];

const report = {
  generatedAt: new Date().toISOString(),
  completed: false,
  checks: [],
  screenshots: [],
  diagnostics: [],
  baseline: null,
  notes: [
    'F2 QA is additive and separate from the accepted historical + F1 browser matrix.',
    'The F1 browser harness runs first and remains byte-for-byte unchanged.',
    'A fresh synthetic seed is applied before this F2-only presentation run.',
    'The runtime demo password and MFA enrollment secret are masked and never written to evidence.',
  ],
};

const sensitiveValues = new Set([PASSWORD]);
let currentStage = 'bootstrap';
let currentUrl = BASE;
let activePage = null;

function rememberSensitive(value) {
  if (typeof value === 'string' && value.length > 0) sensitiveValues.add(value);
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
function checkpoint(stage, page = activePage) {
  currentStage = stage;
  if (page) currentUrl = page.url();
}
function f2(name, ok, details = '', severity = 'P1') {
  const row = { name: `F2: ${clean(name)}`, ok: Boolean(ok), details: clean(details), severity };
  report.checks.push(row);
  if (!row.ok) {
    diagnostic('check-failure', `${row.name}: ${row.details}`);
    throw new Error(`${row.name}: ${row.details}`);
  }
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

async function loadAcceptedBrowserBaseline() {
  const accepted = JSON.parse(await fs.readFile(F1_REPORT_PATH, 'utf8'));
  const f1Checks = accepted.checks.filter((row) => row.name.startsWith('F1:')).length;
  const historicalChecks = accepted.checks.length - f1Checks;
  const accounts = accepted.accounts.length;
  const screenshots = accepted.screenshots.length;
  report.baseline = { historicalChecks, f1Checks, accounts, screenshots };

  f2('accepted historical Browser matrix remains exactly 174 checks', historicalChecks === EXPECTED_HISTORICAL, `historical=${historicalChecks}`, 'P0');
  f2('accepted F1 Browser matrix remains exactly 133 checks', f1Checks === EXPECTED_F1, `f1=${f1Checks}`, 'P0');
  f2('accepted representative account matrix remains 7/7', accounts === EXPECTED_ACCOUNTS, `accounts=${accounts}`, 'P0');
  f2('accepted F1 screenshot set remains 7/7', screenshots === EXPECTED_F1_SCREENSHOTS, `screenshots=${screenshots}`, 'P0');
}

function monitor(page) {
  const errors = [];
  page.on('pageerror', (error) => {
    errors.push(`pageerror ${error.message}`);
    diagnostic('pageerror', error.message);
  });
  page.on('console', (message) => {
    if (message.type() === 'error') diagnostic('console.error', message.text());
  });
  page.on('response', (response) => {
    if (response.status() >= 500) {
      const message = `${response.status()} ${safePath(response.url())}`;
      errors.push(message);
      diagnostic('server-5xx', message);
    }
  });
  page.on('requestfailed', (request) => {
    diagnostic('requestfailed', `${request.resourceType()} ${safePath(request.url())} ${request.failure()?.errorText || 'request failed'}`);
  });
  return () => f2('F2 target pages produce no page errors or server 5xx responses', errors.length === 0, errors.join(' | '), 'P1');
}

async function loginAndEnrollAdmin(page) {
  checkpoint('F2 admin authentication', page);
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.getByLabel('Email').fill('admin@talibon.demo');
  await page.getByLabel('Password').fill(PASSWORD);
  await Promise.all([
    page.waitForURL((url) => ['/dashboard', '/security/mfa/enroll', '/security/mfa/challenge'].includes(url.pathname)),
    page.getByRole('button', { name: 'Sign In' }).click(),
  ]);
  f2('fresh F2 admin login reaches MFA enrollment', safePath(page.url()) === '/security/mfa/enroll', `path=${safePath(page.url())}`, 'P0');

  const secret = (await page.locator('code').first().innerText()).trim();
  rememberSensitive(secret);
  f2('F2 privileged session uses normal MFA enrollment path', secret.length >= 16, 'MFA secret not rendered in enrollment flow', 'P0');
  await page.getByLabel('Six-digit verification code').fill(totp(secret));
  await Promise.all([
    page.waitForURL((url) => url.pathname === '/security/mfa/recovery-codes'),
    page.getByRole('button', { name: /Confirm MFA enrollment/i }).click(),
  ]);
  await Promise.all([
    page.waitForURL((url) => url.pathname === '/dashboard'),
    page.getByRole('link', { name: /Continue to portal/i }).click(),
  ]);
  f2('F2 privileged session reaches dashboard after MFA', safePath(page.url()) === '/dashboard', `path=${safePath(page.url())}`, 'P0');
}

async function primaryNavSnapshot(page) {
  const nav = page.locator('nav[aria-label="Primary navigation"]:visible').first();
  if (await nav.count() === 0) return [];
  return nav.locator('section[aria-label]').evaluateAll((sections) => sections.map((section) => ({
    label: section.getAttribute('aria-label'),
    items: Array.from(section.querySelectorAll('a')).map((link) => ({
      label: link.querySelector('span')?.textContent?.trim() || link.textContent?.trim() || '',
      href: link.getAttribute('href'),
    })),
  })));
}

async function verifyF1ShellAuthority(page) {
  checkpoint('F2 shell regression guard', page);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded' });

  const nav = await primaryNavSnapshot(page);
  f2('F1 grouped System Admin navigation remains unchanged', JSON.stringify(nav) === JSON.stringify(EXPECTED_ADMIN_NAV), `actual=${JSON.stringify(nav)}`, 'P0');

  const shell = page.locator('aside:visible').first();
  const shellMetrics = await shell.evaluate((element) => {
    const style = getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    const surface = element.firstElementChild ? getComputedStyle(element.firstElementChild).backgroundColor : null;
    return { width: rect.width, position: style.position, surface };
  });
  f2(
    'F1 municipal sidebar remains 260px with the navy institutional surface',
    Math.abs(shellMetrics.width - 260) <= 1 && shellMetrics.surface === 'rgb(11, 40, 82)',
    JSON.stringify(shellMetrics),
    'P0',
  );

  const utilityHeader = page.locator('main > header').first();
  const headerMetrics = await utilityHeader.evaluate((element) => {
    const style = getComputedStyle(element);
    return { height: element.getBoundingClientRect().height, position: style.position, top: style.top };
  });
  f2(
    'F1 compact sticky utility header remains unchanged',
    Math.abs(headerMetrics.height - 64) <= 1 && headerMetrics.position === 'sticky' && headerMetrics.top === '0px',
    JSON.stringify(headerMetrics),
    'P1',
  );
}

async function chooseAppearance(page, label, expectedPreference, expectedResolved) {
  const group = page.locator('[role="group"][aria-label="Appearance"]:visible').first();
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

async function pageFoundationSnapshot(page, target) {
  const heading = page.getByRole('heading', { level: 1, name: target.title, exact: true });
  await heading.waitFor({ state: 'visible' });
  return heading.evaluate((h1) => {
    const header = h1.closest('header');
    const frame = header?.parentElement;
    const wrapper = header?.firstElementChild;
    const eyebrow = h1.previousElementSibling;
    const description = h1.nextElementSibling;
    return {
      headerTag: header?.tagName || null,
      headerClass: header?.getAttribute('class') || '',
      frameClass: frame?.getAttribute('class') || '',
      wrapperFlexDirection: wrapper ? getComputedStyle(wrapper).flexDirection : null,
      eyebrowText: eyebrow?.textContent?.trim() || '',
      descriptionText: description?.textContent?.trim() || '',
    };
  });
}

async function verifyTargetLight(page, target) {
  checkpoint(`F2 ${target.key} light foundation`, page);
  await page.setViewportSize({ width: 1440, height: 900 });
  const response = await page.goto(`${BASE}${target.route}`, { waitUntil: 'domcontentloaded' });
  currentUrl = page.url();
  f2(`${target.key}: authorized page loads`, response?.status() === 200, `status=${response?.status()} path=${safePath(page.url())}`, 'P1');

  const snapshot = await pageFoundationSnapshot(page, target);
  f2(
    `${target.key}: shared PageHeader grammar is present`,
    snapshot.headerTag === 'HEADER'
      && snapshot.headerClass.includes('rounded-2xl')
      && snapshot.headerClass.includes('border-slate-200')
      && snapshot.headerClass.includes('dark:bg-[#142236]')
      && snapshot.eyebrowText.length > 0
      && snapshot.descriptionText.length > 0,
    JSON.stringify(snapshot),
    'P1',
  );
  f2(
    `${target.key}: shared PageFrame grammar is present`,
    snapshot.frameClass.includes('mx-auto')
      && snapshot.frameClass.includes('w-full')
      && snapshot.frameClass.includes('min-w-0')
      && snapshot.frameClass.includes(target.widthClass),
    snapshot.frameClass,
    'P1',
  );
}

async function verifyMobileOverflow(page, target) {
  checkpoint(`F2 ${target.key} mobile overflow`, page);
  await page.setViewportSize({ width: 390, height: 844 });
  const response = await page.goto(`${BASE}${target.route}`, { waitUntil: 'domcontentloaded' });
  const metrics = await page.evaluate(() => ({
    viewport: window.innerWidth,
    root: document.documentElement.scrollWidth,
    body: document.body?.scrollWidth || 0,
  }));
  f2(
    `${target.key}: 390x844 has no horizontal overflow`,
    response?.status() === 200 && metrics.root <= metrics.viewport + 1 && metrics.body <= metrics.viewport + 1,
    `status=${response?.status()} metrics=${JSON.stringify(metrics)}`,
    'P2',
  );
}

async function verifyEmployeeHeaderStacking(page) {
  checkpoint('F2 employee header responsive stacking', page);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(`${BASE}/employees`, { waitUntil: 'domcontentloaded' });
  const heading = page.getByRole('heading', { level: 1, name: 'Employee Directory', exact: true });
  const desktop = await heading.evaluate((h1) => {
    const header = h1.closest('header');
    const wrapper = header?.firstElementChild;
    const aside = wrapper?.lastElementChild;
    const h = h1.getBoundingClientRect();
    const a = aside?.getBoundingClientRect();
    return {
      flexDirection: wrapper ? getComputedStyle(wrapper).flexDirection : null,
      titleLeft: h.left,
      asideLeft: a?.left ?? null,
      asideWidth: a?.width ?? null,
    };
  });
  f2(
    'employee directory: desktop title and aside use horizontal hierarchy',
    desktop.flexDirection === 'row' && desktop.asideLeft !== null && desktop.asideLeft > desktop.titleLeft && (desktop.asideWidth ?? 0) > 0,
    JSON.stringify(desktop),
    'P2',
  );

  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(`${BASE}/employees`, { waitUntil: 'domcontentloaded' });
  const mobile = await heading.evaluate((h1) => {
    const header = h1.closest('header');
    const wrapper = header?.firstElementChild;
    const aside = wrapper?.lastElementChild;
    const h = h1.getBoundingClientRect();
    const a = aside?.getBoundingClientRect();
    return {
      flexDirection: wrapper ? getComputedStyle(wrapper).flexDirection : null,
      titleBottom: h.bottom,
      asideTop: a?.top ?? null,
      asideWidth: a?.width ?? null,
      headerWidth: header?.getBoundingClientRect().width ?? null,
    };
  });
  f2(
    'employee directory: mobile title and aside stack vertically',
    mobile.flexDirection === 'column'
      && mobile.asideTop !== null
      && mobile.asideTop > mobile.titleBottom
      && (mobile.asideWidth ?? 0) > 0,
    JSON.stringify(mobile),
    'P2',
  );
}

async function darkHeaderContrast(page, target) {
  checkpoint(`F2 ${target.key} dark header contrast`, page);
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(`${BASE}${target.route}`, { waitUntil: 'domcontentloaded' });
  const heading = page.getByRole('heading', { level: 1, name: target.title, exact: true });
  const metrics = await heading.evaluate((h1) => {
    const toRgb = (value) => {
      if (!value || !CSS.supports('color', value)) return null;
      const canvas = document.createElement('canvas');
      canvas.width = 1;
      canvas.height = 1;
      const context = canvas.getContext('2d', { willReadFrequently: true });
      if (!context) return null;
      context.clearRect(0, 0, 1, 1);
      context.fillStyle = value;
      context.fillRect(0, 0, 1, 1);
      const [red, green, blue, alpha] = context.getImageData(0, 0, 1, 1).data;
      return alpha === 255 ? [red, green, blue] : null;
    };
    const luminance = (rgb) => {
      const channels = rgb.map((value) => {
        const normalized = value / 255;
        return normalized <= 0.03928 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
      });
      return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
    };
    const ratio = (a, b) => {
      const first = luminance(a);
      const second = luminance(b);
      const lighter = Math.max(first, second);
      const darker = Math.min(first, second);
      return (lighter + 0.05) / (darker + 0.05);
    };

    const header = h1.closest('header');
    const eyebrow = h1.previousElementSibling;
    const description = h1.nextElementSibling;
    const background = header ? getComputedStyle(header).backgroundColor : null;
    const titleColor = getComputedStyle(h1).color;
    const eyebrowColor = eyebrow ? getComputedStyle(eyebrow).color : null;
    const descriptionColor = description ? getComputedStyle(description).color : null;
    const bg = toRgb(background);
    const title = toRgb(titleColor);
    const eye = toRgb(eyebrowColor);
    const desc = toRgb(descriptionColor);
    return {
      background,
      titleColor,
      eyebrowColor,
      descriptionColor,
      titleContrast: bg && title ? ratio(bg, title) : 0,
      eyebrowContrast: bg && eye ? ratio(bg, eye) : 0,
      descriptionContrast: bg && desc ? ratio(bg, desc) : 0,
    };
  });

  f2(
    `${target.key}: dark PageHeader title/context/copy remain readable`,
    metrics.background === 'rgb(20, 34, 54)'
      && metrics.titleContrast >= 4.5
      && metrics.eyebrowContrast >= 4.5
      && metrics.descriptionContrast >= 4.5,
    JSON.stringify(metrics),
    'P1',
  );
}

async function saveScreenshot(page, fileName) {
  await fs.mkdir(F2_SCREENSHOT_DIR, { recursive: true });
  const path = `${F2_SCREENSHOT_DIR}/${fileName}`;
  await page.screenshot({ path, fullPage: false, animations: 'disabled' });
  report.screenshots.push(path);
  f2(`sanitized F2 presentation screenshot captured: ${fileName}`, true, path, 'P2');
}

async function captureLightScreenshots(page) {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(`${BASE}/calendar`, { waitUntil: 'domcontentloaded' });
  await saveScreenshot(page, 'f2-calendar-desktop-light.png');

  await page.goto(`${BASE}/departments`, { waitUntil: 'domcontentloaded' });
  await saveScreenshot(page, 'f2-departments-desktop-light.png');

  await page.goto(`${BASE}/employees`, { waitUntil: 'domcontentloaded' });
  await saveScreenshot(page, 'f2-employee-directory-desktop-light.png');

  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(`${BASE}/employees`, { waitUntil: 'domcontentloaded' });
  await saveScreenshot(page, 'f2-employee-directory-mobile-light.png');
}

async function main() {
  await loadAcceptedBrowserBaseline();
  const browser = await chromium.launch({ headless: true });
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    activePage = page;
    const cleanRun = monitor(page);

    await loginAndEnrollAdmin(page);
    await verifyF1ShellAuthority(page);
    await chooseAppearance(page, 'Light', 'light', 'light');

    for (const target of TARGETS) {
      await verifyTargetLight(page, target);
      await verifyMobileOverflow(page, target);
    }

    await verifyEmployeeHeaderStacking(page);
    await captureLightScreenshots(page);

    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded' });
    await chooseAppearance(page, 'Dark', 'dark', 'dark');
    for (const target of TARGETS) await darkHeaderContrast(page, target);

    await page.goto(`${BASE}/audit`, { waitUntil: 'domcontentloaded' });
    await saveScreenshot(page, 'f2-audit-desktop-dark.png');
    cleanRun();

    f2('representative F2 screenshot set is complete', report.screenshots.length === 5, `screenshots=${report.screenshots.length}`, 'P1');
    checkpoint('complete', page);
    report.completed = true;
    report.completedAt = new Date().toISOString();

    await context.close();
  } finally {
    await browser.close().catch(() => {});
  }

  await fs.mkdir('storage/app/qa', { recursive: true });
  await fs.writeFile(F2_REPORT_PATH, reportJson());
  console.log(
    `F2_BROWSER_QA_PASS historical=${report.baseline.historicalChecks} f1=${report.baseline.f1Checks} f2=${report.checks.length} accounts=${report.baseline.accounts} f1_screenshots=${report.baseline.screenshots} f2_screenshots=${report.screenshots.length}`,
  );
}

main().catch(async (error) => {
  report.failure = {
    stage: clean(currentStage),
    currentPath: clean(safePath(activePage?.url?.() || currentUrl)),
    error: clean(error?.message || error),
  };
  report.checks.push({ name: 'F2: browser QA aborted', ok: false, details: report.failure.error, severity: 'P1' });
  try {
    await fs.mkdir('storage/app/qa', { recursive: true });
    await fs.writeFile(F2_REPORT_PATH, reportJson());
  } catch {}
  console.error(`F2_BROWSER_QA_FAIL stage=${report.failure.stage} path=${report.failure.currentPath} error=${report.failure.error}`);
  process.exitCode = 1;
});
