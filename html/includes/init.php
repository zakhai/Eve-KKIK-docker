<?php
# vim: syntax=php tabstop=4 softtabstop=0 noexpandtab laststatus=1 ruler

/**
 * html/includes/init.php
 *
 * Initialization file for UNetLab.
 *
 * This file include all needed files and variables to run UNetLab. Don't
 * edit this file, it will be overwritten when updating. Create a new file
 * named 'config.php' under /opt/unetlab/html/includes and set some of all
 * the following parameters:
 *
 * define('DATABASE', '/opt/unetlab/data/database.sdb');
 * define('FORCE_VM', 'auto');
 * define('SESSION', '3600');
 * define('THEME', 'default');
 * define('TIMEZONE', 'Asia/Shanghai');
 *
 * @author Andrea Dainese <andrea.dainese@gmail.com>
 * @copyright 2014-2016 Andrea Dainese
 * @license BSD-3-Clause https://github.com/dainok/unetlab/blob/master/LICENSE
 * @link http://www.unetlab.com/
 * @version 20160719
 */

// Include custom configuration
if (file_exists('includes/config.php')) {
	require_once('includes/config.php');
}

// Preview Code UIlegacy
$UIlegacy = 1 ;

if (!defined('DATABASE')) define('DATABASE', '/opt/unetlab/data/database.sdb');
if (!defined('FORCE_VM')) define('FORCE_VM', 'auto');
if (!defined('MODE')) define('MODE', 'multi-user');
if (!defined('SESSION')) define('SESSION', '3600');
if (!defined('THEME')) define('THEME', 'default');
if (!defined('TIMEOUT')) define('TIMEOUT', 25);
if (!defined('TIMEZONE')) define('TIMEZONE', 'Asia/Shanghai');
if (!defined('TEMPLATE_DISABLED')) define('TEMPLATE_DISABLED', '.missing');

if (!isset($node_config)) {
	$node_config =	Array(
		'l3'			=>	'embedded',
		'l2'			=>	'embedded',
		'c1710'			=>	'embedded',
		'c1760'			=>	'embedded',
		'c2600'			=>	'embedded',
		'c2691'			=>	'embedded',
		'c3640'			=>	'embedded',
		'c3660'			=>	'embedded',
		'c3725'			=>	'embedded',
		'c3745'			=>	'embedded',
		'c7200'			=>	'embedded',
		'vpcs'			=>	'embedded',
		'asa'			=>	'config_asa.py',
		'asav'			=>	'config_asav.py',
		'csr1000v'		=>	'config_csr1000v.py',
		'csr1000vng'	=>	'config_csr1000v.py',
		'docker'		=>	'config_docker.py',
		'titanium'		=>	'config_titanium.py',
		'nxosv9k'		=>	'config_nxosv9k.py',
		'veos'			=>	'config_veos.py',
		'viosl2'		=>	'config_viosl2.py',
		'vios'			=>	'config_vios.py',
		'vsrx'			=>	'config_vsrx.py',
		'junipervrr'		=>	'config_junipervrr.py',
		'vsrxng'		=>	'config_vsrxng.py',
		'timos'			=>	'config_timos.py',
		'timoscpm'		=>	'config_timos.py',
		'vmx'			=>	'config_vmx.py',
		'vmxvcp'		=>	'config_vmxvcp.py',
		'vqfxre'		=>	'config_vqfxre.py',
		'xrv'			=>	'config_xrv.py',
		'xrv9k'			=>	'config_xrv9k.py',
		'pfsense'		=>	'config_pfsense.py',
		'mikrotik'      =>  'config_mikrotik.py',
		'h3cvsr2k'		=>	'config_h3c.py',
		'h3cvfw1k'		=>	'config_h3c.py',
		'h3cvlb1k'		=>	'config_h3c.py',
		'h3cvbras1k'		=>	'config_h3c.py',
		'hillstone'		=>	'config_hillstone.py'
	);
}

