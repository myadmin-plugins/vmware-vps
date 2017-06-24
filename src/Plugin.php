<?php

namespace Detain\MyAdminVmware;

use Detain\Vmware\Vmware;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Vmware Vps';
	public static $description = 'Allows selling of Vmware Server and VPS License Types.  More info at https://www.netenberg.com/vmware.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a vmware license. Allow 10 minutes for activation.';
	public static $module = 'vps';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'vps.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Vmware Activation', __LINE__, __FILE__);
			function_requirements('activate_vmware');
			activate_vmware($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$vmware = new Vmware(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $vmware->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Vmware editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_vmware', 'icons/database_warning_48.png', 'ReUsable Vmware Licenses');
			$menu->add_link($module, 'choice=none.vmware_list', 'icons/database_warning_48.png', 'Vmware Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.vmware_licenses_list', 'whm/createacct.gif', 'List all Vmware Licenses');
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
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Slice Costs', 'vps_slice_vmware_cost', 'VMWare VPS Cost Per Slice:', 'VMWare VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_VMWARE_COST'));
		//$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_vmware_server', 'VMWare NJ Server', NEW_VPS_VMWARE_SERVER, 10, 1);
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_vmware', 'Out Of Stock VMWare Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_VMWARE'), array('0', '1'), array('No', 'Yes',));
	}

}
