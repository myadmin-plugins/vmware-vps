<?php

declare(strict_types=1);

namespace Detain\MyAdminVmware\Tests;

use Detain\MyAdminVmware\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Unit tests for the VMware VPS Plugin class.
 *
 * Tests cover class structure, static properties, pure methods,
 * event handler signatures, and hook registration.
 *
 * @covers \Detain\MyAdminVmware\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    /**
     * Service type defines used for testing.
     */
    private const SERVICE_DEFINES = [
        'OPENVZ' => 1, 'KVM_LINUX' => 2, 'KVM_WINDOWS' => 3,
        'XEN_LINUX' => 4, 'XEN_WINDOWS' => 5, 'LXC' => 6,
        'VMWARE' => 7, 'VIRTUOZZO' => 8, 'SSD_OPENVZ' => 9,
        'CLOUD_KVM_LINUX' => 10, 'HYPERV' => 11, 'CLOUD_KVM_WINDOWS' => 12,
        'SSD_VIRTUOZZO' => 13, 'KVMV2' => 14, 'KVMV2_WINDOWS' => 15,
        'KVMV2_STORAGE' => 16,
    ];

    /**
     * Set up the reflection instance and global stubs used across tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reflection = new ReflectionClass(Plugin::class);
        // Bind a tf-like stub via the App container so MyAdmin\App::tf()
        // (and the procedural get_service_define() helper) work without DB.
        $defines = self::SERVICE_DEFINES;
        $stub = new class($defines) {
            /** @var array<string, int> */
            private array $defines;
            public string $ima = 'client';
            /** @param array<string, int> $defines */
            public function __construct(array $defines)
            {
                $this->defines = $defines;
            }
            public function get_service_define(string $name): int
            {
                return $this->defines[$name] ?? 0;
            }
        };
        \MyAdmin\App::setContainer(
            \MyAdmin\App\Testing\TestContainerBuilder::make()
                ->withTf($stub)
                ->build()
        );
    }

    protected function tearDown(): void
    {
        \MyAdmin\App::resetContainer();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Class structure tests
    // ---------------------------------------------------------------

    /**
     * Test that the Plugin class exists and is instantiable.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Test that the Plugin class can be instantiated.
     *
     * @return void
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the class resides in the correct namespace.
     *
     * @return void
     */
    public function testNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminVmware', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the class is not abstract.
     *
     * @return void
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the class is not final (allowing extension).
     *
     * @return void
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    // ---------------------------------------------------------------
    // Static property tests
    // ---------------------------------------------------------------

    /**
     * Test that the $name static property is set to the expected value.
     *
     * @return void
     */
    public function testNameProperty(): void
    {
        $this->assertSame('VMware VPS', Plugin::$name);
    }

    /**
     * Test that the $description static property is a non-empty string.
     *
     * @return void
     */
    public function testDescriptionPropertyIsNonEmpty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
    }

    /**
     * Test that the description references VMware.
     *
     * @return void
     */
    public function testDescriptionMentionsVmware(): void
    {
        $this->assertStringContainsString('VMware', Plugin::$description);
    }

    /**
     * Test that the $help static property is a string.
     *
     * @return void
     */
    public function testHelpPropertyIsString(): void
    {
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Test that the $module static property is 'vps'.
     *
     * @return void
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('vps', Plugin::$module);
    }

    /**
     * Test that the $type static property is 'service'.
     *
     * @return void
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Test that all expected static properties exist on the class.
     *
     * @return void
     */
    public function testAllStaticPropertiesExist(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Missing static property: \${$prop}"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isStatic(),
                "Property \${$prop} should be static"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "Property \${$prop} should be public"
            );
        }
    }

    // ---------------------------------------------------------------
    // getHooks() tests (pure method)
    // ---------------------------------------------------------------

    /**
     * Test that getHooks returns an array.
     *
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks returns non-empty array.
     *
     * @return void
     */
    public function testGetHooksIsNotEmpty(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertNotEmpty($hooks);
    }

    /**
     * Test that getHooks contains the settings hook keyed by module.
     *
     * @return void
     */
    public function testGetHooksContainsSettingsHook(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKey = Plugin::$module . '.settings';
        $this->assertArrayHasKey($expectedKey, $hooks);
    }

    /**
     * Test that getHooks contains the deactivate hook keyed by module.
     *
     * @return void
     */
    public function testGetHooksContainsDeactivateHook(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKey = Plugin::$module . '.deactivate';
        $this->assertArrayHasKey($expectedKey, $hooks);
    }

    /**
     * Test that the settings hook points to the correct class and method.
     *
     * @return void
     */
    public function testSettingsHookCallable(): void
    {
        $hooks = Plugin::getHooks();
        $hook = $hooks[Plugin::$module . '.settings'];
        $this->assertIsArray($hook);
        $this->assertCount(2, $hook);
        $this->assertSame(Plugin::class, $hook[0]);
        $this->assertSame('getSettings', $hook[1]);
    }

    /**
     * Test that the deactivate hook points to the correct class and method.
     *
     * @return void
     */
    public function testDeactivateHookCallable(): void
    {
        $hooks = Plugin::getHooks();
        $hook = $hooks[Plugin::$module . '.deactivate'];
        $this->assertIsArray($hook);
        $this->assertCount(2, $hook);
        $this->assertSame(Plugin::class, $hook[0]);
        $this->assertSame('getDeactivate', $hook[1]);
    }

    /**
     * Test that all hook values are valid callables referencing existing methods.
     *
     * @return void
     */
    public function testAllHookMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $this->assertIsArray($handler, "Hook for '{$eventName}' should be an array");
            [$class, $method] = $handler;
            $this->assertTrue(
                method_exists($class, $method),
                "Method {$class}::{$method} referenced in hook '{$eventName}' does not exist"
            );
        }
    }

    /**
     * Test that all hook keys are prefixed with the module name.
     *
     * @return void
     */
    public function testAllHookKeysArePrefixedWithModule(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $key) {
            $this->assertStringStartsWith(
                Plugin::$module . '.',
                $key,
                "Hook key '{$key}' should start with '" . Plugin::$module . ".'"
            );
        }
    }

    /**
     * Test that activate hook is not registered (commented out in source).
     *
     * @return void
     */
    public function testActivateHookIsNotRegistered(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayNotHasKey(Plugin::$module . '.activate', $hooks);
    }

    // ---------------------------------------------------------------
    // Method signature / reflection tests
    // ---------------------------------------------------------------

    /**
     * Test that getHooks is a public static method.
     *
     * @return void
     */
    public function testGetHooksIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that getHooks takes no parameters.
     *
     * @return void
     */
    public function testGetHooksHasNoParameters(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Test that the constructor is public and takes no parameters.
     *
     * @return void
     */
    public function testConstructorSignature(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Provides event handler method names and their expected parameter count.
     *
     * @return array<string, array{string, int}>
     */
    public function eventHandlerMethodProvider(): array
    {
        return [
            'getActivate'     => ['getActivate', 1],
            'getDeactivate'   => ['getDeactivate', 1],
            'getChangeIp'     => ['getChangeIp', 1],
            'getMenu'         => ['getMenu', 1],
            'getRequirements' => ['getRequirements', 1],
            'getSettings'     => ['getSettings', 1],
        ];
    }

    /**
     * Test that all event handler methods are public static and accept exactly one parameter.
     *
     * @dataProvider eventHandlerMethodProvider
     *
     * @param string $methodName     The method name to inspect.
     * @param int    $expectedParams The expected parameter count.
     *
     * @return void
     */
    public function testEventHandlerSignatures(string $methodName, int $expectedParams): void
    {
        $this->assertTrue(
            $this->reflection->hasMethod($methodName),
            "Method {$methodName} should exist"
        );
        $method = $this->reflection->getMethod($methodName);
        $this->assertTrue($method->isPublic(), "{$methodName} should be public");
        $this->assertTrue($method->isStatic(), "{$methodName} should be static");
        $this->assertCount(
            $expectedParams,
            $method->getParameters(),
            "{$methodName} should have {$expectedParams} parameter(s)"
        );
    }

    /**
     * Test that event handler methods accept GenericEvent as their first parameter type.
     *
     * @dataProvider eventHandlerMethodProvider
     *
     * @param string $methodName The method name to inspect.
     * @param int    $_paramCount Unused in this test.
     *
     * @return void
     */
    public function testEventHandlerParameterType(string $methodName, int $_paramCount): void
    {
        $method = $this->reflection->getMethod($methodName);
        $params = $method->getParameters();
        $this->assertNotEmpty($params, "{$methodName} must have at least one parameter");

        $paramType = $params[0]->getType();
        $this->assertNotNull($paramType, "{$methodName} first parameter should have a type hint");
        $this->assertSame(
            GenericEvent::class,
            $paramType->getName(),
            "{$methodName} first parameter should be typed as GenericEvent"
        );
    }

    // ---------------------------------------------------------------
    // Event handler behavioral tests (with stubs)
    // ---------------------------------------------------------------

    /**
     * Test that getActivate stops propagation when type matches VMWARE.
     *
     * @return void
     */
    public function testGetActivateStopsPropagationForVmwareType(): void
    {
        $serviceClass = new class {
            public function getId(): int
            {
                return 42;
            }
        };

        $event = new GenericEvent($serviceClass, ['type' => get_service_define('VMWARE')]);
        Plugin::getActivate($event);
        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * Test that getActivate does not stop propagation for non-VMWARE type.
     *
     * @return void
     */
    public function testGetActivateIgnoresNonVmwareType(): void
    {
        $serviceClass = new class {
            public function getId(): int
            {
                return 42;
            }
        };

        $event = new GenericEvent($serviceClass, ['type' => get_service_define('KVM_LINUX')]);
        Plugin::getActivate($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    /**
     * Test that getDeactivate processes VMWARE type without error when globals are set up.
     *
     * @return void
     */
    public function testGetDeactivateProcessesVmwareType(): void
    {
        $serviceClass = new class {
            public function getId(): int
            {
                return 99;
            }
            public function getCustid(): int
            {
                return 100;
            }
        };

        $historyMock = new class {
            /** @var array<int, array<string, mixed>> */
            public array $calls = [];
            public function add(string $queue, int $id, string $action, string $extra, int $custid): void
            {
                $this->calls[] = compact('queue', 'id', 'action', 'extra', 'custid');
            }
        };

        $defines = self::SERVICE_DEFINES;
        $tfMock = new class($historyMock, $defines) {
            public object $history;
            /** @var array<string, int> */
            private array $defines;
            /** @param array<string, int> $defines */
            public function __construct(object $history, array $defines)
            {
                $this->history = $history;
                $this->defines = $defines;
            }
            public function get_service_define(string $name): int
            {
                return $this->defines[$name] ?? 0;
            }
        };

        $GLOBALS['tf'] = $tfMock;

        $event = new GenericEvent($serviceClass, ['type' => get_service_define('VMWARE')]);
        Plugin::getDeactivate($event);

        $this->assertCount(1, $historyMock->calls);
        $this->assertSame('vpsqueue', $historyMock->calls[0]['queue']);
        $this->assertSame(99, $historyMock->calls[0]['id']);
        $this->assertSame('delete', $historyMock->calls[0]['action']);
        $this->assertSame(100, $historyMock->calls[0]['custid']);
    }

    /**
     * Test that getDeactivate does nothing for non-VMWARE type.
     *
     * @return void
     */
    public function testGetDeactivateIgnoresNonVmwareType(): void
    {
        $historyMock = new class {
            /** @var array<int, array<string, mixed>> */
            public array $calls = [];
            public function add(string $queue, int $id, string $action, string $extra, int $custid): void
            {
                $this->calls[] = compact('queue', 'id', 'action', 'extra', 'custid');
            }
        };

        $defines = self::SERVICE_DEFINES;
        $tfMock = new class($historyMock, $defines) {
            public object $history;
            /** @var array<string, int> */
            private array $defines;
            /** @param array<string, int> $defines */
            public function __construct(object $history, array $defines)
            {
                $this->history = $history;
                $this->defines = $defines;
            }
            public function get_service_define(string $name): int
            {
                return $this->defines[$name] ?? 0;
            }
        };

        $GLOBALS['tf'] = $tfMock;

        $serviceClass = new class {
            public function getId(): int
            {
                return 99;
            }
            public function getCustid(): int
            {
                return 100;
            }
        };

        $event = new GenericEvent($serviceClass, ['type' => get_service_define('KVM_LINUX')]);
        Plugin::getDeactivate($event);

        $this->assertCount(0, $historyMock->calls);
    }

    /**
     * Test that getMenu adds links when user is admin.
     *
     * @return void
     */
    public function testGetMenuAddsLinksForAdmin(): void
    {
        $menu = new class {
            /** @var array<int, array<string, string>> */
            public array $links = [];
            public function add_link(string $module, string $choice, string $icon, string $label): void
            {
                $this->links[] = compact('module', 'choice', 'icon', 'label');
            }
        };

        $defines = self::SERVICE_DEFINES;
        $GLOBALS['tf'] = new class($defines) {
            public string $ima = 'admin';
            /** @var array<string, int> */
            private array $defines;
            /** @param array<string, int> $defines */
            public function __construct(array $defines) { $this->defines = $defines; }
            public function get_service_define(string $name): int { return $this->defines[$name] ?? 0; }
        };

        $event = new GenericEvent($menu);
        Plugin::getMenu($event);

        $this->assertCount(3, $menu->links);
    }

    /**
     * Test that getMenu does not add links for non-admin users.
     *
     * @return void
     */
    public function testGetMenuAddsNoLinksForNonAdmin(): void
    {
        $menu = new class {
            /** @var array<int, array<string, string>> */
            public array $links = [];
            public function add_link(string $module, string $choice, string $icon, string $label): void
            {
                $this->links[] = compact('module', 'choice', 'icon', 'label');
            }
        };

        $defines = self::SERVICE_DEFINES;
        $GLOBALS['tf'] = new class($defines) {
            public string $ima = 'client';
            /** @var array<string, int> */
            private array $defines;
            /** @param array<string, int> $defines */
            public function __construct(array $defines) { $this->defines = $defines; }
            public function get_service_define(string $name): int { return $this->defines[$name] ?? 0; }
        };

        $event = new GenericEvent($menu);
        Plugin::getMenu($event);

        $this->assertCount(0, $menu->links);
    }

    /**
     * Test that getRequirements calls add_page_requirement and add_requirement on the loader.
     *
     * @return void
     */
    public function testGetRequirementsRegistersExpectedRequirements(): void
    {
        $loader = new class {
            /** @var array<int, array{string, string}> */
            public array $pageRequirements = [];
            /** @var array<int, array{string, string}> */
            public array $requirements = [];

            public function add_page_requirement(string $name, string $path): void
            {
                $this->pageRequirements[] = [$name, $path];
            }
            public function add_requirement(string $name, string $path): void
            {
                $this->requirements[] = [$name, $path];
            }
        };

        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);

        $this->assertNotEmpty($loader->pageRequirements, 'Should register page requirements');
        $this->assertNotEmpty($loader->requirements, 'Should register requirements');

        // Verify specific requirement names
        $pageReqNames = array_column($loader->pageRequirements, 0);
        $this->assertContains('crud_vmware_list', $pageReqNames);
        $this->assertContains('crud_reusable_vmware', $pageReqNames);
        $this->assertContains('vmware_licenses_list', $pageReqNames);
        $this->assertContains('vmware_list', $pageReqNames);
        $this->assertContains('reusable_vmware', $pageReqNames);
        $this->assertContains('vps_add_vmware', $pageReqNames);

        $reqNames = array_column($loader->requirements, 0);
        $this->assertContains('get_vmware_licenses', $reqNames);
        $this->assertContains('get_vmware_list', $reqNames);
        $this->assertContains('get_available_vmware', $reqNames);
        $this->assertContains('activate_vmware', $reqNames);
        $this->assertContains('get_reusable_vmware', $reqNames);
        $this->assertContains('class.Vmware', $reqNames);
    }

    /**
     * Test that getSettings calls expected methods on the settings object.
     *
     * @return void
     */
    public function testGetSettingsConfiguresSettingsObject(): void
    {
        $settings = new class {
            /** @var array<int, string> */
            public array $targets = [];
            /** @var array<int, array<string, mixed>> */
            public array $textSettings = [];
            /** @var array<int, array<string, mixed>> */
            public array $dropdownSettings = [];

            public function setTarget(string $target): void
            {
                $this->targets[] = $target;
            }
            public function add_text_setting(string $module, string $cat, string $key, string $label, string $desc, $value): void
            {
                $this->textSettings[] = compact('module', 'cat', 'key', 'label', 'desc', 'value');
            }
            public function add_dropdown_setting(string $module, string $cat, string $key, string $label, string $desc, $value, array $options, array $labels): void
            {
                $this->dropdownSettings[] = compact('module', 'cat', 'key', 'label', 'desc', 'value', 'options', 'labels');
            }
            /**
             * @param string $name
             * @return mixed
             */
            public function get_setting(string $name)
            {
                return '';
            }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        // Should set target to 'module' first and 'global' last
        $this->assertGreaterThanOrEqual(2, count($settings->targets));
        $this->assertSame('module', $settings->targets[0]);
        $this->assertSame('global', $settings->targets[count($settings->targets) - 1]);

        // Should register at least one text setting and one dropdown
        $this->assertNotEmpty($settings->textSettings);
        $this->assertNotEmpty($settings->dropdownSettings);

        // Verify the slice cost setting key
        $textKeys = array_column($settings->textSettings, 'key');
        $this->assertContains('vps_slice_vmware_cost', $textKeys);

        // Verify the out-of-stock dropdown key
        $dropdownKeys = array_column($settings->dropdownSettings, 'key');
        $this->assertContains('outofstock_vmware', $dropdownKeys);
    }

    /**
     * Test that the out-of-stock dropdown setting has binary options (Yes/No).
     *
     * @return void
     */
    public function testOutOfStockDropdownHasBinaryOptions(): void
    {
        $settings = new class {
            /** @var array<int, array<string, mixed>> */
            public array $dropdownSettings = [];

            public function setTarget(string $target): void {}
            public function add_text_setting(string $module, string $cat, string $key, string $label, string $desc, $value): void {}
            public function add_dropdown_setting(string $module, string $cat, string $key, string $label, string $desc, $value, array $options, array $labels): void
            {
                $this->dropdownSettings[] = compact('module', 'cat', 'key', 'label', 'desc', 'value', 'options', 'labels');
            }
            public function get_setting(string $name) { return ''; }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        $dropdown = $settings->dropdownSettings[0];
        $this->assertSame(['0', '1'], $dropdown['options']);
        $this->assertSame(['No', 'Yes'], $dropdown['labels']);
    }

    // ---------------------------------------------------------------
    // Static analysis: DB-dependent methods reference expected globals
    // ---------------------------------------------------------------

    /**
     * Test that getChangeIp method source references expected patterns.
     *
     * @return void
     */
    public function testGetChangeIpReferencesExpectedPatterns(): void
    {
        $method = $this->reflection->getMethod('getChangeIp');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $this->assertNotFalse($filename);
        $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('get_service_define', $source);
        $this->assertStringContainsString('VMWARE', $source);
        $this->assertStringContainsString('stopPropagation', $source);
        $this->assertStringContainsString('editIp', $source);
        $this->assertStringContainsString('faultcode', $source);
        $this->assertStringContainsString("event['newip']", $source);
        $this->assertStringContainsString("event['status']", $source);
    }

    /**
     * Test that getDeactivate method source references the history global.
     *
     * @return void
     */
    public function testGetDeactivateReferencesHistoryGlobal(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $this->assertNotFalse($filename);
        $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString("\$GLOBALS['tf']", $source);
        $this->assertStringContainsString('history', $source);
        $this->assertStringContainsString('delete', $source);
    }

    // ---------------------------------------------------------------
    // Idempotency / determinism
    // ---------------------------------------------------------------

    /**
     * Test that getHooks returns the same result on consecutive calls.
     *
     * @return void
     */
    public function testGetHooksIsDeterministic(): void
    {
        $first = Plugin::getHooks();
        $second = Plugin::getHooks();
        $this->assertSame($first, $second);
    }

    /**
     * Test that creating multiple instances does not alter static state.
     *
     * @return void
     */
    public function testMultipleInstancesDoNotAlterStaticState(): void
    {
        $nameBefore = Plugin::$name;
        $moduleBefore = Plugin::$module;

        new Plugin();
        new Plugin();

        $this->assertSame($nameBefore, Plugin::$name);
        $this->assertSame($moduleBefore, Plugin::$module);
    }
}