if (!isset($node_templates)) {
	$node_templates = Array(
		'a10'			=>	'A10 vThunder',
		'osx'			=>	'Apple OSX',
		'clearpass'		=>	'Aruba ClearPass',
		'aruba'			=>	'Aruba WiFi Controller',
		'veos'			=>	'Arista vEOS',
		'barracuda'		=>	'Barraccuda NGIPS',
		'brocadevadx'	=>	'Brocade vADX',
		'cpsg'			=>	'CheckPoint Security Gateway VE',
		//'docker'		=>	'Docker.io',
		'dockerserver'	=>	'Dockerserver',
		'acs'			=>	'Cisco ACS',
		'ampcloud'      =>  'Cisco AMP Cloud',
		'asa'			=>	'Cisco ASA',
		'asav'			=>	'Cisco ASAv',
                'apicem'                =>      'Cisco Application Policy Infrastructure ',
		'cda'			=>	'Cisco Context Directory Agent',
		'csr1000v'		=>	'Cisco CSR 1000V',
		'csr1000vng'	=>	'Cisco CSR 1000V (Denali and Everest)',
		'cips'			=>	'Cisco IPS',
		'cucm'			=>	'Cisco CUCM',
		'ise'			=>	'Cisco ISE',
		'c1710'			=>	'Cisco IOS 1710 (Dynamips)',
		'c1760'			=>	'Cisco IOS 1760 (Dynamips)',
		'c2600'			=>	'Cisco IOS 2651XM (Dynamips)',
		'c2691'			=>	'Cisco IOS 2691 (Dynamips)',
		'c3640'			=>	'Cisco IOS 3640 (Dynamips)',
		'c3660'			=>	'Cisco IOS 3660 (Dynamips)',
		'c3725'			=>	'Cisco IOS 3725 (Dynamips)',
		'c3745'			=>	'Cisco IOS 3745 (Dynamips)',
		'c7200'			=>	'Cisco IOS 7206VXR (Dynamips)',
		'l3'			=>	'Cisco IOL',
		'l2'			=>	'Cisco IOL Switch',
		'titanium'		=>	'Cisco NX-OSv (Titanium)',
		'nxosv9k'		=>	'Cisco NX-OSv 9K',
		'firepower'		=>	'Cisco FirePower',
		'firepower6'	=>	'Cisco FirePower 6',
		//'ucspe'			=>	'Cisco UCS-PE',
		'vios'			=>	'Cisco vIOS',
		'viosl2'		=>	'Cisco vIOS L2',
		'vnam'			=>	'Cisco vNAM',
		'vwlc'			=>	'Cisco vWLC',
		'vwaas'			=>	'Cisco vWAAS',
		'prime'			=>	'Cisco Prime Infra',
		'phoebe'		=>	'Cisco Email Security Appliance (ESA)',
		'coeus'			=>	'Cisco Web Security Appliance (WSA)',
		'xrv'			=>	'Cisco XRv',
		'xrv9k'			=>	'Cisco XRv 9000',
		'nsvpx'			=>	'Citrix Netscaler',
		'sonicwall'		=>	'Dell SonicWall',
		'cumulus'		=>	'Cumulus VX',
		'extremexos'	=>	'ExtremeXOS',
		'bigip'			=>	'F5 BIG-IP LTM VE',
		'fortinet'		=>	'Fortinet FortiGate',
		'freenas'		=>	'FreeNAS',
		'huaweiusg6kv'  =>  'Huawei USG6000v',
		'h3cvfw1k'		=>	'H3C vFW1000',
		'h3cvsr2k'		=>	'H3C vSR2000',
		'h3cvlb1k'		=>	'H3C vLB1000',
		'h3cvlb1k'		=>	'H3C vLB1000',
		'h3cvbras1k'	=>	'H3C vBras1000',
		'hillstone'		=>	'HillStone SG6000',
		'hpvsr'			=>	'HP VSR1000',
		'ikuai'			=>	'iKuai',
		'infoblox'		=>	'Infoblox IPAM',
		'jspace'        =>  'Junos Space',
		'olive'			=>	'Juniper Olive',
		'vmx'			=>	'Juniper vMX',
		'vmxvcp'        =>  'Juniper vMX VCP',
		'vmxvfp'        =>  'Juniper vMX VFP',
		'vsrx'			=>	'Juniper vSRX',
		'vsrxng'		=>	'Juniper vSRX NextGen',
		'vqfxre'		=>	'Juniper vQFX RE',
		'vqfxpfe'		=>	'Juniper vQFX PFE',
		'junipervrr'		=>	'Juniper RR',
		'linux'			=>	'Linux',
		'mikrotik'		=>	'MikroTik RouterOS',
                'timos'                 =>      'Nokia 7750 VSR-I',
                'timoscpm'              =>      'Nokia 7750 CPM',
                'timosiom'              =>      'Nokia 7750 IOM',
		'ostinato'		=>	'Ostinato',
		'openstack'		=>	'Openstack',
		'opene'			=>	'OpenE',
		'openfiler'		=>	'Openfiler',
		'openwrt'		=>	'OpenWRT',
		'panabit'		=>	'Panabit',
		'paloalto'		=>	'Palo Alto VM-100 Firewall',
		'pfsense'		=>	'pfSense Firewall',
		'pulse'			=>	'Pulse Secure',
		'alteon'		=>	'Radware AlteonVA',
		'radware'		=>	'Radware AlteonOS',
		'riverbed'		=>	'Riverbed',
		'seafile'		=>	'Seafile',
		'sterra'		=>	'S-Terra',
		'synology'		=>	'Synology',
		'vyos'			=>	'VyOS',
		'zabbix'		=>	'Zabbix',
		'nagios'		=>	'Nagios',
		'esxi'			=>	'VMWare ESXi',
		'vcenter'		=>	'VMWare vCenter',
		'win'	        =>	'Windows',
		'winserver'		=>	'Windows Server',
		'android'		=>	'Android',
		'vpcs'			=>	'Virtual PC (VPCS)'
	);
	$qemudir = scandir("/opt/unetlab/addons/qemu/");
	$ioldir=scandir("/opt/unetlab/addons/iol/bin/");
	$dyndir=scandir("/opt/unetlab/addons/dynamips/");
	
	foreach ( $node_templates as $templ => $desc ) {
		$found = 0 ;
		if ( $templ == "l3" || $templ == "l2" ) {
			foreach ( $ioldir as $dir ) {
                        	if ( preg_match ( "/".$templ."/",$dir )  ==  1 ) {
                                	$found = 1 ;
                        	}
                	}
		}
		if ( $templ == "c1710" || $templ == "c1760" || $templ == "c2600" || $templ == "c2691" || $templ == "c3640" || $templ == "c3660" || $templ == "c3725" || $templ == "c3745" || $templ == "c7200" ) {
			foreach ( $dyndir as $dir ) {
				if ( preg_match ( "/".$templ."/",$dir )  ==  1 ) {
					$found = 1 ;
				}
			}
		}
		if ( $templ == "vpcs" || $templ == "docker"  ) {
		$found = 1 ;
		}
		foreach ( $qemudir as $dir ) {
			if ( preg_match ( "/".$templ."-.*/",$dir )  ==  1 ) {
				$found = 1 ;
			}
		}
		if ( $found == 0 )  {
			$node_templates[$templ] = $desc.'.missing';
			$node_templates[$templ] = $desc.'.missing';
		}
			
	}
			
}

