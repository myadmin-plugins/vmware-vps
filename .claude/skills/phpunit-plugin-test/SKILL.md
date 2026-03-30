---
name: phpunit-plugin-test
description: Writes PHPUnit 9.6 tests in tests/PluginTest.php for Detain\MyAdminVmware\Plugin. Use when user says 'add test', 'write unit test', 'test this method', or modifies Plugin.php logic. Covers getHooks() array structure, event handler type guards (VMWARE define check + stopPropagation), settings/requirements registration, and method signatures via ReflectionClass. Do NOT use for integration tests against live VMware APIs or SOAP calls.
---
# PHPUnit Plugin Test

## Critical

- All tests go in `tests/PluginTest.php`, namespace `Detain\MyAdminVmware\Tests`, class `PluginTest extends TestCase`
- File must begin with `declare(strict_types=1);`
- Never call live VMware SOAP methods — `getChangeIp` behavioral tests use source inspection via `ReflectionMethod`, not execution
- Every test that calls an event handler involving `$GLOBALS['tf']` must set up the mock in `setUp()` or inline before the call
- `get_service_define('VMWARE')` returns `7` per the stubs in `tests/bootstrap.php` — use `get_service_define()`, never hardcode `7`
- Run with: `phpunit` (config: `phpunit.xml.dist`, bootstrap: `tests/bootstrap.php`)

## Instructions

1. **Add required imports** at the top of `tests/PluginTest.php`:
   ```php
   use Detain\MyAdminVmware\Plugin;
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;
   use Symfony\Component\EventDispatcher\GenericEvent;
   ```
   Verify `vendor/symfony/event-dispatcher` exists before proceeding.

2. **Declare `$reflection` and `SERVICE_DEFINES`** as class members and initialize in `setUp()`:
   ```php
   private ReflectionClass $reflection;
   private const SERVICE_DEFINES = [
       'OPENVZ'=>1,'KVM_LINUX'=>2,'VMWARE'=>7, /* full list from bootstrap.php */
   ];
   protected function setUp(): void {
       parent::setUp();
       $this->reflection = new ReflectionClass(Plugin::class);
       $defines = self::SERVICE_DEFINES;
       $GLOBALS['tf'] = new class($defines) {
           public string $ima = 'client';
           private array $defines;
           public function __construct(array $d) { $this->defines = $d; }
           public function get_service_define(string $n): int { return $this->defines[$n] ?? 0; }
       };
   }
   ```
   This step sets up stubs needed by all subsequent tests.

3. **Test `getHooks()` structure** — assert return is array, keys are prefixed `vps.`, values are `[Plugin::class, 'methodName']` callables:
   ```php
   public function testGetHooksContainsSettingsHook(): void {
       $hooks = Plugin::getHooks();
       $this->assertArrayHasKey(Plugin::$module.'.settings', $hooks);
       $hook = $hooks[Plugin::$module.'.settings'];
       $this->assertSame([Plugin::class, 'getSettings'], $hook);
   }
   ```
   Verify each referenced method actually exists with `method_exists($class, $method)`.

4. **Test type-guard + stopPropagation** for handlers that call `get_service_define('VMWARE')`:
   - VMWARE type → `$event->isPropagationStopped()` must be `true`
   - Non-VMWARE type (e.g. `KVM_LINUX`) → propagation must NOT be stopped
   ```php
   $serviceClass = new class { public function getId(): int { return 42; } };
   $event = new GenericEvent($serviceClass, ['type' => get_service_define('VMWARE')]);
   Plugin::getActivate($event);
   $this->assertTrue($event->isPropagationStopped());
   ```

