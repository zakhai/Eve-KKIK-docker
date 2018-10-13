<?php
# vim: syntax=php tabstop=4 softtabstop=0 noexpandtab laststatus=1 ruler

/**
 * html/includes/api_nodes.php
 *
 * Nodes related functions for REST APIs.
 *
 * @author Andrea Dainese <andrea.dainese@gmail.com>
 * @copyright 2014-2016 Andrea Dainese
 * @license BSD-3-Clause https://github.com/dainok/unetlab/blob/master/LICENSE
 * @link http://www.unetlab.com/
 * @version 20160719
 */

/**
 * Function to add a node to a lab.
 *
 * @param   Lab     $lab                Lab
 * @param   Array   $p                  Parameters
 * @param   bool    $o                  True if need to add ID to name
 * @return  Array                       Return code (JSend data)
 */
function apiAddLabNode($lab, $p, $o) {
	if (isset($p['numberNodes'])) 
		$numberNodes = $p['numberNodes'];
	
	$default_name = $p['name'];
	if ($default_name == "R")
		$o = True;
	
	$ids = array();
	$no_array = false;
        $initLeft = $p['left'] ;
        $initTop = $p['top'] ;
	if (!isset($numberNodes))
	{
		$numberNodes = 1;
		$no_array = true;
	}
	for($i = 1 ; $i<= $numberNodes; $i++)
	{
		if ($i > 1)
		{
			$p['left'] =  $initLeft + ( ( $i -1 ) % 5 )   * 60   ;
			$p['top'] =  $initTop + ( intval( ( $i -1 ) / 5 )  * 80 ) ;
		}
		$id = $lab -> getFreeNodeId();
                if ( $id > 128 ) { $rc = 20046 ;  break ;} 	//yangfeng free node id
		// Adding node_id to node_name if required
		if ($o == True && $default_name || $numberNodes > 1) $p['name'] = $default_name.$lab -> getFreeNodeId();
		
		// Adding the node
		$rc = $lab -> addNode($p);
		$ids[] = $id;
	}
	if ($rc === 0) {
		$output['code'] = 201;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][60023];
                $output['data'] = array(
                        'id'=> ($no_array ? $id : $ids)
			);
        } else if ( $rc = 20046 ) {
                $output['code'] = 201;
                $output['status'] = 'success';
                $output['message'] = $GLOBALS['messages'][$rc];
                $output['data'] = array(
                        'id'=> ($no_array ? $id : $ids)
                        );
	} else {
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to delete a lab node.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $id                 Node ID
 * @return  Array                       Return code (JSend data)
 */
function apiDeleteLabNode($lab, $id, $tenant) {
	// Delete all tmp files for the node
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper -a delete -T 0 -D '.$id.' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';  // Tenant not required for delete operation
	exec($cmd, $o, $rc);
	// Stop the node
	foreach( scandir("/opt/unetlab/tmp/") as $value ) {	
		if ( is_dir("/opt/unetlab/tmp/".$value) and intval($value) >= 0 ) {
			$output=apiStopLabNode($lab, $id, intval($value)); 
			error_log('Delete Node Lab : output ' . implode("|",$output) . ' id: ' . $id . ' tenant: ' . $tenant );
			if ($output['status'] == 400 ) return $output; 	
		}
	}
	// Deleting the node
	$rc = $lab -> deleteNode($id);
	if ($rc === 0) {
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][60023];
	} else {
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to edit a lab node.
 *
 * @param   Lab     $lab                Lab
 * @param   Array   $p                  Parameters
 * @return  Array                       Return code (JSend data)
 */
function apiEditLabNode($lab, $p) {
	// Edit node
	$rc = $lab -> editNode($p);

	if ($rc === 0) {
		$output['code'] = 201;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][60023];
	} else {
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}
/**
 * Function to edit multiple lab node.
 *
 * @param   Lab     $lab                Lab
 * @param   Array   $p                  Parameters
 * @return  Array                       Return code (JSend data)
 */
function apiEditLabNodes($lab, $p) {
        // Edit node
            //$rc=$lab -> editNode
        foreach ( $p as $node ) { 
          $node['save'] = 0 ;
          $rc = $lab -> editNode($node);
        }
        $rc = $lab -> save() ;
        if ($rc === 0) {
                $output['code'] = 201;
                $output['status'] = 'success';
                $output['message'] = $GLOBALS['messages'][60023];
        } else {
                $output['code'] = 400;
                $output['status'] = 'fail';
                $output['message'] = $GLOBALS['messages'][$rc];
        }
        return $output;
}
/**
 * Function to export a single node.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $id                 Node ID
 * @param   int     $tenant             Tenant ID
 * @return  Array                       Return code (JSend data)
 */
function apiExportLabNode($lab, $id, $tenant) {
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper';
	$cmd .= ' -a export';
	$cmd .= ' -T '.$tenant;
	$cmd .= ' -D '.$id;
	$cmd .= ' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';
	$cmd .= ' 2>> /opt/unetlab/data/Logs/unl_wrapper.txt';
	exec($cmd, $o, $rc);
	if ($rc == 0) {
		// Config exported
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][80058];
	} else {
		// Failed to export
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to export all nodes.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $tenant             Tenant ID
 * @return  Array                       Return code (JSend data)
 */
function apiExportLabNodes($lab, $tenant) {
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper';
	$cmd .= ' -a export';
	$cmd .= ' -T '.$tenant;
	$cmd .= ' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';
	$cmd .= ' 2>> /opt/unetlab/data/Logs/unl_wrapper.txt';
	exec($cmd, $o, $rc);
	if ($rc == 0) {
		// Nodes started
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][80057];
	} else {
		// Failed to start
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/*
 * Function to get a single lab node.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $id                 Node ID
 * @param   Array   $p                  Parameters
 * @return  Array                       Lab node (JSend data)
 */
function apiEditLabNodeInterfaces($lab, $id, $p) {
	// Edit node interfaces
	$rc = $lab -> connectNode($id, $p);

	if ($rc === 0) {
		$output['code'] = 201;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][60023];
	} else {
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to get a single lab node.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $id                 Node ID
 * @return  Array                       Lab node (JSend data)
 */
function apiGetLabNode($lab, $id , $html5, $username ) {
	// Getting node
	if (isset($lab -> getNodes()[$id])) {
		$node = $lab -> getNodes()[$id];

		// Printing node
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][60025];
		$output['data'] = Array(
			'console' => $node -> getConsole(),
			'config' => $node -> getConfig(),
			'delay' => $node -> getDelay(),
			'left' => $node -> getLeft(),
			'icon' => $node -> getIcon(),
			'image' => $node -> getImage(),
			'name' => $node -> getName(),
			'status' => $node -> getStatus(),
			'template' => $node -> getTemplate(),
			'type' => $node -> getNType(),
			'top' => $node -> getTop(),
			'url' => $node -> getConsoleUrl($html5, $username)
		);

		if ($node -> getNType() == 'iol') {
			$output['data']['ethernet'] = $node -> getEthernetCount();
			$output['data']['nvram'] = $node -> getNvram();
			$output['data']['ram'] = $node -> getRam();
			$output['data']['serial'] = $node -> getSerialCount();
		}

		if ($node -> getNType() == 'dynamips') {
			$output['data']['idlepc'] = $node -> getIdlePc();
			$output['data']['nvram'] = $node -> getNvram();
			$output['data']['ram'] = $node -> getRam();
			foreach ($node -> getSlot() as $slot_id => $module) {
				$output['data']['slot'.$slot_id] = $module;
			}
		}

		if ($node -> getNType() == 'qemu') {
			$output['data']['cpulimit'] = $node -> getCpuLimit();
			$output['data']['cpu'] = $node -> getCpu();
			$output['data']['ethernet'] = $node -> getEthernetCount();
			$output['data']['ram'] = $node -> getRam();
			$output['data']['uuid'] = $node -> getUuid();
			if ( $node -> getTemplate() == "bigip" || $node -> getTemplate() == "firepower6" || $node -> getTemplate() == "firepower" || $node -> getTemplate() == "linux" )  {
				$output['data']['firstmac'] = $node -> getFirstMac();
			}
			if ($node -> getTemplate() == "timos"){
				$output['data']['management_address'] = $node -> getManagement_address();
				$output['data']['timos_line'] = $node -> getTimos_Line();
				$output['data']['timos_license'] = $node -> getLicense_File();
			}
			if ($node -> getTemplate() == "timoscpm"){
				$output['data']['management_address'] = $node -> getManagement_address();
				$output['data']['timos_line'] = $node -> getTimos_Line();
				$output['data']['timos_license'] = $node -> getLicense_File();
			}
			if ($node -> getTemplate() == "timosiom"){
				$output['data']['timos_line'] = $node -> getTimos_Line();

			}
                        $output['data']['qemu_options'] = $node -> getQemu_options();
                        $output['data']['qemu_version'] = $node -> getQemu_version();
                        $output['data']['qemu_arch'] = $node -> getQemu_arch();
                        $output['data']['qemu_nic'] = $node -> getQemu_nic();
		}

		if ($node -> getNType() == 'docker') {
			$output['data']['ethernet'] = $node -> getEthernetCount();
			$output['data']['ram'] = $node -> getRam();
			$output['data']['custom_console_port'] = $node -> getCustomConsolePort();
			$output['data']['docker_options'] = $node -> getDocker_options();
		}
		if ($node -> getNType() == 'vpcs') {
                        $output['data']['ethernet'] = $node -> getEthernetCount();
		}
	} else {
		// Node not found
		$output['code'] = 404;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][20024];
	}
	return $output;
}

/**
 * Function to get all lab nodes.
 *
 * @param   Lab     $lab                Lab
 * @return  Array                       Lab nodes (JSend data)
 */
function apiGetLabNodes($lab,$html5,$username) {
	// Getting node(s)
	$nodes = $lab -> getNodes();

	// Printing nodes
	$output['code'] = 200;
	$output['status'] = 'success';
	$output['message'] = $GLOBALS['messages'][60026];
	$output['data'] = Array();
	if (!empty($nodes)) {
		foreach ($nodes as $node_id => $node) {
			$output['data'][$node_id] = Array(
				'console' => $node -> getConsole(),
				'delay' => $node -> getDelay(),
				'id' => $node_id,
				'left' => $node -> getLeft(),
				'icon' => $node -> getIcon(),
				'image' => $node -> getImage(),
				'name' => $node -> getName(),
				'ram' => $node -> getRam(),
				'status' => $node -> getStatus(),
				'template' => $node -> getTemplate(),
				'type' => $node -> getNType(),
				'top' => $node -> getTop(),
				'url' => $node -> getConsoleUrl($html5,$username),
				'config_list' => listNodeConfigTemplates(),
				'config' => $node->getConfig()
			);

			if ($node -> getNType() == 'iol') {
				$output['data'][$node_id]['ethernet'] = $node -> getEthernetCount();
				$output['data'][$node_id]['nvram'] = $node -> getNvram();
				$output['data'][$node_id]['ram'] = $node -> getRam();
				$output['data'][$node_id]['serial'] = $node -> getSerialCount();
			}

			if ($node -> getNType() == 'dynamips') {
				$output['data'][$node_id]['idlepc'] = $node -> getIdlePc();
				$output['data'][$node_id]['nvram'] = $node -> getNvram();
				$output['data'][$node_id]['ram'] = $node -> getRam();
				foreach ($node -> getSlot() as $slot_id => $module) {
					$output['data'][$node_id]['slot'.$slot_id] = $module;
				}
			}

			if ($node -> getNType() == 'qemu') {
				$output['data'][$node_id]['cpu'] = $node -> getCpu();
				$output['data'][$node_id]['ethernet'] = $node -> getEthernetCount();
				$output['data'][$node_id]['ram'] = $node -> getRam();
				$output['data'][$node_id]['uuid'] = $node -> getUuid();
				if ( $node -> getTemplate() == "bigip" || $node -> getTemplate() == "firepower6" || $node -> getTemplate() == "firepower" | $node -> getTemplate() == "linux") {
					$output['data'][$node_id]['firstmac'] = $node -> getFirstMac();
				}
			}

			if ($node -> getNType() == 'docker') {
				$output['data'][$node_id]['ethernet'] = $node -> getEthernetCount();
				$output['data'][$node_id]['ram'] = $node -> getRam();
				$output['data'][$node_id]['custom_console_port'] = $node -> getCustomConsolePort();
				$output['data'][$node_id]['docker_options'] = $node -> getDocker_options();
			}
			if ($node -> getNType() == 'vpcs') {
                                $output['data'][$node_id]['ethernet'] = $node -> getEthernetCount();
                        }
		}
	}
	return $output;
}

/**
 * Function to get all node interfaces.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $id                 Node ID
 * @return  Array                       Node interfaces (JSend data)
 */
function apiGetLabNodeInterfaces($lab, $id) {
	// Getting node
	if (isset($lab -> getNodes()[$id])) {
		$node = $lab -> getNodes()[$id];

		// Printing node
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][60025];
		$output['data'] = Array();
		// Addint node type to properly sort IOL interfaces
		$output['data']['id'] = (int)$id;
		$output['data']['sort'] = $lab -> getNodes()[$id] -> getNType();

		// Getting interfaces
		$ethernets = Array();
		foreach ($lab -> getNodes()[$id] -> getEthernets() as $interface_id => $interface) {
			$ethernets[$interface_id] = Array(
				'name' => $interface -> getName(),
				'network_id' => $interface -> getNetworkId()
			);
		}
		$serials = Array();
		foreach ($lab -> getNodes()[$id] -> getSerials() as $interface_id => $interface) {
			$remoteId = $interface -> getRemoteId();
			$remoteIf = $interface -> getRemoteIf();
			$serials[$interface_id] = Array(
				'name' => $interface -> getName(),
				'remote_id' =>$remoteId,
				'remote_if' => $remoteIf,
				'remote_if_name' => $remoteId?$lab -> getNodes()[$remoteId]-> getSerials()[$remoteIf]-> getName():'',
			);
		}

		$output['data']['ethernet'] = $ethernets;
		$output['data']['serial'] = $serials;

		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][60030];
	} else {
		// Node not found
		$output['code'] = 404;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][20024];
	}
	return $output;
}

