<?php
# vim: syntax=php tabstop=4 softtabstop=0 noexpandtab laststatus=1 ruler

/**
 * html/includes/__node.php
 *
 * Class for UNetLab nodes.
 *
 * @author Andrea Dainese <andrea.dainese@gmail.com>
 * @copyright 2014-2016 Andrea Dainese
 * @license BSD-3-Clause https://github.com/dainok/unetlab/blob/master/LICENSE
 * @link http://www.unetlab.com/
 * @version 20160719
 * @property type $flags_eth CMD flags related to Ethernet interfaces. It's mandatory and automatically set.
 * @property type $flags_ser CMD flags related to Serial interfaces. It's mandatory and automatically set.
 * @property type $console protocol. It's optional.
 * @property type $config Filename for the startup configuration. It's optional.
 * @property type $config_data The full startup configuration. It's optional.
 * @property type $cpu CPUs configured on the node. It's optional.
 * @property type $delay Seconds before starting the node. It's optional.
 * @property type $id Device ID. It's mandatory and set during contruction phase.
 * @property type $ethernet Number of configured Ethernet interfaces/portgroups. It's optional.
 * @property type $ethernets Configured Ethernet interfaces/portgroups. It's optional.
 * @property type $icon Icon used on diagram. It's optional.
 * @property type $idlepc Idle PC for Dynamips nodes. It's optional.
 * @property type $image Image for the node. It's mandatory and automatically set to one of the available one.
 * @property type $lab_id Lab ID. It's mandatory and set during contruction phase.
 * @property type $left Left margin for visual position. It's optional.
 * @property type $name Name of the node. It's optional but suggested.
 * @property type $nvram RAM configured on the node. It's optional.
 * @property type $port Console port. It's mandatory and set during contruction phase.
 * @property type $ram NVRAM configured on the node. It's optional.
 * @property type $serial Number of configured Serial interfaces/porgroups. It's optional (IOL only)
 * @property type $serials Configured Serial interfaces/porgroups. It's optional (IOL only)
 * @property type $slots Array of configured slots. It's optional (Dynamips only)
 * @property type $template Template of the node. It's mandatory.
 * @property type $tenant Tenant ID. It's mandatory and set during contruction phase.
 * @property type $top Top margin for visual position.
 * @property type $type Type of the node. It's mandatory.
 * @property type $uuid UUID of the node. It's optional.
 */

