from __future__ import annotations

from hashlib import sha256
from pathlib import Path
import json

ROOT = Path.cwd()
OUT = ROOT / 'storage/app/qa/runtime-harness'
OUT.mkdir(parents=True, exist_ok=True)


def replace_between(source: str, start_marker: str, end_marker: str, replacement: str, label: str) -> str:
    start = source.find(start_marker)
    if start < 0:
        raise RuntimeError(f'{label}: start marker not found')
    end = source.find(end_marker, start)
    if end < 0:
        raise RuntimeError(f'{label}: end marker not found')
    if source.find(start_marker, start + len(start_marker)) >= 0:
        raise RuntimeError(f'{label}: start marker is not unique')
    return source[:start] + replacement.rstrip() + '\n\n' + source[end:]


def replace_once(source: str, old: str, new: str, label: str) -> str:
    count = source.count(old)
    if count != 1:
        raise RuntimeError(f'{label}: expected exactly one match, observed {count}')
    return source.replace(old, new, 1)


SHOWCASE_HELPER_H1 = r'''async function enterShowcaseSession(page, spec) {
  await page.getByRole('button', { name: 'Enter Workspace', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await dialog.waitFor({ state: 'visible', timeout: 5000 });
  await dialog.getByRole('heading', { name: 'Choose your workspace', exact: true }).waitFor({ state: 'visible', timeout: 5000 });

  if (spec.email === 'engineering@talibon.demo' || spec.email === 'budget@talibon.demo') {
    await dialog.getByRole('button').filter({ hasText: 'Department Head' }).first().click();
    await dialog.getByRole('heading', { name: 'Choose office context', exact: true }).waitFor({ state: 'visible', timeout: 5000 });
    const office = spec.email === 'engineering@talibon.demo'
      ? 'Municipal Engineering Office'
      : 'Municipal Budget Office';
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/dashboard', { timeout: 15000 }),
      dialog.getByRole('button').filter({ hasText: office }).first().click(),
    ]);
  } else {
    const labels = {
      'mayor@talibon.demo': 'Municipal Executive',
      'employee@talibon.demo': 'Employee',
      'hr@talibon.demo': 'Human Resources',
      'legislative@talibon.demo': 'Legislative Office',
      'admin@talibon.demo': 'System Administration',
    };
    const label = labels[spec.email];
    if (!label) throw new Error(`No showcase persona mapping for ${spec.email}`);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/dashboard', { timeout: 15000 }),
      dialog.getByRole('button').filter({ hasText: label }).first().click(),
    ]);
  }
  await appReady(page);
}

async function login(browser, spec) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 850 } });
  const page = await context.newPage();
  monitor(page, spec.label);

  const response = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
  if (response?.status() !== 200) throw new Error(`${spec.label} workspace gateway returned ${response?.status()}`);
  await appReady(page);
  await enterShowcaseSession(page, spec);
  if (new URL(page.url()).pathname !== '/dashboard') throw new Error(`${spec.label} did not reach the dashboard`);
  return { context, page };
}'''

SHOWCASE_HELPER_W08 = r'''async function enterShowcaseSession(page, spec) {
  await page.getByRole('button', { name: 'Enter Workspace', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await dialog.waitFor({ state: 'visible', timeout: 5000 });
  await dialog.getByRole('heading', { name: 'Choose your workspace', exact: true }).waitFor({ state: 'visible', timeout: 5000 });

  if (spec.email === 'engineering@talibon.demo' || spec.email === 'budget@talibon.demo') {
    await dialog.getByRole('button').filter({ hasText: 'Department Head' }).first().click();
    await dialog.getByRole('heading', { name: 'Choose office context', exact: true }).waitFor({ state: 'visible', timeout: 5000 });
    const office = spec.email === 'engineering@talibon.demo'
      ? 'Municipal Engineering Office'
      : 'Municipal Budget Office';
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/dashboard', { timeout: 15000 }),
      dialog.getByRole('button').filter({ hasText: office }).first().click(),
    ]);
  } else {
    const labels = {
      'mayor@talibon.demo': 'Municipal Executive',
      'employee@talibon.demo': 'Employee',
      'hr@talibon.demo': 'Human Resources',
      'legislative@talibon.demo': 'Legislative Office',
      'admin@talibon.demo': 'System Administration',
    };
    const label = labels[spec.email];
    if (!label) throw new Error(`No showcase persona mapping for ${spec.email}`);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/dashboard', { timeout: 15000 }),
      dialog.getByRole('button').filter({ hasText: label }).first().click(),
    ]);
  }
  await appReady(page);
}

async function login(browser, spec) {
  const context = await browser.newContext({ viewport: spec.viewport || { width: 1280, height: 800 } });
  const page = await context.newPage();
  monitor(page, spec.label);
  const response = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
  if (response?.status() !== 200) throw new Error(`${spec.label} workspace gateway returned ${response?.status()}`);
  await appReady(page);
  await enterShowcaseSession(page, spec);
  if (new URL(page.url()).pathname !== '/dashboard') throw new Error(`${spec.label} did not reach dashboard`);
  return { context, page };
}'''

h1_path = ROOT / 'tests/Browser/h1-mutation-readiness.mjs'
w08_path = ROOT / 'tests/Browser/w08-cross-product-readiness.mjs'
h1 = h1_path.read_text(encoding='utf-8')
w08 = w08_path.read_text(encoding='utf-8')

h1_fixed = replace_between(
    h1,
    'async function login(browser, spec) {',
    'function requestMatches(request, method, pathMatcher) {',
    SHOWCASE_HELPER_H1,
    'H1 showcase-login compatibility repair',
)
w08_fixed = replace_between(
    w08,
    'async function login(browser, spec) {',
    'function newScenario(name, meta = {}) {',
    SHOWCASE_HELPER_W08,
    'W08 showcase-login compatibility repair',
)

old_appearance = "    check(row, 'mobile shell exposes Appearance', await page.getByText('Appearance', { exact: true }).last().isVisible(), 'Appearance not visible');"
new_appearance = "    const mobileAppearance = page.locator('dialog[open] summary[title=\\\"Appearance\\\"]:visible').first();\n    check(row, 'mobile shell exposes Appearance', await mobileAppearance.isVisible().catch(() => false), 'visible mobile Appearance trigger not found');"
w08_fixed = replace_once(w08_fixed, old_appearance, new_appearance, 'W01 mobile Appearance selector repair')

outputs = {
    'h1-mutation-readiness.mjs': h1_fixed,
    'w08-cross-product-readiness.mjs': w08_fixed,
}
manifest = {
    'schemaVersion': 1,
    'purpose': 'Bounded W08 QA harness compatibility overlay. Product source is not modified.',
    'transformations': [
        'H1 login follows the accepted browser-visible Showcase workspace gateway instead of obsolete Email/Password controls.',
        'W08 persona login follows the accepted browser-visible Showcase workspace gateway.',
        'Showcase persona-card selection uses stable dialog scope and visible card text because PersonaCard accessible names include description/context text and the dialog heading changes on the department-head step.',
        'W01 mobile Appearance assertion scopes to the visible mobile navigation Appearance trigger rather than an ambiguous hidden details descendant.',
    ],
    'outputs': {},
}
for name, content in outputs.items():
    target = OUT / name
    target.write_text(content, encoding='utf-8')
    manifest['outputs'][name] = {
        'sha256': sha256(content.encode()).hexdigest(),
        'bytes': len(content.encode()),
    }

manifest_path = OUT / 'repair-manifest.json'
manifest_path.write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
print(json.dumps(manifest, indent=2))
