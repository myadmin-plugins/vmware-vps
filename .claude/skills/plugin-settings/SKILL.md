---
name: plugin-settings
description: Registers new admin settings or page requirements in src/Plugin.php via getSettings() and getRequirements(). Use when user says 'add setting', 'new config option', 'register page requirement', 'add requirement', or 'expose a loader path'. Do NOT use for event handler logic, hook registration, or menu links.
---
# Plugin Settings & Requirements

## Critical

- All methods in `src/Plugin.php` are `public static` — never add instance methods or state.
- `getSettings()` MUST call `$settings->setTarget('module')` before adding settings and `$settings->setTarget('global')` at the end — missing either breaks the settings scope.
- Setting keys use `snake_case` with the module prefix: `vps_<descriptor>_vmware_<attribute>` (e.g. `vps_slice_vmware_cost`, `outofstock_vmware`).
- Wrap all UI strings in `_(...)` for gettext i18n.
- Use tabs for indentation (enforced by `.scrutinizer.yml`).

## Instructions

### Adding a text setting

1. Open `src/Plugin.php` and locate `getSettings(GenericEvent $event)`.
2. After `$settings->setTarget('module');`, call:
   ```php
   $settings->add_text_setting(self::$module, _('Category Label'), 'vps_<key>_vmware_<attr>', _('Field Title'), _('Help text describing the value.'), $settings->get_setting('VPS_<KEY>_VMWARE_<ATTR>'));
   ```
   - Arg 1: `self::$module` (always)
   - Arg 2: translated category heading
   - Arg 3: `snake_case` storage key (used as the PHP constant name uppercased)
   - Arg 4: translated field label
   - Arg 5: translated help/description text
   - Arg 6: current value via `$settings->get_setting('UPPERCASE_KEY')`
3. Verify the setting key constant matches the uppercased storage key before proceeding.

### Adding a dropdown (Yes/No toggle)

1. In `getSettings()`, after any text settings, call:
   ```php
   $settings->add_dropdown_setting(self::$module, _('Category Label'), 'outofstock_<type>', _('Field Title'), _('Help text.'), $settings->get_setting('OUTOFSTOCK_<TYPE>'), ['0', '1'], ['No', 'Yes']);
   ```
   - Options array: `['0', '1']`
   - Labels array: `['No', 'Yes']`
   - For non-binary dropdowns, pass full `$optionsArray` and `$labelsArray`.
2. Confirm `setTarget('global')` remains the last call in the method.

### Adding a function requirement

1. Open `getRequirements(GenericEvent $event)` in `src/Plugin.php`.
2. To expose a PHP function lazy-loaded from a `.inc.php` file:
   ```php
   $loader->add_requirement('function_name', '/../vendor/detain/myadmin-vmware-vps/src/file.inc.php');
   ```
   - Arg 1: the exact PHP function name callers will use
   - Arg 2: path starting with `/../vendor/` relative to the webroot
3. Verify the target `.inc.php` file exists under `src/` before adding.

### Adding a page requirement

1. In `getRequirements()`, call:
   ```php
   $loader->add_page_requirement('page_slug', '/../vendor/detain/myadmin-vmware-vps/src/page_slug.php');
   ```
   - For pages outside this vendor package (e.g. core VPS addons): `'/vps/addons/page_slug.php'`
2. Verify the slug matches the filename (without `.php`) to prevent silent 404s.

### Registering the new hook (if adding a new handler)

Only needed when `getSettings` or `getRequirements` is not yet wired. Add to `getHooks()`:
```php
self::$module.'.requirements' => [__CLASS__, 'getRequirements'],
```
For VMware VPS this hook already exists — skip this step if present.

## Examples

**User says:** "Add a text setting for the VMware API endpoint URL and a toggle to disable new sales."

**Actions taken:**
1. In `getSettings()`, after `setTarget('module')`:
   ```php
   $settings->add_text_setting(self::$module, _('API'), 'vps_vmware_api_url', _('VMWare API Endpoint'), _('Base URL for the VMWare API.'), $settings->get_setting('VPS_VMWARE_API_URL'));
   $settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_vmware_new', _('Disable New VMWare Sales'), _('Prevent new VPS orders of this type.'), $settings->get_setting('OUTOFSTOCK_VMWARE_NEW'), ['0', '1'], ['No', 'Yes']);
   ```
2. Confirm `setTarget('global')` closes the method.

**Result:** Admin panel shows a URL text field and a Yes/No toggle under the VMware VPS module settings.

---

**User says:** "Register a new `get_vmware_snapshots` function from `src/vmware_snapshots.inc.php`."

**Actions taken in `getRequirements()`:**
```php
$loader->add_requirement('get_vmware_snapshots', '/../vendor/detain/myadmin-vmware-vps/src/vmware_snapshots.inc.php');
```

## Common Issues

- **Setting never appears in admin panel:** `setTarget('module')` is missing before the `add_*_setting()` call, or `setTarget('global')` is missing at the end — both must be present.
- **Constant undefined error (`VPS_MY_KEY not defined`):** The storage key passed to `get_setting()` must be the exact uppercased version of the `add_*_setting()` key arg. Mismatch causes a missing constant.
- **`add_requirement` silently does nothing:** The path must start with `/../vendor/` (note leading slash-dot-dot). A path like `vendor/...` without the leading `/../` will not resolve correctly from the webroot.
- **PHPUnit test fails with `Call to undefined method ... add_text_setting`:** The stub in `tests/PluginTest.php` `testGetSettingsConfiguresSettingsObject` must declare all methods called in `getSettings()`. Add any new `add_*_setting` variant to the anonymous class stub.
- **Tabs vs spaces error from scrutinizer/CI:** `.scrutinizer.yml` enforces tabs. Run `make php-cs-fixer` if the linter rejects the file.