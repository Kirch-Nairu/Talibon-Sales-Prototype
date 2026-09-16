import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const BASE = process.env.QA_BASE_URL || 'http://127.0.0.1:8000';
const OUT = 'storage/app/qa/v1-showcase-readiness-report.json';
const SHOTS = 'storage/app/qa/v1-showcase-screenshots';
const report = {
    generatedAt: new Date().toISOString(),
    completed: false,
    checks: [],
    matrices: [],
    screenshots: [],
    diagnostics: [],
    failure: null,
};
const runtime = [];
let stage = 'bootstrap';
let pageRef = null;

const safePath = (url) => {
    try { return new URL(url, BASE).pathname; } catch { return '[unavailable]'; }
};
const check = (name, ok, details = '') => {
    const row = { name, ok: Boolean(ok), details: String(details) };
    report.checks.push(row);
    if (!row.ok) throw new Error(`${name}: ${details}`);
};
const write = async () => {
    await fs.mkdir('storage/app/qa', { recursive: true });
    await fs.writeFile(OUT, JSON.stringify(report, null, 2));
};
const checkpoint = (value, page = pageRef) => {
    stage = value;
    if (page) pageRef = page;
};
function monitor(page) {
    page.on('pageerror', (error) => runtime.push({ type: 'pageerror', path: safePath(page.url()), details: error.message }));
    page.on('response', (response) => {
        if (response.status() >= 500) runtime.push({ type: 'server-5xx', path: safePath(response.url()), details: String(response.status()) });
    });
}
async function noOverflow(page, label) {
    const dimensions = await page.evaluate(() => ({
        viewport: innerWidth,
        root: document.documentElement.scrollWidth,
        body: document.body.scrollWidth,
    }));
    check(`${label}: no horizontal overflow`, dimensions.root <= dimensions.viewport + 1 && dimensions.body <= dimensions.viewport + 1, JSON.stringify(dimensions));
}
async function onePrimaryHeading(page, label) {
    const count = await page.locator('h1:visible').count();
    check(`${label}: one primary heading`, count === 1, `h1=${count}`);
}
async function appearance(page, label) {
    const group = page.locator('[role="group"][aria-label="Appearance"]:visible').first();
    await group.waitFor({ state: 'visible' });
    await group.getByRole('button', { name: label, exact: true }).click();
    await page.waitForFunction((value) => document.documentElement.dataset.appearance === value, label.toLowerCase());
}
async function screenshot(page, name) {
    await fs.mkdir(SHOTS, { recursive: true });
    const path = `${SHOTS}/${name}`;
    await page.screenshot({ path, animations: 'disabled', fullPage: false });
    report.screenshots.push(path);
}
async function openRoute(page, route, label) {
    const response = await page.goto(`${BASE}${route}`);
    check(`${label}: route loads`, response?.status() === 200, `${route} status=${response?.status()}`);
    await onePrimaryHeading(page, label);
    await noOverflow(page, label);
}
async function openSelector(page) {
    await page.goto(`${BASE}/login`);
    await page.getByRole('button', { name: 'Enter Workspace', exact: true }).click();
    await page.getByRole('dialog').waitFor({ state: 'visible' });
}
async function enterWorkspace(page, role, office = null) {
    checkpoint(`enter ${role}`, page);
    await openSelector(page);
    await page.getByRole('button', { name: new RegExp(`^${role}`) }).click();
    if (office) {
        await page.getByRole('heading', { name: 'Choose office context', exact: true }).waitFor();
        await page.getByRole('button', { name: new RegExp(`^${office}`) }).click();
    }
    await page.waitForURL((url) => url.pathname === '/dashboard');
    check(`${role}: enters dashboard without credential challenge`, safePath(page.url()) === '/dashboard', safePath(page.url()));
}
async function switchWorkspace(page) {
    await Promise.all([
        page.waitForURL((url) => url.pathname === '/login'),
        page.getByRole('button', { name: 'Switch Workspace', exact: true }).click(),
    ]);
    check('Switch Workspace returns to gateway', safePath(page.url()) === '/login', safePath(page.url()));
}
async function assertNavigation(page, label, expected, forbidden = []) {
    for (const item of expected) {
        check(`${label}: ${item} visible`, await page.getByRole('link', { name: item, exact: true }).first().isVisible().catch(() => false));
    }
    for (const item of forbidden) {
        check(`${label}: ${item} absent`, await page.getByText(item, { exact: true }).count() === 0);
    }
}
async function invalidPersonaDenied(page) {
    await page.goto(`${BASE}/login`);
    const status = await page.evaluate(async () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('/showcase/session', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ persona: '../../foo' }),
        });
        return response.status;
    });
    check('invalid persona denied', status === 422, `status=${status}`);
}