class Node {
	private $flags_eth;
	private $flags_ser;
	private $config;
	private $config_data;
	private $console;
	private $cpu;
	private $cpulimit = 1 ;
	private $delay;
	private $ethernet;
	private $ethernets = Array();
	private $firstmac;
	private $management_address = 'dasdsd';
	private $qemu_options;
	private $qemu_arch;
	private $qemu_version;
	private $qemu_nic;
	private $timos_line;
	private $timos_config;
	private $timos_slot;
	private $timos_chassis;
    	private $timos_license;
	private $host;
	private $icon;
	private $id;
	private $idlepc;
	private $image;
	private $lab_id;
	private $left;
	private $name;
	private $nvram;
	private $port;
	private $ram;
	private $serial;
	private $serials = Array();
	private $slots = Array();
	private $template;
	private $tenant;
	private $top; 
	private $type; 
	private $uuid; 
	private $custom_console_port;
	private $docker_options;
	/**
	 * Constructor which creates a node.
	 * Parameters:
	 * - config
	 * - console
	 * - cpu
	 * - delay
	 * - ethernet
	 * - icon
	 * - idlepc
	 * - image
	 * - lab_id
	 * - left
	 * - name
	 * - nvram
	 * - ram
	 * - serial
	 * - slots
	 * - top
	 * - template*
	 * - type*
	 * - uuid
	 * *mandatory
	 *
	 * @param   Array   $p                  Parameters
	 * @param   int     $id                 Node ID
	 * @param   int     $tenant             Tenant ID
	 * @param   string  $lab_id             Lab ID
	 * @return  void
	 *
	 */
	public function __construct($p, $id, $tenant, $lab_id) {
		// Mandatory parameters
		if (!isset($p['type']) || !isset($p['template'])) {
			// Missing mandatory parameters
			error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40000]);
			throw new Exception('40000');
			return 40000;
		}

		if (!checkNodeType($p['type'])) {
			// Type is not valid
			error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40001]);
			throw new Exception('40001');
			return 40001;
		}

		if (!isset($GLOBALS['node_templates'][$p['template']])) {
			// Template is not valid or not available
			error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40002]);
			throw new Exception('40002');
			return 40002;
		}

		// Optional parameters
		if (isset($p['config']) && !checkNodeConfig($p['config'])) {
			// Config is invalid, ignored
			unset($p['config']);
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40003]);
		}

		if (isset($p['delay']) && (int) $p['delay'] < 0) {
			// Delay is invalid, ignored
			unset($p['delay']);
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40004]);
		}

		if (isset($p['icon']) && !checkNodeIcon($p['icon'])) {
			// Icon is invalid, ignored
			unset($p['icon']);
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40005]);
		}

		if (isset($p['image']) && !checkNodeImage($p['image'], $p['type'], $p['template'])) {
			// Image is invalid, ignored
			unset($p['image']);
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40006]);
		}

		if (isset($p['left']) && !checkPosition($p['left'])) {
			if (preg_match('/^[0-9]+%$/', $p['left']) && substr($p['left'], 0, -1) >= 0 && substr($p['left'], 0, -1) <= 100) {
				// Converting percentage to absolute using a 800x600 viewport
				$p['left'] = 1024 * substr($p['left'], 0, -1) / 100;
			} else {
				// Left is invalid, ignored
				unset($p['left']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40007]);
			}
		}

		if (isset($p['name']) && !checkNodeName($p['name'])) {
			// Name is invalid, ignored
			unset($p['name']);
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40008]);
		}

		if (isset($p['top']) && !checkPosition($p['top'])) {
			if (preg_match('/^[0-9]+%$/', $p['top']) && substr($p['top'], 0, -1) >= 0 && substr($p['top'], 0, -1) <= 100) {
				// Converting percentage to absolute using a 800x600 viewport
				$p['top'] = 768 * substr($p['top'], 0, -1) / 100;
			} else {
				// Top is invalid, ignored
				unset($p['top']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40010]);
			}
		}

		// Specific parameters
		if ($p['type'] == 'iol') {
			if (isset($p['ethernet']) && (int) $p['ethernet'] < 0) {
				// Ethernet interfaces is invalid, default to 4
				$p['ethernet'] = 2;
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40012]);
			}

			if (isset($p['nvram']) && (int) $p['nvram'] <= 0) {
				// NVRAM is invalid, ignored
				unset($p['nvram']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40011]);
			}

			if (isset($p['ram']) && (int) $p['ram'] <= 0) {
				// RAM is invalid, ignored
				unset($p['ram']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40009]);
			}

			if (isset($p['serial']) && (int) $p['serial'] < 0) {
				// Serial interfaces is invalid, default to 4
				$p['serial'] = 2;
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40013]);
			}
		}

		if ($p['type'] == 'dynamips') {
			if (isset($p['idlepc']) && !checkNodeIdlepc($p['idlepc'])) {
				// Idle PC is invalid, ignored
				unset($p['idlepc']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40014]);
			}

			if (isset($p['nvram']) && (int) $p['nvram'] <= 0) {
				// NVRAM is invalid, ignored
				unset($p['nvram']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40011]);
			}

			if (isset($p['ram']) && (int) $p['ram'] <= 0) {
				// RAM is invalid, ignored
				unset($p['ram']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40009]);
			}
		}

		if ($p['type'] == 'qemu') {
			if (isset($p['console']) && !checkNodeConsole($p['console'])) {
				// Configured console is invalid, ignored
				unset($p['console']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40027]);
			}

			if (isset($p['cpu']) && (int) $p['cpu'] <= 0) {
				// Configured CPUs is invalid, ignored
				unset($p['cpu']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40015]);
			}

			if (isset($p['ethernet']) && (int) $p['ethernet'] <= 0) {
				// Ethernet interfaces is invalid, default to 4
				$p['ethernet'] = 4;
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40012]);
			}

			if (isset($p['ram']) && (int) $p['ram'] <= 0) {
				// RAM is invalid, ignored
				unset($p['ram']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40009]);
			}

			if (isset($p['uuid']) && !checkUuid($p['uuid'])) {
				// Configured UUID is invalid, ignored
				unset($p['uuid']);
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40026]);
			}
		}

		if ($p['type'] == 'docker') {
			if (isset($p['ethernet']) && (int) $p['ethernet'] <= 0) {
				// Ethernet interfaces is invalid, default to 1
				$p['ethernet'] = 1;
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40012]);
			}

                        if (isset($p['ram']) && (int) $p['ram'] <= 0) {
                                // RAM is invalid, ignored
                                unset($p['ram']);
                                error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40009]);
                        }
                        if (isset($p['console']) && !checkNodeConsole($p['console'])) {
                                // Configured console is invalid, ignored
                                unset($p['console']);
                                error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40027]);
                        }
                        if (isset($p['custom_console_port']) && !checkCustomConsolePort($p['custom_console_port'])) {
                                // Configured console is invalid, ignored
                                unset($p['custom_console_port']);
                                error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40027]);
                        }

                }
                if ($p['type'] == 'vpcs') {
                        if (isset($p['ethernet']) && (int) $p['ethernet'] <= 0) {
                                // Ethernet interfaces is invalid, default to 1
                                $p['ethernet'] = 1;
                                error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40012]);
                        }
                }


		// If image is not set, choose the latest one available
		if (!isset($p['image'])) {
			if (empty(listNodeImages($p['type'], $p['template']))) {
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40025]);
				$p['image'] = '';
			} else {
				$imglist=listNodeImages($p['type'], $p['template']);
				$p['image'] = end($imglist);
			}
		}

		// Now building the node
		$this -> id = (int) $id;
		$this -> lab_id = $lab_id;
		$this -> port = 32768 + 128 * (int) $tenant + (int) $id;	// Node port yangfeng
		$this -> template = $p['template'];
		$this -> tenant = (int) $tenant;
		$this -> type = $p['type'];
		$this -> image = $p['image'];
		if (isset($p['config'])) $this -> config = $p['config'];	// TODO it's a template and must exists or set to saved
		if (isset($p['delay'])) $this -> delay = (int) $p['delay'];
		if (isset($p['icon'])) $this -> icon = $p['icon'];
		if (isset($p['left'])) $this -> left = (int) $p['left'];
		if (isset($p['name'])) $this -> name = $p['name'];
		if (isset($p['top'])) $this -> top = (int) $p['top'];
		if (isset($p['console'])) $this -> console = $p['console'];

		// Building iol node
		if ($p['type'] == 'iol') {
			if (isset($p['nvram'])) $this -> nvram = (int) $p['nvram'];
			if (isset($p['ethernet'])) $this -> ethernet = (int) $p['ethernet'];
			if (isset($p['ram'])) $this -> ram = (int) $p['ram'];
			if (isset($p['serial'])) $this -> serial = (int) $p['serial'];
		}

		// Building dynamips node
		if ($p['type'] == 'dynamips') {
			if (isset($p['idlepc'])) $this -> idlepc = $p['idlepc'];
			if (isset($p['nvram'])) $this -> nvram = (int) $p['nvram'];
			if (isset($p['ram'])) $this -> ram = (int) $p['ram'];
			foreach ($p as $key => $module) {
				if (preg_match('/^slot[0-9]+$/', $key)) {
					// Found a slot
					$slot_id = substr($key, 4);
					$this -> setSlot($slot_id, $module);
				}
			}
		}

		// Building qemu node
		if ($p['type'] == 'qemu') {
			//if (isset($p['console'])) $this -> console = $p['console'];
			if (isset($p['cpu'])) $this -> cpu = (int) $p['cpu'];
			if (isset($p['cpulimit'])) $this -> cpulimit = (int) $p['cpulimit'];
			if (isset($p['ethernet'])) $this -> ethernet = (int) $p['ethernet'];
			if (isset($p['uuid'])) $this -> uuid = $p['uuid'];
			if (isset($p['ram'])) $this -> ram = (int) $p['ram'];
			if ( $p['template']  == 'bigip' || $p['template']  == 'firepower6' || $p['template']  == 'firepower' || $p['template']  == 'linux') {
				if (isset($p['firstmac']) && isValidMac($p['firstmac'])) {
					$this -> firstmac = (string) $p['firstmac'];
				} else {
					$this -> firstmac =  '00:50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00';
				}
			}
			
			if ( $p['template']  == 'timos' ) {
  				#TimosLine
  				if (isset($p['timos_line']) && !empty($p['timos_line']))  {
					$this -> timos_line = (string) $p['timos_line'];
					$this -> timos_slot = ( preg_match("/slot\s*=\s*([\S]+)/", $p['timos_line'] , $output_array)) ? (string) $output_array[1] : '';
					$this -> timos_chassis = ( preg_match("/chassis=\s*([\S]+)/", $p['timos_line'] , $output_array)) ? (string) $output_array[1] : '';
				} else {
 					$this -> timos_line = 'chassis=VSR-I slot=A card=cpm-v';
 					$this -> timos_slot = 'A';
 					$this -> timos_chassis = 'VSR-I';
 				}
 				if (isset($p['management_address'])) {
 					$this -> management_address = $p['management_address'];
 				} else {
 					$this -> management_address = '1.1.1.1/24';
  				}
  				if (isset($p['timos_license'])) {
  					$this -> timos_license = $p['timos_license'];
  				} else {
  					$this -> timos_license = '';
  				}
  				if (isset($p['timos_config'])) {
  					$this -> timos_config = (string) $p['timos_config'];
  				} else {
  					$this -> timos_config = NULL;
  				}
			}
      
			if ( $p['template']  == 'timoscpm' ) {
  				#TimosLine
  				if (isset($p['timos_line']) && !empty($p['timos_line']))  {
  					$this -> timos_line = (string) $p['timos_line'];
 					$this -> timos_slot = ( preg_match("/slot\s*=\s*([\S]+)/", $p['timos_line'] , $output_array)) ? (string) $output_array[1] : '';
 					$this -> timos_chassis = ( preg_match("/chassis=\s*([\S]+)/", $p['timos_line'] , $output_array)) ? (string) $output_array[1] : '';
  				} else {
  					$this -> timos_line = 'slot=A chassis=SR-12 card=cpm5'; 
  					$this -> timos_slot = 'A';
  					$this -> timos_chassis = 'SR-12';			
  				}
  				if (isset($p['management_address'])) {
  					$this -> management_address = $p['management_address'];
  				} else {
  					$this -> management_address = '';
  				}
  				if (isset($p['timos_license'])) {
  					$this -> timos_license = $p['timos_license'];
  				} else {
  					$this -> timos_license ='';
  				}
  				if (isset($p['timos_config'])) {
  					$this -> timos_config = (string) $p['timos_config'];
  				} else {
  					$this -> timos_config = NULL;
  				}
			}
      
			if ( $p['template']  == 'timosiom' ) {
				#TimosLine
				if (isset($p['timos_line'])) {
					$this -> timos_line = (string) $p['timos_line'];
					$this -> timos_slot = ( preg_match("/slot\s*=\s*([\S]+)/", $p['timos_line'] , $output_array)) ? (string) $output_array[1] : '';
					$this -> timos_chassis = ( preg_match("/chassis=\s*([\S]+)/", $p['timos_line'] , $output_array)) ? (string) $output_array[1] : '';
				} else {
					$this -> timos_line = 'slot=1 chassis=SR-12 card=iom3-xp-b mda/1=m10-1gb-sfp-b mda/2=isa-bb';
					$this -> timos_slot = '1';
					$this -> timos_chassis = 'SR-12';
				}
			}
                        if (  ( isset($p['qemu_options']) &&  isset($p['ro_qemu_options'])  &&  $p['qemu_options'] != $p['ro_qemu_options']) || (isset($p['qemu_options']) &&  !isset($p['ro_qemu_options']) )) {
				$this -> qemu_options = (string) $p['qemu_options'];
			} else {
				$this -> qemu_options = '';
			}
			if (isset($p['qemu_version'])) {
				$this -> qemu_version = (string) $p['qemu_version'];
			} else {
				$this -> qemu_version = '';
			}
                        if (isset($p['qemu_arch'])) {
                                $this -> qemu_arch = (string) $p['qemu_arch'];
                        } else {
				$this -> qemu_arch = "";
			}
                        if (isset($p['qemu_nic'])) {
                                $this -> qemu_nic = (string) $p['qemu_nic'];
                        } else {
				$this -> qemu_nic = '';
			}
		}

		// Building docker node
		if ($p['type'] == 'docker') {
                        if (isset($p['ram'])) $this -> ram = (int) $p['ram'];
                        if (isset($p['ethernet'])) $this -> ethernet = (int) $p['ethernet'];
                        if (isset($p['console'])) $this -> console = $p['console'];
                        if (isset($p['custom_console_port'])) $this -> custom_console_port = $p['custom_console_port'];
                        if (isset($p['docker_options'])) $this -> docker_options = $p['docker_options'];
                }
		// Building vpcs node
                if ($p['type'] == 'vpcs') {
                        if (isset($p['ethernet'])) $this -> ethernet = 1;
			//$this -> image = 'vpcs' ;
                }


		// Set interface name
		$this -> setEthernets();
		if ($p['type'] == 'iol') $this -> setSerials();
	}

	/**
	 * Method to add or replace the node metadata.
	 * Editable attributes:
	 * - config
	 * - cpu
	 * - delay
	 * - ethernet
	 * - icon
	 * - idlepc
	 * - image
	 * - left
	 * - name
	 * - nvram
	 * - ram
	 * - serial
	 * - slots
	 * - top
	 * If an attribute is set and is valid, then it will be used. If an
	 * attribute is not set, then the original is maintained. If in attribute
	 * is set and empty '', then the current one is deleted.
	 *
	 * @param   Array   $p                  Parameters
	 * @return  int                         0 means ok
	 */
	public function edit($p) {
		$modified = False;
		
		//if (isset($p['config']))
		//	$p['config'] = str_replace('.php', '', $p['config']);
		
		if (isset($p['config']) && $p['config'] === '') {
			// Config is empty, unset the current one
			unset($this -> config);
			$modified = True;
		} else if (isset($p['config']) && !checkNodeConfig($p['config'])) {
			// Config is invalid, ingored
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40003]);
		} else if (isset($p['config'])) {
			$this -> config = $p['config'];
			$modified = True;
		}
		
		if (isset($p['delay']) && $p['delay'] === '') {
			// Delay is empty, unset the current one
			unset($this -> delay);
			$modified = True;
		} else if (isset($p['delay']) && (int) $p['delay'] < 0) {
			// Delay is invalid, ignored
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40004]);
		} else if (isset($p['delay'])) {
			$this -> delay = (int) $p['delay'];
		}

		if (isset($p['icon']) && $p['icon'] === '') {
			// Icon is empty, unset the current one
			unset($this -> icon);
			$modified = True;
		} else if (isset($p['icon']) && !checkNodeIcon($p['icon'])) {
			// Icon is invalid, ignored
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40005]);
		} else if (isset($p['icon'])) {
			$this -> icon = $p['icon'];
		}

		if (isset($p['image']) && ($p['image'] === '' || !checkNodeImage($p['image'], $p['type'], $p['template']))) {
			// Image is not valid, ignored
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40006]);
		} else if (isset($p['image'])) {
			$this -> image = $p['image'];
			$modified = True;
		}

		if (isset($p['left']) && $p['left'] === '') {
			// Left is empty, unset the current one
			unset($this -> left);
			$modified = True;
		} else if (isset($p['left']) && !checkPosition($p['left'])) {
			// Left is not valid, ignored
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40007]);
		} else if (isset($p['left'])) {
			$this -> left = $p['left'];
			$modified = True;
		}

		if (isset($p['name']) && $p['name'] === '') {
			// Name is empty, unset the current one
			unset($this -> name);
			$modified = True;
		} else if (isset($p['name']) && !checkNodeName($p['name'])) {
			// Name is not valid, ignored
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40008]);
		} else if (isset($p['name'])) {
			$this -> name = $p['name'];
			$modified = True;
		}

		if (isset($p['top']) && $p['top'] === '') {
			// Top is empty, unset the current one
			unset($this -> top);
			$modified = True;
		} else if (isset($p['top']) && !checkPosition($p['top'])) {
			// Top is not valid, ignored
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40010]);
		} else if (isset($p['top'])) {
			$this -> top = $p['top'];
			$modified = True;
		}

		if (isset($p['console']) && $p['console'] === '') {
			unset($this -> console);
			$modified = True;
		} else if (isset($p['console'])) {
			$this -> console = htmlentities($p['console']);
			$modified = True;
		}

		// Specific parameters
		if ($this -> type == 'iol') {
			if (isset($p['ethernet']) && $p['ethernet'] === '') {
				// Ethernet interfaces is empty, unset the current one
				unset($p['ethernet']);
				$modified = True;
			} else if (isset($p['ethernet']) && (int) $p['ethernet'] <  0) {
				// Ethernet interfaces is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40012]);
			} else if (isset($p['ethernet']) && $this -> ethernet != (int) $p['ethernet']) {
				// New Ethernet value
				$this -> ethernet = (int) $p['ethernet'];
				$modified = True;
			}

			if (isset($p['nvram']) && $p['nvram'] === '') {
				// NVRAM is empty, unset the current one
				unset($p['nvram']);
				$modified = True;
			} else if (isset($p['nvram']) && (int) $p['nvram'] <= 0) {
				// NVRAM is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40011]);
			} else if (isset($p['nvram'])) {
				$this -> nvram = (int) $p['nvram'];
			}

			if (isset($p['ram']) && $p['ram'] === '') {
				// RAM is empty, unset the current one
				unset($p['ram']);
				$modified = True;
			} else if (isset($p['ram']) && (int) $p['ram'] <= 0) {
				// RAM is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40009]);
			} else if (isset($p['ram'])) {
				$this -> ram = (int) $p['ram'];
			}

			if (isset($p['serial']) && $p['serial'] === '') {
				// Serial interfaces is empty, unset the current one
				unset($p['serial']);
				$modified = True;
			} else if (isset($p['serial']) && (int) $p['serial'] < 0) {
				// Serial interfaces is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40013]);
			} else if (isset($p['serial']) && $this -> serial != (int) $p['serial']) {
				// New Serial value
				$this -> serial = (int) $p['serial'];
				$modified = True;
			}
		}

		if ($this -> type == 'dynamips') {
			if (isset($p['idlepc']) && $p['idlepc'] === '') {
				// Idle PC is empty, unset the current one
				unset($p['idlepc']);
				$modified = True;
			} else if (isset($p['idlepc']) && !checkNodeIdlepc($p['idlepc'])) {
				// Idle PC is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40014]);
			} else if (isset($p['idlepc'])) {
				$this -> idlepc = $p['idlepc'];
			}

			if (isset($p['nvram']) && $p['nvram'] === '') {
				// NVRAM is empty, unset the current one
				unset($p['nvram']);
				$modified = True;
			} else if (isset($p['nvram']) && (int) $p['nvram'] <= 0) {
				// NVRAM is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40011]);
			} else if (isset($p['nvram'])) {
				$this -> nvram = (int) $p['nvram'];
			}

			if (isset($p['ram']) && $p['ram'] === '') {
				// RAM is empty, unset the current one
				unset($p['ram']);
				$modified = True;
			} else if (isset($p['ram']) && (int) $p['ram'] <= 0) {
				// RAM is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40009]);
			} else if (isset($p['ram'])) {
				$this -> ram = (int) $p['ram'];
			}

			// Loading slots
			foreach ($p as $key => $module) {
				if (preg_match('/^slot[0-9]+$/', $key)) {
					// Found a slot
					$slot_id = substr($key, 4);
					if ($module == '') {
						// Slot is empty, unset the current one
						unset($this -> slots[$slot_id]);
						$modified = True;
					} else if (!isset($this -> slots[$slot_id]) || $this -> slots[$slot_id] != $module) {
						// Need to set the slot (previous was empty or different module)
						$this -> setSlot($slot_id, $module);
						$modified = True;
					}
				}
			}
		}

		if ($this -> type == 'qemu') {
			// TODO check if == vnc or telnet (checkConsole)
			if (isset($p['console']) && $p['console'] === '') {
				// Console is empty, unset the current one
				unset($p['console']);
				$modified = True;
			} else if (isset($p['console'])) {
				$this -> console = htmlentities($p['console']);
				$modified = True;
			}

			if (isset($p['cpu']) && $p['cpu'] === '') {
				// Configured CPUs is empty, unset the current one
				unset($p['cpu']);
				$modified = True;
			} else if (isset($p['cpu']) && (int) $p['cpu'] <= 0) {
				// Configured CPUs is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40011]);
			} else if (isset($p['cpu'])) {
				$this -> cpu = (int) $p['cpu'];
			}
                        if (isset($p['cpulimit']) && ( (int) $p['cpulimit'] < 0 || (int) $p['cpulimit'] > 1 ) ) {
                                // Configured CPUlimit is invalid, ignored
                                error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40028]);
                        } else if (isset($p['cpulimit'])) {
                                $this -> cpulimit = (int) $p['cpulimit'];
                        }

			if (isset($p['ram']) && $p['ram'] === '') {
				// RAM is empty, unset the current one
				unset($p['ram']);
				$modified = True;
			} else if (isset($p['ram']) && (int) $p['ram'] <= 0) {
				// RAM is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40009]);
			} else if (isset($p['ram'])) {
				$this -> ram = (int) $p['ram'];
			}

			if (isset($p['ethernet']) && $p['ethernet'] === '') {
				// Ethernet interfaces is empty, unset the current one
				unset($p['ethernet']);
				$modified = True;
			} else if (isset($p['ethernet']) && (int) $p['ethernet'] <= 0) {
				// Ethernet interfaces is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40012]);
			} else if (isset($p['ethernet']) && $this -> ethernet != (int) $p['ethernet']) {
				// New Ethernet value
				$this -> ethernet = (int) $p['ethernet'];
				$modified = True;
			}

			if (isset($p['uuid']) && $p['uuid'] === '') {
				// UUID is empty, unset the current one
				unset($this -> uuid);
				$modified = True;
			} else if (isset($p['uuid']) && !checkUuid($p['uuid'])) {
				// UUID is not valid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40026]);
			} else if (isset($p['uuid'])) {
				$this -> uuid = $p['uuid'];
				$modified = True;
			}
			if (isset($p['firstmac']) && !isValidMac($p['firstmac'])) {
				$this -> firstmac =  '00:50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00';
				$modified = True;
			} else if (isset($p['firstmac']) && isValidMac( $p['firstmac'])) {
                                $this -> firstmac = (string) $p['firstmac'];
                        } 
			if (isset($p['qemu_options']) &&  isset($p['ro_qemu_options'])  &&  $p['qemu_options'] != $p['ro_qemu_options']) {
				$this -> qemu_options = (string) $p['qemu_options'];
			} else {
				$this -> qemu_options = '';
			}
				
                        if (isset($p['qemu_version'])) {
                                $this -> qemu_version = (string) $p['qemu_version'];
                                }
                        if (isset($p['qemu_arch'])) {
                                $this -> qemu_arch = (string) $p['qemu_arch'];
                                }
                        if (isset($p['qemu_nic'])) {
                                $this -> qemu_nic = (string) $p['qemu_nic'];
                                }
			
			if (isset($p['timos_line']) && $p['timos_line'] === '') {
				// Config is empty, unset the current one
				//unset($this -> timos_line);
				$modified = True;
			} else if (isset($p['timos_line'])) {
				$this -> timos_line = $p['timos_line'];
				$modified = True;
			}
			
			if (isset($p['timos_license']) && $p['timos_license'] === '') {
				// Config is empty, unset the current one
				//unset($this -> timos_license);
				$modified = True;
			} else if (isset($p['timos_license'])) {
				$this -> timos_license = $p['timos_license'];
				$modified = True;
			}

			if (isset($p['management_address']) && $p['management_address'] === '') {
				// Config is empty, unset the current one
				unset($this -> management_address);
				$modified = True;
			} else if (isset($p['management_address'])) {
				$this -> management_address = $p['management_address'];
				$modified = True;
			}		
		}


		if ($this -> type == 'docker') {
                        if (isset($p['ram']) && $p['ram'] === '') {
                                // RAM is empty, unset the current one
                                unset($p['ram']);
                                $modified = True;
                        } else if (isset($p['ram']) && (int) $p['ram'] <= 0) {
                                // RAM is invalid, ignored
                                error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40009]);
                        } else if (isset($p['ram'])) {
                                $this -> ram = (int) $p['ram'];
                        }
                        if (isset($p['console']) && $p['console'] === '') {
                                // Console is empty, unset the current one
                                unset($p['console']);
                                $modified = True;
                        } else if (isset($p['console'])) {
                                $this -> console = htmlentities($p['console']);
                                $modified = True;
                        }
                        if (isset($p['custom_console_port']) && $p['custom_console_port'] === '') {
                                // Console is empty, unset the current one
                                unset($p['custom_console_port']);
                                $modified = True;
                        } else if (isset($p['custom_console_port'])) {
                                $this -> custom_console_port = (int) $p['custom_console_port'];
                                $modified = True;
                        }

                        if (isset($p['docker_options']) && $p['docker_options'] === '') {
                            //options are empty unset current
                            //unset($p['docker_options']);
                            $this -> docker_options = (string) $p['docker_options'];
                            //$modified = True;
                        } else if (isset($p['docker_options'])) {
                            $this -> docker_options = (string) $p['docker_options'];
                            //$modified = True;
                        }

			if (isset($p['ethernet']) && $p['ethernet'] === '') {
				// Ethernet interfaces is empty, unset the current one
				unset($p['ethernet']);
				$modified = True;
			} else if (isset($p['ethernet']) && (int) $p['ethernet'] <= 0) {
				// Ethernet interfaces is invalid, ignored
				error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40012]);
			} else if (isset($p['ethernet']) && $this -> ethernet != (int) $p['ethernet']) {
				// New Ethernet value
				$this -> ethernet = (int) $p['ethernet'];
				$modified = True;
			}
		}

                if ($this -> type == 'vpcs') {
                        if (isset($p['ethernet']) && $p['ethernet'] === '') {
                                // Ethernet interfaces is empty, unset the current one
                                unset($p['ethernet']);
                                $modified = True;
                        } else if (isset($p['ethernet']) && (int) $p['ethernet'] <= 0) {
                                // Ethernet interfaces is invalid, ignored
                                error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40012]);
                        } else if (isset($p['ethernet']) && $this -> ethernet != (int) $p['ethernet']) {
                                // New Ethernet value
                                $this -> ethernet = (int) $p['ethernet'];
                                $modified = True;
                        }
                }

		if ($modified) {
			// At least an attribute is changed
			// Set interface name
			if (isset($p['ethernet'])) $this -> setEthernets();
			if (isset($p['serial']) && $this -> type == 'iol') $this -> setSerials();
			return 0;
		} else {
			// No attribute has been changed
			error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40016]);
			return 40016;
		}
	}

	/**
	 * Method to get command line (for start).
	 * 
	 * @return	string                      Command line
	 */
	public function getCommand() {
		$bin = '';
		$flags = '';

                if ($this -> type == 'vpcs') {
                        error_log(date('M d H:i:s ').'INFO: entering into vpcs getcommand');
                        $bin .= '/opt/vpcsu/bin/vpcs';
                        $flags .= ' -i 1 -p '.$this -> port;
                        $flags .= ' '.$this -> flags_eth ;
			return Array($bin ,$flags);
                }

		if ($this -> getImage() === '') {
			// No image found
			error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][80014]);
			return Array(False, False);
		}

		if ($this -> type == 'iol') {
			// IOL node
			$bin .= '/opt/unetlab/addons/iol/bin/'.$this -> getImage();
			// Node ID, Ethernet and Serial interfaces will be added by the wrapper
			$flags .= ' -n '.$this -> getNvram();  // Size of nvram in Kb
			// -c config TODO                      // Configuration file name
			$flags .= ' -q';                       // Suppress informational messages
			$flags .= ' -m '.$this -> getRam();    // Megabytes of router memory
			if ($this -> getConfig() == '1') {
				$flags .= ' -c startup-config';		// Configuration file name
			}
		}

		if ($this -> type == 'dynamips') {
			// Dynamips node
			require(BASE_DIR.'/html/templates/'.$this -> template.'.php');

			$bin .= '/usr/bin/dynamips';

			if (!is_file($bin)) {
				// Dynamips not found
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][80054]);
				return Array(False, False);
			}

			if (isset($p['dynamips_options'])) {
				$flags = $p['dynamips_options'];
			}

			$flags .= ' -l dynamips.txt';  // Set logging file
			$flags .= ' --idle-pc '.$this -> getIdlePc();  // Set the idle PC
			$flags .= ' -N "'.$this -> getName().'"';      // Set instance name (and Window title)
			$flags .= ' -i '.$this -> id;                  // Set instance ID
			$flags .= ' -r '.$this -> getRam();            // Set the virtual RAM size
			$flags .= ' -n '.$this -> getNvram();          // Set the NVRAM size
			$flags .= ' '.$this -> flags_eth;              // Adding Ethernet flags
			if ($this -> getConfig() == '1') {
				$flags .= ' -C startup-config';			   // Import IOS configuration file into NVRAM
			}
		}

		if ($this -> type == 'qemu') {
			// QEMU node
			require(BASE_DIR.'/html/templates/'.$this -> template.'.php');
			/*
			if (!isset($p['qemu_arch'])) {
				// Arch not found
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][80015]);
				return Array(False, False);
			}

			if (isset($p['qemu_version'])) {
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][80015]);
				$bin .= '/opt/qemu-'.$p['qemu_version'].'/bin/qemu-system-'.$p['qemu_arch'];
			} else {
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][80015]);
				$bin .= '/opt/qemu/bin/qemu-system-'.$p['qemu_arch'];
			}
			*/
  			$qarch = ( $this -> getQemu_arch() != "" ) ? $this -> getQemu_arch() : ( isset($p['qemu_arch']) ? $p['qemu_arch'] : "" );
			if ( $qarch == "" ) {
				                                // Arch not found
                                error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][80015]);
                                return Array(False, False);
                        }
			$qversion = ( $this -> getQemu_version() != "" ) ? $this -> getQemu_version() : ( isset($p['qemu_version']) ? $p['qemu_version'] : "" );
			if ( $qversion != "" ) {
                                $bin .= '/opt/qemu-'.$qversion.'/bin/qemu-system-'.$qarch;
			} else {
                                $bin .= '/opt/qemu/bin/qemu-system-'.$qarch;
                        }

			if (!is_file($bin)) {
				// QEMU not found
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][80016]);
				return Array(False, False);
			}
			if ($this -> getConsole() == 'rdp' ) {

                                $this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$this->ethernet.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $this->ethernet);
                                $this -> flags_eth .= ' -netdev user,id=net'.$this->ethernet.',hostfwd=tcp::'.$this -> port.'-:3389,net=169.254.1.100/30,dhcpstart=169.254.1.100,restrict=on';
                        }
			if ($this -> getConsole() != 'vnc' ) {
				$flags .= ' -nographic ';
			}
			$qnic = ( $this -> getQemu_nic() != "" ) ? $this -> getQemu_nic() : ( isset($p['qemu_nic']) ? $p['qemu_nic'] : "" );
			if ( preg_match('/^[0-9a-zA-Z-]+$/', $qnic)) {
				// Setting non default NIC driver
				$flags .= str_replace('%NICDRIVER%', $qnic, $this -> flags_eth);
			} else if ( $qnic != '' ) {
				// Invalid NIC driver
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][80017]);
				return Array(False, False);
			} else {
				// Setting default NIC driver
				$flags .= str_replace('%NICDRIVER%', 'e1000', $this -> flags_eth);
			}

			$flags .= ' -smp '.$this -> getCpu();             // set the number of CPUs
			$flags .= ' -m '.$this -> getRam();              // configure guest RAM
			$flags .= ' -name '.$this -> getName();          // set the name of the guest
			$flags .= ' -uuid '.$this -> getUuid();          // specify machine UUID
			if ($this -> getConsole() == 'vnc') {
				$flags .= ' -vnc :'.($this -> port - 5900);  // start a VNC server on display
			}

			// Adding controller
			foreach(scandir('/opt/unetlab/addons/qemu/'.$this -> getImage()) as $filename) {
				if (preg_match('/^megasas[a-z]+.qcow2$/', $filename)) {
					// MegaSAS
					$flags .= ' -device megasas,id=scsi0,bus=pci.0,addr=0x5';                                             // Define SCSI BUS
					break;
				} else if (preg_match('/^lsi[a-z]+.qcow2$/', $filename)) {
					// LSI
					$flags .= ' -device lsi,id=scsi0,bus=pci.0,addr=0x5';                                             // Define SCSI BUS
					break;
				} 

			}

			// Adding disks
			foreach(scandir('/opt/unetlab/addons/qemu/'.$this -> getImage()) as $filename) {
				if ($filename == 'cdrom.iso') {
					// CDROM
					$flags .= ' -cdrom /opt/unetlab/addons/qemu/'.$this -> getImage().'/cdrom.iso';
				} else if ($filename == 'kernel.img') {
					// Custom Kernel
					$flags .= ' -kernel /opt/unetlab/addons/qemu/'.$this -> getImage().'/kernel.img';
				} else if (preg_match('/^megasas[a-z]+.qcow2$/', $filename)) {
					// MegaSAS
					$patterns[0] = '/^megasas([a-z]+).qcow2$/';
					$replacements[0] = '$1';
					$disk_id = preg_replace($patterns, $replacements, $filename);
					$lun = (int) ord(strtolower($disk_id)) - 97;
					$flags .= ' -device scsi-disk,bus=scsi0.0,scsi-id='.$lun.',drive=drive-scsi0-0-'.$lun.',id=scsi0-0-'.$lun.',bootindex='.$lun;  // Define SCSI disk
					$flags .= ' -drive file='.$filename.',if=none,id=drive-scsi0-0-'.$lun.',cache=none';                        // Define SCSI file
				} else if (preg_match('/^lsi[a-z]+.qcow2$/', $filename)) {
					// LSI
					$patterns[0] = '/^lsi([a-z]+).qcow2$/';
					$replacements[0] = '$1';
					$disk_id = preg_replace($patterns, $replacements, $filename);
					$lun = (int) ord(strtolower($disk_id)) - 97;
					$flags .= ' -device scsi-disk,bus=scsi0.0,scsi-id='.$lun.',drive=drive-scsi0-0-'.$lun.',id=scsi0-0-'.$lun.',bootindex='.$lun;  // Define SCSI disk
					$flags .= ' -drive file='.$filename.',if=none,id=drive-scsi0-0-'.$lun.',cache=none';                        // Define SCSI file
				} else if (preg_match('/^hd[a-z]+.qcow2$/', $filename)) {
					// IDE
					$patterns[0] = '/^hd([a-z]+).qcow2$/';
					$replacements[0] = '$1';
					$disk_id = preg_replace($patterns, $replacements, $filename);
					$flags .= ' -hd'.$disk_id.' '.$filename;
					if ( $this->template == 'nxosv9k') { 
						//$flags .= ' -bios /opt/qemu/share/qemu/OVMF.fd -drive file=hda.qcow2,if=ide,index=2';
						$flags .= ' -bios /opt/qemu/share/qemu/OVMF.fd -drive file=hda.qcow2,if=none,id=drive-sata-disk0,format=qcow2';
					}
				} else if (preg_match('/^virtide[a-z]+.qcow2$/', $filename)) {
					// IDE
					$patterns[0] = '/^virtide([a-z]+).qcow2$/';
					$replacements[0] = '$1';
					$disk_id = preg_replace($patterns, $replacements, $filename);
					$disk_num = (int) ord(strtolower($disk_id)) - 97;
					$flags .= ' -device virtio-blk-pci,scsi=off,drive=idedisk'.$disk_num.',id=hd'.$disk_id.',bootindex=1';
					$flags .= ' -drive file='.$filename.',if=none,id=idedisk'.$disk_num.',format=qcow2,cache=none';
				} else if (preg_match('/^virtio[a-z]+.qcow2$/', $filename)) {
					// VirtIO
					$patterns[0] = '/^virtio([a-z]+).qcow2$/';
					$replacements[0] = '$1';
					$disk_id = preg_replace($patterns, $replacements, $filename);
					$lun = (int) ord(strtolower($disk_id)) - 97;
					$flags .= ' -drive file='.$filename.',if=virtio,bus=0,unit='.$lun.',cache=none';
				} else if (preg_match('/^scsi[a-z]+.qcow2$/', $filename)) {
					// SCSI
					$patterns[0] = '/^scsi([a-z]+).qcow2$/';
					$replacements[0] = '$1';
					$disk_id = preg_replace($patterns, $replacements, $filename);
					$lun = (int) ord(strtolower($disk_id)) - 97;
					$flags .= ' -drive file='.$filename.',if=scsi,bus=0,unit='.$lun.',cache=none';
				} else if (preg_match('/^sata[a-z]+.qcow2$/', $filename)) {
                                        //SATA
                                        $patterns[0] = '/^sata([a-z]+).qcow2$/';
                                        $replacements[0] = '$1';
                                        $disk_id = preg_replace($patterns, $replacements, $filename);
                                        $disk_id = (int) ord(strtolower($disk_id)) - 97;
                                        $flags .= ' -device ahci,id=ahci'.$disk_id.',bus=pci.0';
                                        $flags .= ' -drive file='.$filename.',if=none,id=drive-sata-disk'.$disk_id.',format=qcow2';
                                        $flags .= ' -device ide-drive,bus=ahci'.$disk_id.'.0,drive=drive-sata-disk'.$disk_id.',id=drive-sata-disk'.$disk_id.',bootindex='.($disk_id + 1);
					if ( $this->template == 'nxosv9k') {
						$flags .=" -bios /opt/qemu/share/qemu/OVMF-sata.fd";
					}
				}
			}

			// Adding custom flags
			$qoptions = ( $this -> getQemu_options() != "" ) ? $this -> getQemu_options() : ( isset($p['qemu_options']) ? $p['qemu_options'] : "" );
			if (isset($this -> timos_license)) {
						
				  $flags .= ' '.$qoptions;					
				  $flags .= ' -smbios type=1,product=\"' . 'Timos:' .$this -> timos_line;
				  if (($this -> management_address) != '') {
					$flags .= ' address='. $this -> management_address . '@active'; 
				  }
				  if (($this -> timos_license) != '') {
					$flags .= ' license-file=' .  $this -> timos_license;   
				  }
				  $flags .= '\"';
					}   
			elseif (isset($this -> timos_line)) {

						
				  $flags .= ' '.$qoptions;					
						$flags .= ' -smbios type=1,product=\"' . 'Timos:' .$this -> timos_line; 
				  $flags .= '\"';
					}     
			else {
				$flags .= ' '.$qoptions;
			}
		}

		if ($this -> type == 'docker') {
			// Docker.io node
            $qoptions = ( $this -> getDocker_options() != "" ) ? $this -> getDocker_options() : ( isset($p['docker_options']) ? $p['docker_options'] : "" );
			$bin .= '/usr/bin/docker -H=tcp://127.0.0.1:4243';
			$flags .= 'run -dti --net=none --rm=false -n INSTANCENAME -h HOSTNAME';
			if ($qoptions != "") {
				$flags .= ' '.$qoptions;
			}
			$flags .= ' -m '.$this -> getRam();		// Maximum RAM
			$flags .= ' '.$this -> getImage();		// Docker image
		}

		return Array($bin, $flags);
	}

	/**
	 * Method to get config.
	 * 
	 * @return	string                      Where the node take the startup-config
	 */

	public function getConfig() {
		if (isset($this -> config)) {
			return $this -> config;
		} else {
			// By default return 'None'
			return '0';
		}
	}

	/**
	 * Method to get config bin.
	 * 
	 * @return	string                      Configured startup-config
	 */
	public function getConfigData() {
		if (isset($this -> config_data)) {
			return $this -> config_data;
		} else {
			// By default return ''
			return '';
		}
	}

	/**
	 * Method to get node console protocol.
	 * 
	 * @return	string                      Node console protocol
	 */
	public function getConsole() {
		if (in_array($this -> type, Array('iol', 'dynamips'))) {
			//return 'telnet';
			return (isset($this -> console) ? $this -> console : "");
		} else if (isset($this -> console)) {
			return $this -> console;
		} else {
			return 'telnet';
		}
	}

	/**
	 * Method to get node console URL.
	 * 
	 * @return	string                      Node console URL
	 */
	public function getConsoleUrl($html5,$username) {
/*
		if ($this -> type == 'docker') {
			return 'docker://'.$_SERVER['SERVER_NAME'].':4243/'.$this -> lab_id.'-'.$this -> tenant.'-'.$this -> id.'?'.$this -> name;
		} else if (isset($this -> console)) {
				return $this -> console.'://'.$_SERVER['SERVER_NAME'].':'.$this -> port;
		} else {
			return 'telnet://'.$_SERVER['SERVER_NAME'].':'.$this -> port;
		}
	}
*/
		//if ($this -> type == 'docker') {
                //        return 'docker://'.$_SERVER['SERVER_NAME'].':4243/'.$this -> lab_id.'-'.$this -> tenant.'-'.$this -> id.'?'.$this -> name;
                //}
		$html5_db = html5_checkDatabase();
		if ( $html5 != 1 ) {
			switch ( $this -> console ) {
				default:
				case 'telnet' :
					//html5AddSession( $html5_db, $username.'_'.$this -> name , "telnet" , $this -> port, $this -> tenant);
					return 'telnet://'.$_SERVER['SERVER_NAME'].':'.$this -> port;
					break;;
				case 'vnc' :
					//html5AddSession( $html5_db, $username.'_'.$this -> name , "vnc" , $this -> port, $this -> tenant);
					return 'vnc://'.$_SERVER['SERVER_NAME'].':'.$this -> port;
					break;;
				case 'rdp' :
					//html5AddSession( $html5_db, $username.'_'.$this -> name , "rdp" , $this -> port, $this -> tenant);
					//return 'rdp://full%20address=s:'.$_SERVER['SERVER_NAME'].':'.$this -> port;
					return '/rdp/?target='.$_SERVER['SERVER_NAME'].'&port='.$this -> port;
					break;;
			}
		} else {
			if ( !isset($this->console) || $this->console == '' ) {
				$console='telnet' ;
			} else {
				$console=$this->console ;
			}
			//$html5_db = html5_checkDatabase();
			html5AddSession( $html5_db, $this -> name.'_'.$this ->id.'_'.$username , $console , $this -> port, $this -> tenant);
			$html5_db = null ;
			addHtml5Perm($this->port,$this->tenant);
			$token=getHtml5Token($this->tenant);
			$b64id=base64_encode( $this->port."\0".'c'."\0".'mysql' );
			//return 'http://'.$_SERVER['SERVER_NAME'].':8080/guacamole/#/client/'.$b64id ;
			return '/html5/#/client/'.$b64id.'?token='.$token ;
		}
	}

	/**
	 * Method to get configured CPU.
	 * 
	 * @return	int                         Configured CPUs
	 */
	public function getCpu() {
		if (isset($this -> cpu)) {
			return $this -> cpu;
		} else {
			// By default return 1
			return 1;
		}
	}

        /**
         * Method to get configured CPULimit.
         *
         * @return      int                         Configured CPUs
         */
        public function getCpuLimit() {
                if (isset($this -> cpulimit)) {
                        return $this -> cpulimit;
                } else {
                        // By default return 1
                        return 1;
                }
        }

	/**
	 * Method to get node delay.
	 * 
	 * @return	int                         Node delay
	 */
	public function getDelay() {
		if (isset($this -> delay)) {
			return $this -> delay;
		} else {
			// By default return 0
			return 0;
		}
	}

	/**
	 * Method to get node Ethernet interfaces.
	 * 
	 * @return	Array                       Array of interfaces
	 */
	public function getEthernets() {
		return $this -> ethernets;
	}

	/**
	 * Method to get count of Ethernet interfaces.
	 * 
	 * @return	int                         Total configured Ethernet interfaces/portgroups
	 */
	public function getEthernetCount() {
		if (in_Array($this -> type, Array('iol', 'qemu', 'docker','vpcs'))) {
			return $this -> ethernet;
		} else {
			return 0;
		}
	}

	/**
	 * Method to get node icon.
	 * 
	 * @return	string                      Node icon
	 */
	public function getIcon() {
		if (isset($this -> icon)) {
			return $this -> icon;
		} else {
			// By default return "Router.png"
			return 'Router.png';
		}
	}

	/**
	 * Method to get node Idle PC.
	 *
	 * @return  string                      Node Idle PC
	 */
	public function getIdlePc() {
		if (isset($this -> idlepc)) {
			return $this -> idlepc;
		} else {
			// By default return "0x0"
			return '0x0';
		}
	}

	/**
	 * Method to get node image.
	 * 
	 * @return	string                      Node iamge
	 */
	public function getImage() {
		return $this -> image;
	}

	/**
	 * Method to get both Ethernet and Serial interfaces.
	 * 
	 * @return	Array                       Node interfaces
	 */
	public function getInterfaces() {
		return $this -> ethernets + $this -> serials;
	}

        /**
         * Method to get Management Mac ( Needed for F5 )
         * 
         * @return      Array                       Node management mac
         */
        public function getFirstMac() {
                return $this -> firstmac;
		printf("firstmac") ;
        }

        /**
         * Method to get Qemu options - custom flags
         * 
         * @return      Array                       qemu options
         */
        public function getQemu_options() {
                return $this -> qemu_options;
        }

        /**
        * Method to get docker options
        *
        * @return      Array                       docker options
        */
       public function getDocker_options() {
           return $this -> docker_options;
       }

        /**
         * Method to get Qemu options - custom flags
         *
         * @return      Array                       qemu options
         */
        public function getQemu_arch() {
                return $this -> qemu_arch;
        }

        /**
         * Method to get Qemu options - custom flags
         *
         * @return      Array                       qemu options
         */
        public function getQemu_version() {
                return $this -> qemu_version;
        }

        /**
         * Method to get Qemu options - custom flags
         *
         * @return      Array                       qemu options
         */
        public function getQemu_nic() {
                return $this -> qemu_nic;
        }

		
		 /**
         * Method to get Timos_Line from Qemu options
         * 
         * @return      string                       Timos_Line
         */
        public function getManagement_address() {
                return $this -> management_address;
		    printf("management_address") ;
        }

        /**
         * Method to get Timos_Line from Qemu options
         * 
         * @return      string                       Timos_Line
         */
        public function getTimos_Line() {
                return $this -> timos_line;
		printf("timos_line") ;
        }

		/**
         * Method to get Timos Config from Qemu options
         * 
         * @return      string                       Timos_Line
         */
        public function getTimos_Config() {
                return $this -> timos_config;
		printf("timos_line") ;
        }
        /**
         * Method to get Timos Slot from Qemu options
         * 
         * @return      string                       Timos_Line
         */
        public function getTimos_Slot() {
                return $this -> timos_slot;
		printf("timos_line") ;
        }

        /**
         * Method to get Timos Slot from Qemu options
         * 
         * @return      string                       Timos_Line
         */
        public function getTimos_Chassis() {
                return $this -> timos_chassis;
		printf("timos_line") ;
        }

        /**
         * Method to get Timos Slot from Qemu options
         * 
         * @return      string                       Timos_Line
         */
        public function getLicense_File() {
                return $this -> timos_license;
		printf("timos_license") ;
        }
		
	/**
	 * Method to get left offset.
	 * 
	 * @return	string                      Left offset
	 */
	public function getLeft() {
		if (isset($this -> left)) {
			return $this -> left;
		} else {
			// By default return a random value between 240 and 560
			return rand(100, 924);
		}
	}

	/**
	 * Method to get node name.
	 * 
	 * @return	string                      Node name
	 */
	public function getName() {
		if (isset($this -> name)) {
			return $this -> name;
		} else {
			// By default return NodeX
			return 'Node'.$this -> id;
		}
	}

	/**
	 * Method to get node type.
	 * 
	 * @return	string                      Node type
	 */
	public function getNType() {
		return $this -> type;
	}

	/**
	 * Method to get node NVRAM.
	 * 
	 * @return	int                         Node NVRAM
	 */
	public function getNvram() {
		if (isset($this -> nvram)) {
			return $this -> nvram;
		} else {
			// By default return 1024
			return 1024;
		}
	}

	/**
	 * Method to get node console port.
	 * 
	 * @return	int                         Node console port
	 */
	public function getPort() {
		return $this -> port;
	}

	
	 /**
         * Method to get node custom console port.
         *
         * @return      int                         Node custom console port
         */
        public function getCustomConsolePort() {
        if (isset($this -> custom_console_port)) {
                        return $this -> custom_console_port;
                } else {
                        // By default return 1024
                        return '';
                }
        }

	/**
	 * Method to get node RAM.
	 * 
	 * @return	int                         Node RAM
	 */
	public function getRam() {
		if (isset($this -> ram)) {
			return $this -> ram;
		} else {
			// By default return 1024
			return 1024;
		}
	}

	/**
	 * Method to get running path.
	 * 
	 * @return	string                      Running path
	 */
	public function getRunningPath() {
		return '/opt/unetlab/tmp/'.$this -> tenant.'/'.$this -> lab_id.'/'.$this -> id;
	}

	/**
	 * Method to get running path.
	 * 
	 * @return	string                      Running path
	 */
	public function getRunningPathA() {
		return '/opt/unetlab/tmp/'.$this -> tenant.'/'.$this -> lab_id.'/';
	}	

	/**
	 * Method to get node Serial interfaces.
	 * 
	 * @return	Array                       Array of interfaces
	 */
	public function getSerials() {
		return $this -> serials;
	}

	/**
	 * Method to get count of Serial interfaces.
	 * 
	 * @return	int                         Total configured Serial interfaces/portgroups
	 */
	public function getSerialCount() {
		if ($this -> type == 'iol') {
			return count($this -> serials) / 4;
		} else {
			return 0;
		}
	}

	/**
	 * Method to get configured slots.
	 * 
	 * @return	Array                       Configured slots
	 */
	public function getSlot() {
		if ($this -> type == 'dynamips') {
			return $this -> slots;
		} else {
			return Array();
		}
	}

	/**
	 * Method to get node status.
	 * 
	 * @return	int                         0 is stopped, 1 is running, 2 is building and started, 3 is building and stopped
	 */
	public function getStatus() {
		if ($this -> type == 'docker') {
			$cmd = 'docker -H=tcp://127.0.0.1:4243 inspect --format="{{ .State.Running }}" '.$this -> getUuid();
			exec($cmd, $o, $rc);
			if ($rc == 0) {
				if ($o[0] == 'true') {
					// Node is running
					if (is_file(BASE_TMP.'/'.$this -> tenant.'/'.$this -> lab_id.'/'.$this -> id.'/.lock')) {
						// Node is running and locked
						return 3;
					} else {
						return 2;
					}
				} else {
					if (is_file(BASE_TMP.'/'.$this -> tenant.'/'.$this -> lab_id.'/'.$this -> id.'/.lock')) {
						// Node is stopped and locked
						return 1;
					} else {
						return 0;
					}
				}
			} else {
				// Instance does not exist
				return 0;
			}
		} else {
			// Need to check if node port is used (netstat + grep doesn't require root privileges)
			$cmd = 'netstat -a -t -n | grep LISTEN | grep :'.$this -> port.' 2>&1';
			exec($cmd, $o, $rc);
			if ($rc == 0) {
				// Console available -> node is running
				if (is_file(BASE_TMP.'/'.$this -> tenant.'/'.$this -> lab_id.'/'.$this -> id.'/.lock')) {
					// Node is running and locked
					return 3;
				} else {
					return 2;
				}
			} else {
				// No console available -> node is stopped
				if (is_file(BASE_TMP.'/'.$this -> tenant.'/'.$this -> lab_id.'/'.$this -> id.'/.lock')) {
					// Node is stopped and locked
					return 1;
				} else {
					return 0;
				}
			}
		}
	}

	/**
	 * Method to get node template.
	 * 
	 * @return	string                      Node template
	 */
	public function getTemplate() {
		return $this -> template;
	}

	/**
* Method to get top offset.
	*
	* @return  string                      Top offset
	*/
	public function getTop() {
		if (isset($this -> top)) {
			return $this -> top;
		} else {
			// By default return a random value between 180 and 420
			return rand(100, 668);
		}
	}

	/**
	* Method to get UUID.
	*
	* @return  string                      UUID
	*/
	public function getUuid() {
		if ($this -> type == 'docker') {
			return $this -> lab_id.'-'.$this -> tenant.'-'.$this -> id;
		} else if (isset($this -> uuid)) {
			return $this -> uuid;
		} else {
			// By default return a random UUID
			return genUuid();
		}
	}

	/**
	 * Method to link an interface.
	 * 
	 * @param   Array   $p                  Parameters
	 * @return  int                         0 means ok
	 */
	public function linkInterface($p) {
		if (!isset($p['id']) || (int) $p['id'] < 0) {
			error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40017]);
			return 40017;
		}

		// Ethernet interface
		if (isset($this -> ethernets[$p['id']])) {
			return $this -> ethernets[$p['id']] -> edit($p);
		}

		// Serial interface
		if (isset($this -> serials[$p['id']])) {
			return $this -> serials[$p['id']] -> edit($p);
		}

		// Non existent interface
		error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40018]);
		return 40018;
	}

	/**
	 * Method to set config bin.
	 * 
	 * @param   string  $config_data         Binary config
	 * @return  int                         0 means ok
	 */
	public function setConfigData($config_data) {
		if ($config_data === '') {
			// Config is empty, unset the current one
			unset($this -> config_data);
			$this -> config = '0';
		} else {
			$this -> config_data = $config_data;
		}
		return 0;
	}

	/**
	 * Method to set configured Ethernet interfaces.
	 * 
	 * @return  int                         0 means ok
	 */
	public function setEthernets() {
		// Storing old configuration
		if (isset($this -> ethernets)) {
			$old_ethernets = $this -> ethernets;
		} else {
			$old_ethernets = Array();
		}

		// Empty the Ethernet Array, must be after setSlot() or after set ethernet attributes
		$this -> ethernets = Array();

		switch ($this -> type) {
			default:
				// Should not be here
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40019]);
				return 40019;
			case 'iol':
				// IOL uses porgroups, 4 interfaces each portgroup
				// Ethernets before Serials
				// i = x/y -> i = x + y * 16 -> x = i - y * 16 = i % 16
				for ($x = 0; $x < $this -> ethernet; $x++) {
					for ($y = 0; $y <= 3; $y++) {
						$i = $x + $y * 16;      // Interface ID
						$n = 'e'.$x.'/'.$y;     // Interface name
						if (isset($old_ethernets[$i])) {
							// Previous interface found, copy from old one
							$this -> ethernets[$i] = $old_ethernets[$i];
						} else {
							// New interface
							try {
								$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
					}
				}
				
				// Setting CMD flags
				$this -> flags_eth = '-e '.$this -> ethernet;  // Number of Ethernet interfaces
				break;
			case 'docker':
				for ($i = 0; $i < $this -> ethernet; $i++) {
					if (isset($old_ethernets[$i])) {
						// Previous interface found, copy from old one
						$this -> ethernets[$i] = $old_ethernets[$i];
					} else {
						$n = 'eth'.$i;		// Interface name
						try {
							$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
						} catch (Exception $e) {
							error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
							error_log(date('M d H:i:s ').(string) $e);
							return 40020;
						}
					}
					// Setting CMD flags (virtual device and map to TAP device)
					$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
					$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
				}
				break;
                        case 'vpcs':
                                for ($i = 0; $i < $this -> ethernet; $i++) {
                                        if (isset($old_ethernets[$i])) {
                                                // Previous interface found, copy from old one
                                                $this -> ethernets[$i] = $old_ethernets[$i];
                                        } else {
                                                $n = 'eth'.$i;          // Interface name
                                                try {
                                                        $this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
                                                } catch (Exception $e) {
                                                        error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
                                                        error_log(date('M d H:i:s ').(string) $e);
                                                        return 40020;
                                                }
                                        }
					
                                        // Setting CMD flags (virtual device and map to TAP device)
					$this -> flags_eth .= ' -e -d vunl'.$this -> tenant.'_'.$this -> id.'_'.$i; 
                                }
                                break;

			case 'dynamips':
				switch ($this -> getTemplate()) {
					default:
						// Should not be here
						error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40021]);
						return 40021;
					case 'c1710':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'fa0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						if (isset($old_ethernets[1])) {
							// Previous interface found, copy from old one
							$this -> ethernets[1] = $old_ethernets[1];
						} else {
							// New interface
							try {
								$this -> ethernets[1] = new Interfc(Array('name' => 'e0', 'type' => 'ethernet'), 1);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						// Setting CMD flags (Cisco 1710 has two embedded interfaces)
						$this -> flags_eth .= ' -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';  // TODO check if ok because we have 0 -> 1
						$this -> flags_eth .= ' -s 0:1:tap:vunl'.$this -> tenant.'_'.$this -> id.'_1';
						break;
					case 'c1760':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'fa0/0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}

						for ($y = 1; $y <= 2; $y++) {
							$i = 128 + $this -> ethernet + $y - 1;      // Interface ID (need to add Ethernets)
							$n = 's'.(0 + $this -> ethernet).'/'.($y - 1);   // Interface name
							if (isset($old_serials[$i])) {
								// Previous interface found, copy from old one
								$this -> serials[$i] = $old_serials[$i];
							} else {
								// New interface
								try {
									$this -> serials[$i] = new Interfc(Array('name' => $n, 'type' => 'serial'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40022]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40022;
								}
							}
						}

						$this -> flags_eth .= ' -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';
						break;
					case 'c2600':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'fa0/0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						if (isset($old_ethernets[1])) {
							// Previous interface found, copy from old one
							$this -> ethernets[1] = $old_ethernets[1];
						} else {
							// New interface
							try {
								$this -> ethernets[1] = new Interfc(Array('name' => 'fa0/1', 'type' => 'ethernet'), 1);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}

						for ($y = 1; $y <= 2; $y++) {
							$i = 128 + $this -> ethernet + $y - 1;      // Interface ID (need to add Ethernets)
							$n = 's'.(0 + $this -> ethernet).'/'.($y - 1);   // Interface name
							if (isset($old_serials[$i])) {
								// Previous interface found, copy from old one
								$this -> serials[$i] = $old_serials[$i];
							} else {
								// New interface
								try {
									$this -> serials[$i] = new Interfc(Array('name' => $n, 'type' => 'serial'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40022]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40022;
								}
							}
						}
					
						// Setting CMD flags
					 	$this -> flags_eth .= ' -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';
					 	$this -> flags_eth .= ' -s 0:1:tap:vunl'.$this -> tenant.'_'.$this -> id.'_1';
						break;
					case 'c2691':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'fa0/0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						if (isset($old_ethernets[1])) {
							// Previous interface found, copy from old one
							$this -> ethernets[1] = $old_ethernets[1];
						} else {
							// New interface
							try {
								$this -> ethernets[1] = new Interfc(Array('name' => 'fa0/1', 'type' => 'ethernet'), 1);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}

						for ($y = 1; $y <= 2; $y++) {
							$i = 128 + $this -> ethernet + $y - 1;      // Interface ID (need to add Ethernets)
							$n = 's'.(0 + $this -> ethernet).'/'.($y - 1);   // Interface name
							if (isset($old_serials[$i])) {
								// Previous interface found, copy from old one
								$this -> serials[$i] = $old_serials[$i];
							} else {
								// New interface
								try {
									$this -> serials[$i] = new Interfc(Array('name' => $n, 'type' => 'serial'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40022]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40022;
								}
							}
						}
					
						// Setting CMD flags
					 	$this -> flags_eth .= ' -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';
					 	$this -> flags_eth .= ' -s 0:1:tap:vunl'.$this -> tenant.'_'.$this -> id.'_1';
						break;
					case 'c3640':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'fa0/0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						$this -> flags_eth .= ' -p 0:NM-1FE-TX -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';
						break;
					case 'c3660':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'fa0/0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						if (isset($old_ethernets[1])) {
							// Previous interface found, copy from old one
							$this -> ethernets[1] = $old_ethernets[1];
						} else {
							// New interface
							try {
								$this -> ethernets[1] = new Interfc(Array('name' => 'fa0/1', 'type' => 'ethernet'), 1);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						$this -> flags_eth .= ' -p 0:Leopard-2FE -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';
						$this -> flags_eth .= ' -s 0:1:tap:vunl'.$this -> tenant.'_'.$this -> id.'_1';
						break;
					case 'c3725':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'fa0/0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						if (isset($old_ethernets[1])) {
							// Previous interface found, copy from old one
							$this -> ethernets[1] = $old_ethernets[1];
						} else {
							// New interface
							try {
								$this -> ethernets[1] = new Interfc(Array('name' => 'fa0/1', 'type' => 'ethernet'), 1);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}

						for ($y = 1; $y <= 2; $y++) {
							$i = 128 + $this -> ethernet + $y - 1;      // Interface ID (need to add Ethernets)
							$n = 's'.(0 + $this -> ethernet).'/'.($y - 1);   // Interface name
							if (isset($old_serials[$i])) {
								// Previous interface found, copy from old one
								$this -> serials[$i] = $old_serials[$i];
							} else {
								// New interface
								try {
									$this -> serials[$i] = new Interfc(Array('name' => $n, 'type' => 'serial'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40022]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40022;
								}
							}
						}
						// Setting CMD flags (Cisco 3725 has two embedded interfaces and two slots)
						$this -> flags_eth .= ' -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';
						$this -> flags_eth .= ' -s 0:1:tap:vunl'.$this -> tenant.'_'.$this -> id.'_1';
						break;
					case 'c3745':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'fa0/0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						if (isset($old_ethernets[1])) {
							// Previous interface found, copy from old one
							$this -> ethernets[1] = $old_ethernets[1];
						} else {
							// New interface
							try {
								$this -> ethernets[1] = new Interfc(Array('name' => 'fa0/1', 'type' => 'ethernet'), 1);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}

						for ($y = 1; $y <= 2; $y++) {
							$i = 128 + $this -> ethernet + $y - 1;      // Interface ID (need to add Ethernets)
							$n = 's'.(0 + $this -> ethernet).'/'.($y - 1);   // Interface name
							if (isset($old_serials[$i])) {
								// Previous interface found, copy from old one
								$this -> serials[$i] = $old_serials[$i];
							} else {
								// New interface
								try {
									$this -> serials[$i] = new Interfc(Array('name' => $n, 'type' => 'serial'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40022]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40022;
								}
							}
						}
						// Setting CMD flags (Cisco 3725 has two embedded interfaces and two slots)
						$this -> flags_eth .= ' -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';
						$this -> flags_eth .= ' -s 0:1:tap:vunl'.$this -> tenant.'_'.$this -> id.'_1';
						break;
					case 'c7200':
						if (isset($old_ethernets[0])) {
							// Previous interface found, copy from old one
							$this -> ethernets[0] = $old_ethernets[0];
						} else {
							// New interface
							try {
								$this -> ethernets[0] = new Interfc(Array('name' => 'Gi0/0', 'type' => 'ethernet'), 0);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						if (isset($old_ethernets[1])) {
							// Previous interface found, copy from old one
							$this -> ethernets[1] = $old_ethernets[1];
						} else {
							// New interface
							try {
								$this -> ethernets[1] = new Interfc(Array('name' => 'e0/0', 'type' => 'ethernet'), 1);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						$this -> flags_eth .= ' -p 0:C7200-IO-GE-E -s 0:0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_0';
						$this -> flags_eth .= ' -s 0:1:tap:vunl'.$this -> tenant.'_'.$this -> id.'_1';
						break;
				}
				break;
			case 'qemu':
				$this -> flags_eth = '';
				switch ($this -> getTemplate()) {
					default:
						for ($i = 0; $i < $this -> ethernet; $i++) {
							$n = 'e'.$i;                // Interface name
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';

						}
						break;
                                        case 'linux':
                                                for ($i = 0; $i < $this -> ethernet; $i++) {
                                                        $n = 'e'.$i;                // Interface name
                                                        if (isset($old_ethernets[$i])) {
                                                                // Previous interface found, copy from old one
                                                                $this -> ethernets[$i] = $old_ethernets[$i];
                                                        } else {
                                                                try {
                                                                        $this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
                                                                } catch (Exception $e) {
                                                                        error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
                                                                        error_log(date('M d H:i:s ').(string) $e);
                                                                        return 40020;
                                                                }
                                                        }
                                                        // Setting CMD flags (virtual device and map to TAP device)
                                                        $this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac='.incMac($this->firstmac,$i);
                                                        $this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';

                                                }
                                                break;

					case 'h3cvsr2k':
					case 'h3cvbras1k':
					case 'h3cvfw1k':
					case 'h3cvlb1k':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							$n = 'Gi'.($i+1).'/0';                // Interface name
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;

					case 'hillstone':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							$n = 'Ethernet0/'.$i;                // Interface name
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;

					case 'a10':
						$this -> flags_eth = '';
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt';            // Interface name
								} else {
									$n = 'E'.$i;            // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							if ($i == 0) {
								// First virtual NIC must be e1000
								$this -> flags_eth .= ' -device e1000,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
								$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
							} else {
								$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
								$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
							}
						}
						break;
					case 'acs':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'G'.$i;		// Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'asa':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'eth'.$i;              // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'asav':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt0/0';         // Interface name
								} else {
									$n = 'Gi0/'.($i - 1);   // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'aruba':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Not in Use';         // Interface name
								} else {
									$n = 'G0/0/'.($i - 1);   // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'bigip':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt';            // Interface name
								} else {
									$n = 'E1.'.$i;          // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device) SPECIAL COMPUTING
							//$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac='.incMac($this->firstmac,$i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'brocadevadx':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt1';           // Interface name
								} else {
									$n = 'Port '.$i;        // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'cda':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'g'.$i;              // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'cips':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt0/0';         // Interface name
								} else {
									$n = 'Gi0/'.($i - 1);   // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'coeus':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'M1';            // Interface name	
								} else {
									$n = 'P'.$i;        // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';

						}
						break;
					case 'cpsg':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'eth'.$i;              // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'clearpass':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'eth'.($i + 1);        // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'csr1000v':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'Gi'.($i + 1);         // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'csr1000vng':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'Gi'.($i + 1);         // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'cumulus':
						for ($i = 0; $i < $this -> ethernet; $i++) {
 							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'eth0';            // Interface name
								} else {
									$n = 'swp'.$i;          // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
 							// Setting CMD flags (virtual device and map to TAP device)
 							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
 							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
 						}
						break;
					case 'extremexos':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt';            // Interface name
								} else {
									$n = 'port'.$i;          // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'firepower':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'eth0 / mgmnt';            // Interface name	
								} else {
									$n = 'G0/'.($i - 1);         // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac='.incMac($this->firstmac,$i);		
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'firepower6':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'eth0 / mgmnt';            // Interface name	
								} else {
									$n = 'G0/'.($i - 1);          // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac='.incMac($this->firstmac,$i);		
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'fortinet':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'port'.($i + 1);       // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'hpvsr':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'Gi'.($i + 1).'/0';    // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'huaweiusg6kv':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'G0/0/0 / MGMT';         // Interface name
								} else {
									$n = 'G1/0/'.($i - 1);   // Interface name

								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'ise':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'G'.$i;		// Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'prime':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'G'.$i;		// Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;

					case 'nsvpx':
                                                for ($i = 0; $i < $this -> ethernet; $i++) {
                                                        if (isset($old_ethernets[$i])) {
                                                                // Previous interface found, copy from old one
                                                                $this -> ethernets[$i] = $old_ethernets[$i];
                                                        } else {
                                                                if ($i == 0) {
                                                                        $n = '0/1';            // Interface name
                                                                } else {
                                                                        $n = '1/'.$i;          // Interface name
                                                                }
                                                                try {
                                                                        $this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
                                                                } catch (Exception $e) {
                                                                        error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
                                                                        error_log(date('M d H:i:s ').(string) $e);
                                                                        return 40020;
                                                                }
                                                        }
                                                        // Setting CMD flags (virtual device and map to TAP device)
                                                        $this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
                                                        $this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
                                                }
                                                break;
					case 'olive':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'em'.$i;               // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'paloalto':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'mgmt';            // Interface name
								} else {
									$n = 'eth1/'.$i;        // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'phoebe':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt';            // Interface name	
								} else {
									$n = 'Data'.$i;        // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';

						}
						break;
					case 'riverbed':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
                                if ($i == 0) {
                                        $n = 'PRI';             // Interface name
                                } else if ($i == 1) {
                                        $n = 'AUX';             // Interface name
                                } else if ($i == 2) {
                                        $n = 'LAN0_0';          // Interface name
                                } else if ($i == 3) {               
                                        $n = 'WAN0_0';          // Interface name
                                } else {
                                        $n = 'em'.$i.($i - 4);
                                }
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'timoscpm':
						if($this -> timos_chassis == 'VSR-I'){
							for ($i = 0; $i < $this -> ethernet; $i++) {
								if (isset($old_ethernets[$i])) {
									// Previous interface found, copy from old one
									$this -> ethernets[$i] = $old_ethernets[$i];
								} else {
									if ($i == 0) {
										$n = 'Mgmt';            // Interface name
									} else {
										$n = '1/1/'.$i;         // Interface name
									}
									try {
										$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (virtual device and map to TAP device)
								$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
								$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
							}			
						} else {
							for ($i = 0; $i < $this -> ethernet; $i++) {
								if (isset($old_ethernets[$i])) {
									// Previous interface found, copy from old one
									$this -> ethernets[$i] = $old_ethernets[$i];
								} else {
									if ($i == 0) {				// management
										$n = 'Mgmt';
									} else if ($i == 1) {		// Switch Fabric
										$n = 'SF';
									} else {
										$n = 'CPM should have 2 itf..'.$i;         // Interface name - shouldn't be seen on CPM
									}
									try {
										$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (virtual device and map to TAP device)
								$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
								$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
							}							
						}
						break;
					case 'timosiom':					
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {				// management
									$n = 'Mgmt';
								} else if ($i == 1) {		// Switch Fabric
									$n = 'SF';


								} else {
									$n = $this -> timos_slot.'/1/'.($i - 1);         // Interface name

								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'mikrotik':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'eth'.($i+1);      // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;					
					case 'nxosv9k':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt0';           // Interface name
								} else {
									$n = 'E1/'.$i;          // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
                                                        $this -> flags_eth .= ' -device %NICDRIVER%,addr='.((int)($i/4)+3).'.'.($i%4).',multifunction=on,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
                                                        $this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';

						}
						break;
					case 'titanium':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt0';           // Interface name
								} else {
									$n = 'E'.((int)($i/48)+2).'/'.($i%48); // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
                                                        $this -> flags_eth .= ' -device %NICDRIVER%,addr='.((int)($i/4)+3).'.'.($i%4).',multifunction=on,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
                                                        $this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'veos':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt1';           // Interface name
								} else {
									$n = 'Eth'.$i;          // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'vios':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'Gi0/'.((int) $i);      // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'viosl2':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'Gi'.((int) ($i / 4)).'/'.((int) ($i % 4));  // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'vmx':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'em0 / fxp0';			// Interface name
								} else if ($i == 1) {
									$n = 'em1 / Internal use';		// Interface name
								} else {
                                                                        $n = 'em'.$i.' / ge-0/0/'.($i - 2);
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'vmxvcp':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'em0 / fxp0';			// Interface name
								} else if ($i == 1) {
									$n = 'em1 / int';		// Interface name
								} else {
                                    $n = 'em'.$i.' / ge-0/0/'.($i - 2);
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'vmxvfp':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'em0 / fxp0';			// Interface name
								} else if ($i == 1) {
									$n = 'em1 / int';		// Interface name
								} else {
                                    $n = 'em'.$i.' / ge-0/0/'.($i - 2);
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'vqfxpfe':
                                                for ($i = 0; $i < $this -> ethernet; $i++) {
                                                        if (isset($old_ethernets[$i])) {
                                                                // Previous interface found, copy from old one
                                                                $this -> ethernets[$i] = $old_ethernets[$i];
                                                        } else {
                                                                if ($i == 0) {
                                                                        $n = 'em0 / fxp0';                      // Interface name
                                                                } else if ($i == 1) {
                                                                        $n = 'em1 / int';              // Interface name
                                                                } else {
                                                                        $n = 'xe-0/0/'.($i - 3);
                                                                }
                                                                try {
                                                                        $this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
                                                                } catch (Exception $e) {
                                                                        error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
                                                                        error_log(date('M d H:i:s ').(string) $e);
                                                                        return 40020;
                                                                }
                                                        }
                                                        // Setting CMD flags (virtual device and map to TAP device)
                                                        $this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
                                                        $this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
                                                }
                                                break;
					case 'vqfxre':
                                                for ($i = 0; $i < $this -> ethernet; $i++) {
                                                        if (isset($old_ethernets[$i])) {
                                                                // Previous interface found, copy from old one
                                                                $this -> ethernets[$i] = $old_ethernets[$i];
                                                        } else {
                                                                if ($i == 0) {
                                                                        $n = 'em0 / fxp0';                      // Interface name
                                                                } else if ($i == 1) {
                                                                        $n = 'em1 / int';              // Interface name
                                                                } else if ($i == 2) {
                                                                        $n = 'em2 / mgmt';              // Interface name
                                                                } else {
                                                                        $n = 'xe-0/0/'.($i - 3);
                                                                }
                                                                try {
                                                                        $this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
                                                                } catch (Exception $e) {
                                                                        error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
                                                                        error_log(date('M d H:i:s ').(string) $e);
                                                                        return 40020;
                                                                }
                                                        }
                                                        // Setting CMD flags (virtual device and map to TAP device)
                                                        $this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
                                                        $this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
                                                }
                                                break;
					case 'infoblox':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'MGNT';
								} else {
									$n = 'LAN'.$i;
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'junipervrr':
                                                for ($i = 0; $i < $this -> ethernet; $i++) {
                                                        if (isset($old_ethernets[$i])) {
                                                                // Previous interface found, copy from old one
                                                                $this -> ethernets[$i] = $old_ethernets[$i];
                                                        } else {
                                                                if ($i == 0) {
                                                                        $n = 'em0';                      // Interface name
                                                                } else if ($i == 1) {
                                                                        $n = 'em1';              // Interface name
                                                                } 
                                                                try {
                                                                        $this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
                                                                } catch (Exception $e) {
                                                                        error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
                                                                        error_log(date('M d H:i:s ').(string) $e);
                                                                        return 40020;
                                                                }
                                                        }
                                                        // Setting CMD flags (virtual device and map to TAP device)
                                                        $this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
                                                        $this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
                                                }
                                                break;
					case 'vsrx':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'ge-0/0/'.$i;          // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
						case 'vsrxng':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'fxp0';         // Interface name
								} else {
									$n = 'ge-0/0/'.($i - 1);   // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'vwaas':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'v1/'.$i;			// Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'vwlc':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'srvc-p';            // Interface name
								} elseif ( $i == 1)  {
									$n = 'mgmt-p';
								} else {
									$n = 'g0/0/'.$i;            // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
                                       case 'pulse':
                                                for ($i = 0; $i < $this -> ethernet; $i++) {
                                                        if (isset($old_ethernets[$i])) {
                                                                // Previous interface found, copy from old one
                                                                $this -> ethernets[$i] = $old_ethernets[$i];
                                                        } else {
                                                                if ($i == 0) {
                                                                        $n = 'internal';            // Interface name
                                                                } elseif ( $i == 1)  {
                                                                        $n = 'external';                        // Interface name
                                                                                                                                } elseif ( $i == 2)  {
                                                                        $n = 'mgmt';                            // Interface name
                                                                } else {
                                                                        $n = 'g0/0/'.$i;            // Interface name
                                                                }
                                                                try {
                                                                        $this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
                                                                } catch (Exception $e) {
                                                                        error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
                                                                        error_log(date('M d H:i:s ').(string) $e);
                                                                        return 40020;
                                                                }
                                                        }
                                                        // Setting CMD flags (virtual device and map to TAP device)
                                                        $this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
                                                        $this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
                                                }
                                                break;
					case 'vyos':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								$n = 'eth'.($i);        // Interface name
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'xrv9k':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt0/0/CPU0/0';      // Interface name
								} else if ($i == 1) {
									$n = 'Internal use';        // Interface name
								} else if ($i == 2) {
								    $n = 'Internal use';        // Interface name
								} else {
								    $n = 'Gi0/0/0/'.($i - 3);   // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,addr='.((int)($i/8)+3).'.'.($i%8).',multifunction=on,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
					case 'xrv':
						for ($i = 0; $i < $this -> ethernet; $i++) {
							if (isset($old_ethernets[$i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[$i] = $old_ethernets[$i];
							} else {
								if ($i == 0) {
									$n = 'Mgmt0/0/CPU0/0';      // Interface name
								} else {
									$n = 'Gi0/0/0/'.($i - 1);   // Interface name
								}
								try {
									$this -> ethernets[$i] = new Interfc(Array('name' => $n, 'type' => 'ethernet'), $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (virtual device and map to TAP device)
							$this -> flags_eth .= ' -device %NICDRIVER%,addr='.((int)($i/8)+3).'.'.($i%8).',multifunction=on,netdev=net'.$i.',mac=50:'.sprintf('%02x', $this -> tenant).':'.sprintf('%02x', $this -> id / 512).':'.sprintf('%02x', $this -> id % 512).':00:'.sprintf('%02x', $i);
							$this -> flags_eth .= ' -netdev tap,id=net'.$i.',ifname=vunl'.$this -> tenant.'_'.$this -> id.'_'.$i.',script=no';
						}
						break;
				}
				$this -> flags_eth = trim($this -> flags_eth);
		}
		return 0;
	}

	/**
	 * Method to set configured Serial interfaces.
	 * 
	 * @return  int                         0 means ok
	 */
	public function setSerials() {
		// Storing old configuration
		if (isset($this -> serials)) {
			$old_serials = $this -> serials;
		} else {
			$old_serials = Array();
		}

		// Empty the Ethernet Array, must be after setSlot() or after set ethernet attributes
		$this -> serials = Array();

		switch ($this -> type) {
			default:
				// Should not be here
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40019]);
				return 40019;
			case 'iol':
				// IOL uses porgroups, 4 interfaces each portgroup
				// Ethernets before Serials
				// i = x/y -> i = x + y * 16 -> x = i - y * 16 = i % 16
				for ($x = 0; $x < $this -> serial; $x++) {
					for ($y = 0; $y <= 3; $y++) {
						$i = $x + $this -> ethernet + $y * 16;      // Interface ID (need to add Ethernets)
						$n = 's'.($x + $this -> ethernet).'/'.$y;   // Interface name
						if (isset($old_serials[$i])) {
							// Previous interface found, copy from old one
							$this -> serials[$i] = $old_serials[$i];
						} else {
							// New interface
							try {
								$this -> serials[$i] = new Interfc(Array('name' => $n, 'type' => 'serial'), $i);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40022]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40022;
							}
						}
					}
				}
				// Setting CMD line
				$this -> flags_ser = '-s '.$this -> serial;  // Number of Serial interfaces
				break;
		}
	}

	/**
	 * Method to configure a slot on Dynamips nodes
	 *
	 * @param   int     $i                  slot_id
	 * @param   string  $s                  slot type
	 * @return  int                         0 means ok
	 */
	public function setSlot($i, $s) {
		if ($this -> type != 'dynamips') {
			error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40023]);
			return 40023;
		}

		// Storing old configuration
		if (isset($this -> ethernets)) {
			$old_ethernets = $this -> ethernets;
		} else {
			$old_ethernets = Array();
		}

		switch ($this -> getTemplate()) {
			default:
				// Should not be here
				error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40019]);
				return 40019;
			case 'c1760':
				if (in_Array($i, Array(1))) {
					switch ($s) {
						default:
							// Unsupported module
							error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40024]);
							return 40024;
						case 'WIC-2T':
							$this -> slots[$i] = $s;
							for ($p = 0; $p <= 1; $p++) {
								if (isset($old_serials[144 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> serials[144 * $i + $p] = $old_serials[144 * $i + $p];
								} else {
									// New interface
									try {
										$this -> serials[144 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 144 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
							}
							break;
						case 'WIC-1ENET':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'e0/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p 0:16:'.$s;
							$this -> flags_eth .= ' -s 0:16:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case '':
							// Empty module
							break;
					}
				}
				break;
			case 'c2600':
			if (in_Array($i, Array(1))) {
				switch ($s) {
					default:
						// Unsupported module
						error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40024]);
						return 40024;
					case 'WIC-2T':
						$this -> slots[$i] = $s;
						for ($p = 0; $p <= 1; $p++) {
							if (isset($old_serials[144 * $i + $p])) {
								// Previous interface found, copy from old one
								$this -> serials[144 * $i + $p] = $old_serials[144 * $i + $p];
							} else {
								// New interface
								try {
									$this -> serials[144 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 144 * $i + $p);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
						}
						break;
					case 'NM-1FE-TX':
						$this -> slots[$i] = $s;
						if (isset($old_ethernets[16 * $i])) {
							// Previous interface found, copy from old one
							$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
						} else {
							// New interface
							try {
								$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'fa'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						// Setting CMD flags (module and tap interface map)
						$this -> flags_eth .= ' -p '.$i.':'.$s;
						$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
						break;
					case 'NM-1E':
						$this -> slots[$i] = $s;
						if (isset($old_ethernets[16 * $i])) {
							// Previous interface found, copy from old one
							$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
						} else {
							// New interface
							try {
								$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'e'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						// Setting CMD flags (module and tap interface map)
						$this -> flags_eth .= ' -p '.$i.':'.$s;
						$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
						break;
					case 'NM-4E':
						$this -> slots[$i] = $s;
						$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
						for ($p = 0; $p <= 3; $p++) {
							if (isset($old_ethernets[16 * $i + $p])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'e'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (tap interface map)
							$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
						}
						break;
					case 'NM-16ESW':
						$this -> slots[$i] = $s;
						$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
						for ($p = 0; $p <= 15; $p++) {
							if (isset($old_ethernets[16 * $i + $p])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'fa'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (tap interface map)
							$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
						}
						break;
					case '':
						// Empty module
						break;
				}
			}
			break;
			case 'c2691':
			if (in_Array($i, Array(1))) {
				switch ($s) {
					default:
						// Unsupported module
						error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40024]);
						return 40024;
					case 'WIC-2T':
						$this -> slots[$i] = $s;
						for ($p = 0; $p <= 1; $p++) {
							if (isset($old_serials[144 * $i + $p])) {
								// Previous interface found, copy from old one
								$this -> serials[144 * $i + $p] = $old_serials[144 * $i + $p];
							} else {
								// New interface
								try {
									$this -> serials[144 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 144 * $i + $p);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
						}
						break;
					case 'NM-4T':
						$this -> slots[$i] = $s;
						for ($p = 0; $p <= 3; $p++) {
							if (isset($old_serials[16 * $i + $p])) {
								$this -> serials[16 * $i + $p] = $old_serials[16 * $i + $p];
							} else {
								try {
									$this -> serials[16 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 16 * $i + $p);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
									}
							}	
						}
						break;
					case 'NM-1FE-TX':
						$this -> slots[$i] = $s;
						if (isset($old_ethernets[16 * $i])) {
							// Previous interface found, copy from old one
							$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
						} else {
							// New interface
							try {
								$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'fa'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
							} catch (Exception $e) {
								error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
								error_log(date('M d H:i:s ').(string) $e);
								return 40020;
							}
						}
						// Setting CMD flags (module and tap interface map)
						$this -> flags_eth .= ' -p '.$i.':'.$s;
						$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
						break;
					case 'NM-16ESW':
						$this -> slots[$i] = $s;
						$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
						for ($p = 0; $p <= 15; $p++) {
							if (isset($old_ethernets[16 * $i + $p])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'fa'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (tap interface map)
							$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
						}
						break;
					case '':
						// Empty module
						break;
				}
			}
			break;
			case 'c3640':
				// c3640 has three slots
				if (in_Array($i, Array(1, 2, 3))) {
					switch ($s) {
						default:
							// Unsupported module
							error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40024]);
							return 40024;
						case 'NM-1FE-TX':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'fa'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p '.$i.':'.$s;
							$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case 'NM-1E':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'e'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p '.$i.':'.$s;
							$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case 'NM-4E':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 3; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'e'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'NM-16ESW':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 15; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'fa'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'NM-4T':
							$this -> slots[$i] = $s;
							for ($p = 0; $p <= 3; $p++) {
								if (isset($old_serials[16 * $i + $p])) {
									$this -> serials[16 * $i + $p] = $old_serials[16 * $i + $p];
								} else {
									try {
										$this -> serials[16 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
										}
								}	
							}
							break;
						case '':
							// Empty module
							break;
					}
				}
				break;
			case 'c3660':
				// c3640 has three slots
				if (in_Array($i, Array(1, 2, 3, 4, 5, 6))) {
					switch ($s) {
						default:
							// Unsupported module
							error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40024]);
							return 40024;
						case 'NM-1FE-TX':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'fa'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p '.$i.':'.$s;
							$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case 'NM-1E':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'e'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p '.$i.':'.$s;
							$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case 'NM-4E':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 3; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'e'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'NM-16ESW':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 15; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'fa'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'NM-4T':
							$this -> slots[$i] = $s;
							for ($p = 0; $p <= 3; $p++) {
								if (isset($old_serials[16 * $i + $p])) {
									$this -> serials[16 * $i + $p] = $old_serials[16 * $i + $p];
								} else {
									try {
										$this -> serials[16 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
										}
								}	
							}
							break;
						case '':
							// Empty module
							break;
					}
				}
				break;
			case 'c3725':
				// c3725 has two slots
				if (in_Array($i, Array(1, 2))) {
					switch ($s) {
						default:
							// Unsupported module
							error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40024]);
							return 40024;
						case 'NM-1FE-TX':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'fa'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p '.$i.':'.$s;
							$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case 'NM-16ESW':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 15; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'fa'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'NM-4T':
							$this -> slots[$i] = $s;
							for ($p = 0; $p <= 3; $p++) {
								if (isset($old_serials[16 * $i + $p])) {
									$this -> serials[16 * $i + $p] = $old_serials[16 * $i + $p];
								} else {
									try {
										$this -> serials[16 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
										}
								}	
							}
							break;
						case '':
							// Empty module
							break;
					}
				}
				break;
			case 'c3745':
				// c3745 has four slots
				if (in_Array($i, Array(1, 2, 3, 4))) {
					switch ($s) {
						default:
							// Unsupported module
							error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40024]);
							return 40024;
						case 'NM-1FE-TX':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'fa'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p '.$i.':'.$s;
							$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case 'NM-16ESW':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 15; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'fa'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'NM-4T':
							$this -> slots[$i] = $s;
							for ($p = 0; $p <= 3; $p++) {
								if (isset($old_serials[16 * $i + $p])) {
									$this -> serials[16 * $i + $p] = $old_serials[16 * $i + $p];
								} else {
									try {
										$this -> serials[16 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
										}
								}	
							}
							break;
						case '':
							// Empty module
							break;
					}
				}
				break;
			case 'c7200':
				// c7200 has six slots
				if (in_Array($i, Array(1, 2, 3, 4, 5, 6))) {
					switch ($s) {
						default:
							error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40024]);
							return 40024;
						case 'PA-FE-TX':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'fa'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p '.$i.':'.$s;
							$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case 'PA-2FE-TX':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 1; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'fa'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'PA-GE':
							$this -> slots[$i] = $s;
							if (isset($old_ethernets[16 * $i])) {
								// Previous interface found, copy from old one
								$this -> ethernets[16 * $i] = $old_ethernets[16 * $i];
							} else {
								// New interface
								try {
									$this -> ethernets[16 * $i] = new Interfc(Array('name' => 'Gi'.$i.'/0', 'type' => 'ethernet'), 16 * $i);
								} catch (Exception $e) {
									error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
									error_log(date('M d H:i:s ').(string) $e);
									return 40020;
								}
							}
							// Setting CMD flags (module and tap interface map)
							$this -> flags_eth .= ' -p '.$i.':'.$s;
							$this -> flags_eth .= ' -s '.$i.':0:tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i);
							break;
						case 'PA-4E':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 3; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'e'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'PA-8E':
							$this -> slots[$i] = $s;
							$this -> flags_eth .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 7; $p++) {
								if (isset($old_ethernets[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> ethernets[16 * $i + $p] = $old_ethernets[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> ethernets[16 * $i + $p] = new Interfc(Array('name' => 'e'.$i.'/'.$p, 'type' => 'ethernet'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
								// Setting CMD flags (tap interface map)
								$this -> flags_eth .= ' -s '.$i.':'.$p.':tap:vunl'.$this -> tenant.'_'.$this -> id.'_'.(16 * $i + $p);
							}
							break;
						case 'PA-4T+':
							$this -> slots[$i] = $s;
							//$this -> flags_ser .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 3; $p++) {
								if (isset($old_serials[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> serials[16 * $i + $p] = $old_serials[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> serials[16 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
							}
							break;
						case 'PA-8T':
							$this -> slots[$i] = $s;
							//$this -> flags_ser .= ' -p '.$i.':'.$s;  // Setting CMD flags (module)
							for ($p = 0; $p <= 7; $p++) {
								if (isset($old_serials[16 * $i + $p])) {
									// Previous interface found, copy from old one
									$this -> serials[16 * $i + $p] = $old_serials[16 * $i + $p];
								} else {
									// New interface
									try {
										$this -> serials[16 * $i + $p] = new Interfc(Array('name' => 's'.$i.'/'.$p, 'type' => 'serial'), 16 * $i + $p);
									} catch (Exception $e) {
										error_log(date('M d H:i:s ').'ERROR: '.$GLOBALS['messages'][40020]);
										error_log(date('M d H:i:s ').(string) $e);
										return 40020;
									}
								}
							}
							break;
						case '':
							// Empty module
							break;
					}
				}
				break;
		}
		return 0;
	}

	/**
	 * Method to unlink an interface.
	 * 
	 * @param   int     $i                  Interface ID
	 * @return  int                         0 means ok
	 */
	public function unlinkInterface($i) {
		if (!isset($i) || (int) $i < 0) {
			error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40017]);
			return 40017;
		}

		// Ethernet interface
		if (isset($this -> ethernets[$i])) {
			return $this -> ethernets[$i] -> edit(Array('network_id' => ''));
		}

		// Serial interface
		if (isset($this -> serials[$i])) {
			return $this -> serials[$i] -> edit(Array('remote_id' => '', 'remote_if' => ''));
		}

		// Non existent interface
		error_log(date('M d H:i:s ').'WARNING: '.$GLOBALS['messages'][40018]);
		return 40018;
	}
}
?>