// Define parameters
$eve_ver = file_get_contents("/opt/unetlab/html/themes/adminLTE/VERSION");
$yf_ver = file_get_contents("/opt/unetlab/html/themes/adminLTE/RVERSION");
define('RVERSION', $yf_ver);
define('VERSION', $eve_ver);
define('BASE_DIR', '/opt/unetlab');
define('BASE_LAB', BASE_DIR.'/labs');
define('BASE_TMP', BASE_DIR.'/tmp');
define('BASE_THEME', '/themes/'.THEME);

// Setting timezone
date_default_timezone_set(TIMEZONE);

// Include classes and functions
require_once(BASE_DIR.'/html/includes/__interfc.php');
require_once(BASE_DIR.'/html/includes/__lab.php');
require_once(BASE_DIR.'/html/includes/__network.php');
require_once(BASE_DIR.'/html/includes/__node.php');
require_once(BASE_DIR.'/html/includes/__textobject.php');
require_once(BASE_DIR.'/html/includes/__picture.php');
require_once(BASE_DIR.'/html/includes/functions.php');
require_once(BASE_DIR.'/html/includes/messages_en.php');
require_once(BASE_DIR.'/html/includes/Parsedown.php');
if (defined('LOCALE') && is_file(BASE_DIR.'/html/includes/messages_'.LOCALE.'.php')) {
	// Load a custom language
	require_once(BASE_DIR.'/html/includes/messages_'.LOCALE.'.php');
}

// Include CLI specific functions
if (php_sapi_name() ==	'cli') {
	// CLI User
	require_once(BASE_DIR.'/html/includes/cli.php');
} else {
	// Web User
	//session_start();
}
?>