/**
 * Function to get node template.
 *
 * @param   Array   $p                  Parameters
 * @return  Array                       Node template (JSend data)
 */
function apiGetLabNodeTemplate($p) {
	// Check mandatory parameters
	if (!isset($p['type']) || !isset($p['template'])) {
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][60033];
		return $output;
	}

	// TODO must check lot of parameters
	$output['code'] = 200;
	$output['status'] = 'success';
	$output['message'] = $GLOBALS['messages'][60032];
	$output['data'] = Array();
	$output['data']['options'] = Array();

	// Name
	$output['data']['description'] = $GLOBALS['node_templates'][$p['template']];

	// Type
	$output['data']['type'] = $p['type'];

	// Image
	if ($p['type'] != 'vpcs') {	
	$node_images = listNodeImages($p['type'], $p['template']);
		if (empty($node_images)) {
			$output['data']['options']['image'] = Array(
				'name' => $GLOBALS['messages'][70002],
				'type' => 'list',
				'value' => '',
				'list' => Array()
			);
		} else {
			$output['data']['options']['image'] = Array(
				'name' => $GLOBALS['messages'][70002],
				'type' => 'list',
				'list' => $node_images
			);
			if (isset($p['image'])) {
				$output['data']['options']['image']['value'] =  $p['image'];
			} else {
				$output['data']['options']['image']['value'] =  end($node_images);
			}
		}
	}
	// Node Name/Prefix
	$output['data']['options']['name'] = Array(
		'name' => $GLOBALS['messages'][70000],
		'type' => 'input',
		'value' => $p['name']
	);

	// Icon
	$output['data']['options']['icon'] = Array(
		'name' => $GLOBALS['messages'][70001],
		'type' => 'list',
		'value' => $p['icon'],
		'list' => listNodeIcons()
	);

	// UUID
	if ($p['type'] == 'qemu') $output['data']['options']['uuid'] = Array(
		'name' => $GLOBALS['messages'][70008],
		'type' => 'input',
		'value' => ''
	);
        // CPULimit
        if ($p['type'] == 'qemu') $output['data']['options']['cpulimit'] = Array(
                'name' => $GLOBALS['messages'][70037],
                'type' => 'checkbox',
                'value' => $p['cpulimit']
        );
	// CPU
	if ($p['type'] == 'qemu') $output['data']['options']['cpu'] = Array(
		'name' => $GLOBALS['messages'][70003],
		'type' => 'input',
		'value' => $p['cpu']
	);

	// Idle PC
	if ($p['type'] == 'dynamips') $output['data']['options']['idlepc'] = Array(
		'name' => $GLOBALS['messages'][70009],
		'type' => 'list',
		'value' => $p['idlepc'],
		'list' => $p['idlepc_modules']
	);

	// NVRAM
	if (in_array($p['type'], Array('dynamips', 'iol'))) $output['data']['options']['nvram'] = Array(
		'name' => $GLOBALS['messages'][70010],
		'type' => 'input',
		'value' => $p['nvram']
	);

	// RAM
	if (in_array($p['type'], Array('dynamips', 'iol', 'qemu', 'docker'))) $output['data']['options']['ram'] = Array(
		'name' => $GLOBALS['messages'][70011],
		'type' => 'input',
		'value' => $p['ram']
	);

	// Slots
	if ($p['type'] == 'dynamips') {
		foreach ($p as $key => $module) {
			if (preg_match('/^slot[0-9]+$/', $key)) {
				// Found a slot
				$slot_id = substr($key, 4);
				$output['data']['options']['slot'.$slot_id] = Array(
					'name' => $GLOBALS['messages'][70016].' '.$slot_id,
					'type' => 'list',
					'value' => $p['slot'.$slot_id],
					'list' => $p['modules']
				);
            }
        }
	}

	// Ethernet
	if (in_array($p['type'], Array('qemu', 'docker'))) $output['data']['options']['ethernet'] = Array(
		'name' => $GLOBALS['messages'][70012],
		'type' => 'input',
		'value' => $p['ethernet']
	);
	if ($p['type'] == 'iol') $output['data']['options']['ethernet'] = Array(
		'name' => $GLOBALS['messages'][70018],
		'type' => 'input',
		'value' => $p['ethernet']
	);

	// First Mac
        if ($p['template'] == "bigip" || $p['template'] == "firepower6" || $p['template'] == "firepower" || $p['template'] == "linux") $output['data']['options']['firstmac'] =  Array(
                'name' => $GLOBALS['messages'][70021],
                'type' => 'input',
                'value' => ( isset($p['firstmac'])?$p['firstmac']:"") 
        );
		
		
	// Timos Options
	if ($p['template'] == "oldtimos" ) {
			$output['data']['options']['management_address'] =  Array(
				'name' => $GLOBALS['messages'][70031],
				'type' => 'input',
				'value' => ( isset($p['management_address'])?$p['management_address']:"")	);
	};
      
	// Timos Options CPM
	if ($p['template'] == "timoscpm" || $p['template'] == "timos" ) {
			$output['data']['options']['management_address'] =  Array(
				'name' => $GLOBALS['messages'][70031],
				'type' => 'input',
				'value' => ( isset($p['management_address'])?$p['management_address']:"")	);    

			$output['data']['options']['timos_line'] =  Array(
				'name' => $GLOBALS['messages'][70032],
				'type' => 'input',
				'value' => ( isset($p['timos_line'])?$p['timos_line']:"") );	        
	  
			$output['data']['options']['timos_license'] =  Array(
				'name' => $GLOBALS['messages'][70033],
				'type' => 'input',
				'value' => ( isset($p['timos_license'])?$p['timos_license']:"") );	    

	};
	// Timos Options IOM
	if ($p['template'] == "timosiom" ) {
			$output['data']['options']['timos_line'] =  Array(
				'name' => $GLOBALS['messages'][70032],
				'type' => 'input',
				'value' => ( isset($p['timos_line'])?$p['timos_line']:"") );	        
	};
        // Qemu Options
	if ($p['type'] == "qemu") {

                        $output['data']['options']['qemu_version'] =  Array(
                                'name' => $GLOBALS['messages'][70036],
                                'type' => 'list',
                                'value' =>( isset($p['qemu_version'])?$p['qemu_version']:""),
                                'list'  => Array ( '1.3.1' => '1.3.1' ,'2.0.2' => '2.0.2','2.2.0' => '2.2.0','2.4.0' => '2.4.0','2.5.0' => '2.5.0','2.6.2' => '2.6.2','2.12.0' => '2.12.0', '' => 'tpl'.( isset($p['qemu_version'])?'('.$p['qemu_version'].')':"(default 2.4.0)")));
                        $output['data']['options']['qemu_arch'] =  Array(
                                'name' => $GLOBALS['messages'][70034],
                                'type' => 'list',
                                'value' =>( isset($p['qemu_arch'])?$p['qemu_arch']:""), 
				'list'  => Array ( 'i386' => 'i386' ,'x86_64' => 'x86_64', '' => 'tpl'.( isset($p['qemu_arch'])?'('.$p['qemu_arch'].')':"")));
                        $output['data']['options']['qemu_nic'] =  Array(
                                'name' => $GLOBALS['messages'][70035],
                                'type' => 'list',
                                'value' => ( isset($p['qemu_nic'])?$p['qemu_nic']:"") ,
				'list' => Array ( 'virtio-net-pci' => 'virtio-net-pci' ,'e1000' => 'e1000','e1000-82545em' => 'e1000-82545em', 'vmxnet3' => 'vmxnet3', '' => 'tpl'.( isset($p['qemu_nic'])?'('.$p['qemu_nic'].')':"(e1000)")));

                        $output['data']['options']['qemu_options'] =  Array(
                                'name' => $GLOBALS['messages'][70030],
                                'type' => 'input',
                                'value' => ( isset($p['qemu_options'])?$p['qemu_options']:"") );
	};
	// Serial
	if ($p['type'] == 'iol') $output['data']['options']['serial'] = Array(
		'name' => $GLOBALS['messages'][70017],
		'type' => 'input',
		'value' => $p['serial']
	);

	// Startup configs
	if (in_array($p['type'], Array('dynamips', 'iol', 'qemu', 'docker','vpcs'))) {
		$output['data']['options']['config'] = Array(
			'name' => $GLOBALS['messages'][70013],
			'type' => 'list',
			'value' => '0',	// None
			'list' => listNodeConfigTemplates()
		);
		$output['data']['options']['config']['list'][0] = $GLOBALS['messages'][70020];	// None
		$output['data']['options']['config']['list'][1] = $GLOBALS['messages'][70019];	// Exported
	}

	// Delay
	$output['data']['options']['delay'] = Array(
		'name' => $GLOBALS['messages'][70014],
		'type' => 'input',
		'value' => 0
	);

	// Console
	if ($p['type'] == 'qemu') {
		$output['data']['options']['console'] = Array(
			'name' => $GLOBALS['messages'][70015],
			'type' => 'list',
			'value' => $p['console'],
			'list' => Array('telnet' => 'telnet', 'vnc' => 'vnc' , 'rdp' => 'rdp' )
		);
	}
   	if ($p['type'] == 'docker') {
                $output['data']['options']['console'] = Array(
                                'name' => $GLOBALS['messages'][70015],
                                'type' => 'list',
                                'value' => $p['console'],
                                'list' => Array('telnet' => 'telnet', 'vnc' => 'vnc' , 'rdp' => 'rdp' )
                );
                $output['data']['options']['custom_console_port'] = Array(
                                'name' => $GLOBALS['messages'][70038],
                                'type' => 'input',
                                'value' => $p['custom_console_port']
                );

                // adds to form for input on add node screen
                $output['data']['options']['docker_options'] =  Array(
                                'name' => $GLOBALS['messages'][79999],
                                'type' => 'input',
                                'value' => ( isset($p['docker_options'])?$p['docker_options']:"") );
	}
	// Dynamips options
	if ($p['type'] == 'dynamips') {
		$output['data']['dynamips'] = Array();
		if (isset($p['dynamips_options'])) $output['data']['dynamips']['options'] = $p['dynamips_options'];
	}

	// QEMU options
	if ($p['type'] == 'qemu') {
		$output['data']['qemu'] = Array();
		if (isset($p['qemu_arch'])) $output['data']['qemu']['arch'] = $p['qemu_arch'];
		if (isset($p['qemu_version'])) $output['data']['qemu']['version'] = $p['qemu_version'];
		if (isset($p['qemu_nic'])) $output['data']['qemu']['nic'] = $p['qemu_nic'];
		if (isset($p['qemu_options'])) $output['data']['qemu']['options'] = $p['qemu_options'];
	}

	if ($p['type'] == 'docker') {
	    $output['data']['docker'] = Array();
	    if (isset($p['docker_options'])) $output['data']['docker']['options'] = $p['docker_options'];
    }

	return $output;
}

