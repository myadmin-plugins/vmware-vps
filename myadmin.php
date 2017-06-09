<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_vmware define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Vmware Vps',
	'description' => 'Allows selling of Vmware Server and VPS License Types.  More info at https://www.netenberg.com/vmware.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a vmware license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-vmware-vps',
	'repo' => 'https://github.com/detain/myadmin-vmware-vps',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		'vps.settings' => ['Detain\MyAdminVmware\Plugin', 'Settings'],
		/*'function.requirements' => ['Detain\MyAdminVmware\Plugin', 'Requirements'],
		'vps.activate' => ['Detain\MyAdminVmware\Plugin', 'Activate'],
		'vps.change_ip' => ['Detain\MyAdminVmware\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminVmware\Plugin', 'Menu'] */
	],
];