async function gatewayMatrix(browser) {
    checkpoint('persona gateway');
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    pageRef = page;
    monitor(page);
    await page.goto(`${BASE}/login`);
    await onePrimaryHeading(page, 'Gateway');
    check('gateway has no email input', await page.locator('input[type="email"]').count() === 0);
    check('gateway has no password input', await page.locator('input[type="password"]').count() === 0);
    const body = (await page.locator('body').innerText()).toLowerCase();
    for (const forbidden of ['mfa', 'continue with google', 'activate employee account', 'prototype environment', 'secure municipal employee access']) {
        check(`gateway omits ${forbidden}`, !body.includes(forbidden));
    }
    await openSelector(page);
    check('persona selector opens', await page.getByRole('heading', { name: 'Choose your workspace', exact: true }).isVisible());
    for (const role of ['Municipal Executive', 'Department Head', 'Employee', 'Human Resources', 'Legislative Office', 'System Administration']) {
        check(`persona role visible: ${role}`, await page.getByRole('button', { name: new RegExp(`^${role}`) }).isVisible());
    }
    await page.keyboard.press('Escape');
    check('Escape closes persona selector', await page.getByRole('dialog').count() === 0);
    await invalidPersonaDenied(page);
    report.matrices.push({ key: 'gateway', result: 'pass' });
    await context.close();
}

async function executiveMatrix(browser) {
    checkpoint('executive matrix');
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    pageRef = page;
    monitor(page);
    await enterWorkspace(page, 'Municipal Executive');
    await assertNavigation(page, 'Executive', [
        'Home', 'Executive Departments', 'Employee Directory', 'Legislative', 'Local Special Bodies',
        'Development Plans', 'PPAs', 'Project Monitoring', 'Calendar', 'Meetings', 'Messages', 'Announcements',
    ], ['Audit & Security']);
    for (const [route, label] of [
        ['/dashboard', 'Executive Home'], ['/departments', 'Executive Departments'], ['/employees', 'Employee Directory'],
        ['/legislation', 'Legislative'], ['/local-special-bodies', 'Local Special Bodies'], ['/development-plans', 'Development Plans'],
        ['/ppas', 'PPAs'], ['/operations', 'Project Monitoring'], ['/calendar', 'Calendar'], ['/meetings', 'Meetings'],
        ['/messages', 'Messages'], ['/announcements', 'Announcements'],
    ]) await openRoute(page, route, label);
    await switchWorkspace(page);
    report.matrices.push({ key: 'executive', result: 'pass' });
    await context.close();
}

async function departmentHeadMatrix(browser) {
    checkpoint('department head matrix');
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    pageRef = page;
    monitor(page);
    await enterWorkspace(page, 'Department Head', 'Municipal Engineering Office');
    await assertNavigation(page, 'Department Head', [
        'Home', 'My Work', 'Correspondence', 'Records', 'Memoranda', 'Calendar', 'Meetings', 'Messages',
        'Executive Departments', 'Employee Directory', 'Development Plans', 'PPAs', 'Project Monitoring',
    ], ['Audit & Security', 'System Administration']);
    for (const [route, label] of [
        ['/dashboard', 'Department Head Home'], ['/transactions', 'My Work'], ['/correspondence', 'Correspondence'],
        ['/records', 'Records'], ['/memoranda', 'Memoranda'], ['/calendar', 'Calendar'], ['/meetings', 'Meetings'],
        ['/messages', 'Messages'], ['/departments', 'Departments'], ['/employees', 'Directory'],
        ['/development-plans', 'Plans'], ['/ppas', 'PPAs'], ['/operations', 'Project Monitoring'],
    ]) await openRoute(page, route, label);
    report.matrices.push({ key: 'department-head', result: 'pass' });
    await context.close();
}