/**
 * Function to start a single node.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $id                 Node ID
 * @param   int     $tenant             Tenant ID
 * @return  Array                       Return code (JSend data)
 */
function apiStartLabNode($lab, $id, $tenant) {
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper';
	$cmd .= ' -a start';
	$cmd .= ' -T '.$tenant;
	$cmd .= ' -D '.$id;
	$cmd .= ' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';
	$cmd .= ' 2>> /opt/unetlab/data/Logs/unl_wrapper.txt';
	exec($cmd, $o, $rc);
	if ($rc == 0) {
		// Nodes started
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][80049];
	} else {
		// Failed to start
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to start all nodes.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $tenant             Tenant ID
 * @return  Array                       Return code (JSend data)
 */
function apiStartLabNodes($lab, $tenant) {
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper';
	$cmd .= ' -a start';
	$cmd .= ' -T '.$tenant;
	$cmd .= ' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';
	$cmd .= ' 2>> /opt/unetlab/data/Logs/unl_wrapper.txt';
	exec($cmd, $o, $rc);
	if ($rc == 0) {
		// Nodes started
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][80048];
	} else {
		// Failed to start
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to stop a single node.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $id                 Node ID
 * @param   int     $tenant             Tenant ID
 * @return  Array                       Return code (JSend data)
 */
function apiStopLabNode($lab, $id, $tenant) {
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper';
	$cmd .= ' -a stop';
	$cmd .= ' -T '.$tenant;
	$cmd .= ' -D '.$id;
	$cmd .= ' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';
	$cmd .= ' 2>> /opt/unetlab/data/Logs/unl_wrapper.txt';
	exec($cmd, $o, $rc);
	if ($rc == 0) {
		// Nodes started
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][80051];
	} else {
		// Failed to stop
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to stop all nodes.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $tenant             Tenant ID
 * @return  Array                       Return code (JSend data)
 */
function apiStopLabNodes($lab, $tenant) {
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper';
	$cmd .= ' -a stop';
	$cmd .= ' -T '.$tenant;
	$cmd .= ' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';
	$cmd .= ' 2>> /opt/unetlab/data/Logs/unl_wrapper.txt';
	exec($cmd, $o, $rc);
	if ($rc == 0) {
		// Nodes started
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][80050];
	} else {
		// Failed to start
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to wipe a single node.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $id                 Node ID
 * @param   int     $tenant             Tenant ID
 * @return  Array                       Return code (JSend data)
 */
function apiWipeLabNode($lab, $id, $tenant) {
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper';
	$cmd .= ' -a wipe';
	$cmd .= ' -T '.$tenant;
	$cmd .= ' -D '.$id;
	$cmd .= ' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';
	$cmd .= ' 2>> /opt/unetlab/data/Logs/unl_wrapper.txt';
	exec($cmd, $o, $rc);
	if ($rc == 0) {
		// Nodes started
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][80053];
	} else {
		// Failed to start
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}

/**
 * Function to wipe all nodes.
 *
 * @param   Lab     $lab                Lab
 * @param   int     $tenant             Tenant ID
 * @return  Array                       Return code (JSend data)
 */
function apiWipeLabNodes($lab, $tenant) {
	$cmd = 'sudo /opt/unetlab/wrappers/unl_wrapper';
	$cmd .= ' -a wipe';
	$cmd .= ' -T '.$tenant;
	$cmd .= ' -F "'.$lab -> getPath().'/'.$lab -> getFilename().'"';
	$cmd .= ' 2>> /opt/unetlab/data/Logs/unl_wrapper.txt';
	exec($cmd, $o, $rc);
	if ($rc == 0) {
		// Nodes started
		$output['code'] = 200;
		$output['status'] = 'success';
		$output['message'] = $GLOBALS['messages'][80052];
	} else {
		// Failed to start
		$output['code'] = 400;
		$output['status'] = 'fail';
		$output['message'] = $GLOBALS['messages'][$rc];
	}
	return $output;
}
?>
