# W08 Screenshot Manifest

Logical QA session: `2026-09-17_105721_PHT`

Source run: `Forge W08 Quality Assurance #4` / `35189429744`

QA execution head: `5bbfd48611b3f65e279048faa0fa1d246ba89789`

Total screenshots in final evidence ZIP: `11`

## W08 screenshots — 7

- `screenshots/w08/w01-showcase-shell-390-light.png` — W01 mobile shell controls/footer/workspace switching, 390x844 light.
- `screenshots/w08/w05-utilities-drawer-1280-dark.png` — W05 compact utility drawer, 1280x800 dark.
- `screenshots/w08/w05-utilities-rail-1600-dark.png` — W05 persistent utility rail after >=1536 transition, 1600x900 dark.
- `screenshots/w08/w07-error-403-390-light.png` — normalized W07 controlled 403 surface, 390x844 light.
- `screenshots/w08/matrix-municipal-executive-768x1024-light.png` — representative Municipal Executive accessibility mechanics, 768x1024 light.
- `screenshots/w08/matrix-human-resources-390x844-dark.png` — filename says dark, but the harness did not actually switch mobile appearance; preserve as historical evidence only, NOT valid dark-theme proof.
- `screenshots/w08/matrix-legislative-office-360x800-light.png` — narrow-phone Legislative reflow/accessibility mechanics, 360x800 light.

## H1 failure screenshots — 4

- `screenshots/h1-failures/correspondence-register.png` — actual visible action `Register correspondence`; harness waited for exact `Register Correspondence`. HARNESS DEFECT.
- `screenshots/h1-failures/correspondence-classify.png` — dependency failure after registration harness miss.
- `screenshots/h1-failures/correspondence-route-double-click.png` — dependency failure after registration harness miss.
- `screenshots/h1-failures/correspondence-begin-action.png` — dependency failure after registration harness miss.

## W06 product-defect evidence

The W06 Messages unnamed `<select>` was caught by the browser semantics assertion before the harness screenshot call. The blocking product defect is therefore evidenced by the machine-readable W08 report plus immutable source inspection rather than by a dedicated screenshot. Browser report evidence: `unlabeled: ["SELECT:"]`.
