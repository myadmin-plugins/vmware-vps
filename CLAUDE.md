# MyAdmin VMware VPS Plugin

PHP plugin providing VMware VPS provisioning, lifecycle management, and billing hooks for the MyAdmin platform.

## Commands

```bash
composer install          # install deps incl. phpunit/phpunit ^9.6
phpunit                   # run all tests (uses phpunit.xml.dist)
```

## Architecture

**Entry:** `src/Plugin.php` — single static class `Detain\MyAdminVmware\Plugin`
**Tests:** `tests/PluginTest.php` · bootstrap at `tests/bootstrap.php`
**Autoload:** PSR-4 `Detain\MyAdminVmware\` → `src/` · test namespace `Detain\MyAdminVmware\Tests\` → `tests/`
**CI/CD:** `.github/` contains workflows for automated testing and deployment pipelines · `.idea/` contains IDE configuration including inspectionProfiles, deployment.xml, and encodings.xml

**Plugin static properties:**
- `$module = 'vps'` · `$type = 'service'` · `$name` · `$description`

**Hook registration** — `getHooks()` returns event → handler map:
```php
public static function getHooks(): array {
    return [
        self::$module.'.settings'   => [__CLASS__, 'getSettings'],
        self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
    ];
}
```

**Event handler pattern** — all handlers receive `GenericEvent $event`:
```php
public static function getDeactivate(GenericEvent $event): void {
    if ($event['type'] == get_service_define('VMWARE')) {
        $serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', self::$name.' Deactivation',
            __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $event->stopPropagation();
    }
}
```

**Settings registration** — inside `getSettings(GenericEvent $event)`:
```php
$settings->add_text_setting(self::$module, _('Label'), 'vps_key', _('Title'), _('Help text'), $settings->get_setting('VPS_KEY'));
$settings->add_dropdown_setting(self::$module, _('Label'), 'key', _('Title'), _('Help'), $val, ['0','1'], ['No','Yes']);
```

**Requirements registration** — inside `getRequirements(GenericEvent $event)`:
```php
$loader->add_requirement('function_name', '/path/to/src/file.inc.php');
$loader->add_page_requirement('page_name', '/path/to/src/page.php');
```

**Logging:** `myadmin_log($module, $level, $message, __LINE__, __FILE__, $module, $id)`
**Type guard:** always check `$event['type'] == get_service_define('VMWARE')` before acting
**IP changes:** use `Detain\Vmware\Vmware` via `ext-soap`; check `$result['faultcode']` for errors

## Conventions

- All `Plugin` methods are `public static`; no instance state
- Namespace: `Detain\MyAdminVmware\` (matches `composer.json` autoload)
- Call `$event->stopPropagation()` after handling to prevent other handlers running
- Wrap UI strings in `_(...)` for gettext i18n
- Tabs for indentation (see `.scrutinizer.yml` coding style)
- PHP `>=7.4` required; `ext-soap` required
- Test stubs for `myadmin_log`, `get_service_define`, `get_module_settings`, `_` are in `tests/bootstrap.php`

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
