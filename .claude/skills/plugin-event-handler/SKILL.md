---
name: plugin-event-handler
description: Adds a new event hook to src/Plugin.php following the getHooks() registration and GenericEvent handler pattern. Ensures type guard with get_service_define('VMWARE'), myadmin_log() call, and stopPropagation(). Use when user says 'add hook', 'new event', 'handle event', or adds a handler method to Plugin.php. Do NOT use for modifying settings (getSettings), requirements (getRequirements), or menu links (getMenu).
---
# Plugin Event Handler

## Critical

- Every handler that acts on a specific service type **must** check `$event['type'] == get_service_define('VMWARE')` before doing anything — handlers fire for all VPS types.
- Call `$event->stopPropagation()` inside the type guard after handling — prevents other plugins from processing the same event.
- All `Plugin` methods **must** be `public static` — no instance state ever.
- Use tabs for indentation (`.scrutinizer.yml` enforces this).
- After adding a handler, register it in `getHooks()` — an orphaned method will never fire.

## Instructions

1. **Add the entry to `getHooks()`** in `src/Plugin.php`.
   The key is `self::$module . '.event_name'`; the value is `[__CLASS__, 'getHandlerName']`.
   ```php
   public static function getHooks()
   {
       return [
           self::$module.'.settings'      => [__CLASS__, 'getSettings'],
           self::$module.'.deactivate'    => [__CLASS__, 'getDeactivate'],
           self::$module.'.your_event'    => [__CLASS__, 'getYourEvent'],  // add here
       ];
   }
   ```
   Verify the key string matches the event name fired by the MyAdmin core before proceeding.

2. **Add the handler method** to `src/Plugin.php` following the exact signature below.
   The parameter must be type-hinted as `GenericEvent` (already imported at the top of the file).
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getYourEvent(GenericEvent $event)
   {
       if ($event['type'] == get_service_define('VMWARE')) {
           $serviceClass = $event->getSubject();
           myadmin_log(self::$module, 'info', self::$name.' Your Action',
               __LINE__, __FILE__, self::$module, $serviceClass->getId());
           // handler logic here
           $event->stopPropagation();
       }
   }
   ```
   Verify `get_service_define('VMWARE')` is the type guard (not a hardcoded int).

3. **If the handler needs the Vmware API client** (e.g. for IP changes, provisioning):
   ```php
   $vmware = new Vmware(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
   $result = $vmware->someMethod(...);
   if (isset($result['faultcode'])) {
       myadmin_log(self::$module, 'error',
           'Vmware someMethod() returned Fault '.$result['faultcode'].': '.$result['fault'],
           __LINE__, __FILE__, self::$module, $serviceClass->getId());
       $event['status'] = 'error';
       $event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
   } else {
       $event['status'] = 'ok';
       $event['status_text'] = 'Action completed.';
   }
   ```
   `Detain\Vmware\Vmware` is already imported via `use Detain\Vmware\Vmware;` at line 5.

4. **Add a PHPUnit test** in `tests/PluginTest.php`:
   - Add `'getYourEvent' => ['getYourEvent', 1]` to `eventHandlerMethodProvider()` — this auto-tests signature, visibility, and `GenericEvent` type hint.
   - Add a behavioral test verifying propagation stops for VMWARE type and does **not** stop for another type (e.g. `KVM_LINUX`):
   ```php
   public function testGetYourEventStopsPropagationForVmwareType(): void
   {
       $serviceClass = new class {
           public function getId(): int { return 1; }
       };
       $event = new GenericEvent($serviceClass, ['type' => get_service_define('VMWARE')]);
       Plugin::getYourEvent($event);
       $this->assertTrue($event->isPropagationStopped());
   }
   ```
   Run `vendor/bin/phpunit` and confirm green before considering the task done.

## Examples

**User says:** "Add a suspend event handler to the VMware plugin."

**Actions taken:**
1. Add `self::$module.'.suspend' => [__CLASS__, 'getSuspend']` to `getHooks()`.
2. Add method:
   ```php
   public static function getSuspend(GenericEvent $event)
   {
       if ($event['type'] == get_service_define('VMWARE')) {
           $serviceClass = $event->getSubject();
           myadmin_log(self::$module, 'info', self::$name.' Suspension',
               __LINE__, __FILE__, self::$module, $serviceClass->getId());
           $GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(),
               'suspend', '', $serviceClass->getCustid());
           $event->stopPropagation();
       }
   }
   ```
3. Add `'getSuspend' => ['getSuspend', 1]` to `eventHandlerMethodProvider()` in `PluginTest.php`.
4. Run `vendor/bin/phpunit` — all tests pass.

**Result:** Suspend hook fires only for VMware services, logs the action, records history, and stops propagation.

## Common Issues

- **Handler fires for all VPS types, not just VMware:** Missing or incorrect type guard. Confirm the `if` block wraps all logic: `if ($event['type'] == get_service_define('VMWARE'))`.
- **`get_service_define()` returns 0 in tests:** The `$GLOBALS['tf']` stub in `tests/bootstrap.php` only stubs `get_service_define` as a free function. If tests fail with unexpected type match, check that `setUp()` in `PluginTest.php` initializes `$GLOBALS['tf']` with the `SERVICE_DEFINES` map.
- **`testAllHookMethodsExist` fails after adding to `getHooks()`:** The handler method name in `getHooks()` doesn't match the actual method name — PHP method names are case-sensitive.
- **`testEventHandlerParameterType` fails for new handler:** The new method is missing the `GenericEvent $event` type hint. Add it: `public static function getYourEvent(GenericEvent $event)`.
- **Propagation not stopping in behavioral test:** `$event->stopPropagation()` is outside the `if` block or missing entirely. It must be the last statement inside the type guard.