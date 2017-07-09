<?php

namespace Detain\MyAdminVmware;

use Detain\Vmware\Vmware;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'VMware VPS';
	public static $description = 'Allows selling of VMware VPS Types. VMware, Inc. is a subsidiary of Dell Technologies that provides cloud computing and platform virtualization software and services.[2] It was the first commercially successful company to virtualize the x86 architecture.  More info at https://www.vmware.com/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_VMWARE) {
			myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__);
			function_requirements('activate_vmware');
			activate_vmware($serviceClass->getIp(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function getDeactivate(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_VMWARE) {
			myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_VMWARE) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$vmware = new Vmware(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', "IP Change - (OLD:".$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $vmware->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Vmware editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_vmware', 'icons/database_warning_48.png', 'ReUsable Vmware Licenses');
			$menu->add_link(self::$module, 'choice=none.vmware_list', 'icons/database_warning_48.png', 'Vmware Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.vmware_licenses_list', 'whm/createacct.gif', 'List all Vmware Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('crud_vmware_list', '/../vendor/detain/crud/src/crud/crud_vmware_list.php');
		$loader->add_requirement('crud_reusable_vmware', '/../vendor/detain/crud/src/crud/crud_reusable_vmware.php');
		$loader->add_requirement('get_vmware_licenses', '/../vendor/detain/myadmin-vmware-vps/src/vmware.inc.php');
		$loader->add_requirement('get_vmware_list', '/../vendor/detain/myadmin-vmware-vps/src/vmware.inc.php');
		$loader->add_requirement('vmware_licenses_list', '/../vendor/detain/myadmin-vmware-vps/src/vmware_licenses_list.php');
		$loader->add_requirement('vmware_list', '/../vendor/detain/myadmin-vmware-vps/src/vmware_list.php');
		$loader->add_requirement('get_available_vmware', '/../vendor/detain/myadmin-vmware-vps/src/vmware.inc.php');
		$loader->add_requirement('activate_vmware', '/../vendor/detain/myadmin-vmware-vps/src/vmware.inc.php');
		$loader->add_requirement('get_reusable_vmware', '/../vendor/detain/myadmin-vmware-vps/src/vmware.inc.php');
		$loader->add_requirement('reusable_vmware', '/../vendor/detain/myadmin-vmware-vps/src/reusable_vmware.php');
		$loader->add_requirement('class.Vmware', '/../vendor/detain/vmware-vps/src/Vmware.php');
		$loader->add_requirement('vps_add_vmware', '/vps/addons/vps_add_vmware.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_vmware_cost', 'VMWare VPS Cost Per Slice:', 'VMWare VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_VMWARE_COST'));
		//$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_vmware_server', 'VMWare NJ Server', NEW_VPS_VMWARE_SERVER, 10, 1);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_vmware', 'Out Of Stock VMWare Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_VMWARE'), ['0', '1'], ['No', 'Yes']);
	}

}
