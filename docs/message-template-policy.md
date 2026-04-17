# Message Template Migration Policy (Stabilization)

## 1) Placeholder convention (official)

- **Canonical placeholder format:** `{var_name}`
- `var_name` should be `snake_case`.
- Temporary backward compatibility for `{{var_name}}` is supported by `UiMessageRenderer`, but **new templates must use `{var_name}`**.

## 2) Message-level key naming convention (official)

- Canonical namespace for full-message templates:
  - `<domain>.<flow>.messages.<message_name>`
- Example:
  - `admin.panel_settings.messages.wizard_preview`
  - `admin.panel_settings.messages.menu_overview`
  - `admin.panel_settings.messages.delete_confirm`

## 3) Legacy/canonical key policy (current scope)

- Canonical keys are under `admin.panel_settings.messages.*`.
- `admin.panel_settings.wizard.preview_message` is currently **legacy-compatible** and kept for safety.
- Existing non-message keys (labels/info/steps) remain for backward compatibility unless all callsites migrate safely.

### Safe cleanup rule

Only remove a legacy key when:
1. all known callsites have migrated,
2. no dynamic lookup depends on it,
3. one release cycle has passed without regressions (recommended operational guard).

## 4) Renderer behavior contract

- `UiMessageRenderer` escapes **placeholder values** by default (`htmlspecialchars`).
- Template body is trusted and is not escaped.
- `trustedHtmlVars` can bypass escaping for explicitly vetted values only.
- Missing placeholder values do not crash rendering; unresolved placeholders remain in output and are logged via `error_log`.

## 5) Migration priority guidance

### Good candidates

- Stable, full multi-line messages with fixed structure and limited placeholders.
- Flows with low branch complexity.

### Defer candidates

- Branch-heavy or state-sensitive messages where text shape changes significantly per branch.
- Messages with complex computed summaries unless golden-output checks are in place.