5. **Test `getDeactivate` side-effects** using an anonymous history mock attached to `$GLOBALS['tf']->history`:
   ```php
   $historyMock = new class {
       public array $calls = [];
       public function add(string $queue, int $id, string $action, string $extra, int $custid): void {
           $this->calls[] = compact('queue','id','action','extra','custid');
       }
   };
   $GLOBALS['tf'] = new class($historyMock, self::SERVICE_DEFINES) {
       public object $history;
       private array $defines;
       public function __construct(object $h, array $d) { $this->history=$h; $this->defines=$d; }
       public function get_service_define(string $n): int { return $this->defines[$n] ?? 0; }
   };
   ```
   Assert `$historyMock->calls[0]['action'] === 'delete'` and `queue === 'vpsqueue'`.

6. **Test `getSettings`** using an anonymous settings stub that records calls to `add_text_setting` and `add_dropdown_setting`:
   - Assert `setTarget('module')` called first, `setTarget('global')` called last
   - Assert `vps_slice_vmware_cost` appears in text setting keys
   - Assert `outofstock_vmware` dropdown has `options=['0','1']`, `labels=['No','Yes']`

7. **Test `getRequirements`** with an anonymous loader stub recording `add_requirement` and `add_page_requirement` calls:
   - Assert `crud_vmware_list` in page requirement names
   - Assert `activate_vmware` in requirement names

8. **Test method signatures via Reflection** for all event handlers (`getActivate`, `getDeactivate`, `getChangeIp`, `getMenu`, `getRequirements`, `getSettings`):
   - Must be `public static`
   - Must accept exactly 1 parameter typed `GenericEvent`
   Use a `@dataProvider` returning `['methodName' => ['methodName', 1]]`.

9. **Run tests** with `phpunit` (config: `phpunit.xml.dist`) and confirm zero failures before considering the work complete.

## Examples

**User says:** "Add a test for getDeactivate ignoring non-VMware events"

**Actions taken:**
1. Open `tests/PluginTest.php`
2. Add `testGetDeactivateIgnoresNonVmwareType()` after existing deactivate tests
3. Build inline `$historyMock` and `$GLOBALS['tf']` mock (Step 5 pattern)
4. Create `GenericEvent` with `type => get_service_define('KVM_LINUX')`
5. Call `Plugin::getDeactivate($event)`, assert `$historyMock->calls` is empty

**Result:**
```php
public function testGetDeactivateIgnoresNonVmwareType(): void {
    $historyMock = new class { public array $calls = [];
        public function add(string $q, int $id, string $a, string $e, int $c): void { $this->calls[]=compact('q','id','a','e','c'); }
    };
    $GLOBALS['tf'] = new class($historyMock, self::SERVICE_DEFINES) {
        public object $history; private array $defines;
        public function __construct(object $h, array $d){$this->history=$h;$this->defines=$d;}
        public function get_service_define(string $n):int{return $this->defines[$n]??0;}
    };
    $svc = new class{ public function getId():int{return 1;} public function getCustid():int{return 2;} };
    $event = new GenericEvent($svc, ['type' => get_service_define('KVM_LINUX')]);
    Plugin::getDeactivate($event);
    $this->assertCount(0, $historyMock->calls);
}
```

## Common Issues

- **`Call to undefined function get_service_define()`** — `tests/bootstrap.php` is not being loaded. Verify `phpunit.xml.dist` has `<bootstrap>tests/bootstrap.php</bootstrap>`. Run `phpunit --bootstrap tests/bootstrap.php` to confirm.
- **`Class 'Symfony\Component\EventDispatcher\GenericEvent' not found`** — run `composer install`; the package is `symfony/event-dispatcher`.
- **`$event->isPropagationStopped()` returns false unexpectedly** — the handler type check failed. Confirm `$GLOBALS['tf']` is set up before calling the handler; the bootstrap stub delegates to it.
- **Anonymous class `add()` method never called** — wrong `$GLOBALS['tf']` setup; ensure the mock is assigned *after* `setUp()` runs its default assignment (assign inline in the test method, not only in `setUp()`).
- **Reflection test fails: parameter type is `null`** — the Plugin method is missing a `GenericEvent` type hint. Add it to `src/Plugin.php`: `public static function getXxx(GenericEvent $event)`.