async function employeeMatrix(browser) {
    checkpoint('employee matrix');
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    pageRef = page;
    monitor(page);
    await enterWorkspace(page, 'Employee');
    await assertNavigation(page, 'Employee', ['Home', 'My Work', 'Records', 'Announcements', 'Calendar', 'Messages'], ['System Administration', 'Audit & Security']);
    for (const [route, label] of [['/dashboard', 'Employee Home'], ['/transactions', 'Employee My Work'], ['/records', 'Employee Records'], ['/announcements', 'Employee Announcements'], ['/calendar', 'Employee Calendar'], ['/messages', 'Employee Messages']]) {
        await openRoute(page, route, label);
    }
    report.matrices.push({ key: 'employee', result: 'pass' });
    await context.close();
}

async function administrationMatrix(browser) {
    checkpoint('system administration matrix');
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    pageRef = page;
    monitor(page);
    await enterWorkspace(page, 'System Administration');
    await assertNavigation(page, 'System Administration', ['Home', 'System Administration', 'Municipal Systems'], ['Audit & Security']);
    await openRoute(page, '/admin', 'System Administration');
    await switchWorkspace(page);
    report.matrices.push({ key: 'system-administration', result: 'pass' });
    await context.close();
}

async function responsiveMatrix(browser) {
    checkpoint('responsive matrix');
    const contexts = [
        { width: 1440, height: 900, appearance: 'Light', suffix: 'desktop-light' },
        { width: 1440, height: 900, appearance: 'Dark', suffix: 'desktop-dark' },
        { width: 390, height: 844, appearance: 'Light', suffix: 'mobile-light' },
        { width: 390, height: 844, appearance: 'Dark', suffix: 'mobile-dark' },
    ];

    for (const target of contexts) {
        const context = await browser.newContext({ viewport: { width: target.width, height: target.height } });
        const page = await context.newPage();
        pageRef = page;
        monitor(page);
        await page.goto(`${BASE}/login`);
        await appearance(page, target.appearance);
        await noOverflow(page, `Entry ${target.suffix}`);
        await openSelector(page);
        await noOverflow(page, `Selector ${target.suffix}`);
        await screenshot(page, `entry-selector-${target.suffix}.png`);
        await page.keyboard.press('Escape');
        await enterWorkspace(page, 'System Administration');
        await appearance(page, target.appearance);
        for (const [route, label] of [
            ['/dashboard', 'Dashboard'], ['/operations', 'Project Monitoring'], ['/development-plans', 'Development Plans'],
            ['/calendar', 'Calendar'], ['/messages', 'Messages'], ['/departments', 'Departments'], ['/admin', 'System Administration'],
        ]) await openRoute(page, route, `${label} ${target.suffix}`);
        await screenshot(page, `system-administration-${target.suffix}.png`);
        await context.close();
    }
    report.matrices.push({ key: 'responsive', result: 'pass' });
}

await write();
let browser;
try {
    browser = await chromium.launch({ headless: true });
    await gatewayMatrix(browser);
    await executiveMatrix(browser);
    await departmentHeadMatrix(browser);
    await employeeMatrix(browser);
    await administrationMatrix(browser);
    await responsiveMatrix(browser);
    checkpoint('finalize');
    report.diagnostics = runtime;
    check('no browser page errors or server 5xx', runtime.length === 0, JSON.stringify(runtime));
    check('all V1 showcase matrices completed', report.matrices.length === 6 && report.matrices.every((matrix) => matrix.result === 'pass'), JSON.stringify(report.matrices));
    report.completed = true;
    report.failure = null;
    await write();
    console.log(`V1_SHOWCASE_BROWSER_PASS checks=${report.checks.length} matrices=${report.matrices.length} screenshots=${report.screenshots.length}`);
} catch (error) {
    report.completed = false;
    report.failure = { stage, path: pageRef ? safePath(pageRef.url()) : null, error: error?.stack || String(error) };
    report.diagnostics = runtime;
    await write();
    console.error(`V1_SHOWCASE_BROWSER_FAIL stage=${stage} path=${report.failure.path || '[none]'} error=${error?.message || error}`);
    process.exitCode = 1;
} finally {
    if (browser) await browser.close();
}
