<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'eibclient', 'class', 'eibd');
include_file('core', 'dpt', 'class', 'eibd');
class eibd extends eqLogic {
	public static function cron() {
		foreach(eqLogic::byType('eibd') as $Equipement){		
			if($Equipement->getIsEnable()){
				foreach($Equipement->getCmd('info') as $Commande){
					if(!$Commande->getConfiguration('eventOnly'))
						$Commande->execute();
				}
			}
		}
    }
	public function preInsert() {
		if (is_object(eqLogic::byLogicalId($this->getLogicalId(),'eibd')))     
			$this->setLogicalId('');
	}
	public function preSave() {
		$this->setLogicalId(trim($this->getLogicalId()));    
	}
	public function postSave() {
	}	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                                               //
	//                                                      Gestion des Template d'equipement                                                       // 
	//                                                                                                                                               //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	public static function devicesParameters($_device = '') {
		$path = dirname(__FILE__) . '/../config/devices';
		if (isset($_device) && $_device != '') {
			$files = ls($path, $_device . '.json', false, array('files', 'quiet'));
			if (count($files) == 1) {
				try {
					$content = file_get_contents($path . '/' . $files[0]);
					if (is_json($content)) {
						$deviceConfiguration = json_decode($content, true);
						return $deviceConfiguration[$_device];
					}
				} catch (Exception $e) {
					return array();
				}
			}
		}
		$files = ls($path, '*.json', false, array('files', 'quiet'));
		$return = array();
		foreach ($files as $file) {
			try {
				$content = file_get_contents($path . '/' . $file);
				if (is_json($content)) {
					$return = array_merge($return, json_decode($content, true));
				}
			} catch (Exception $e) {

			}
		}
		if (isset($_device) && $_device != '') {
			if (isset($return[$_device])) {
				return $return[$_device];
			}
			return array();
		}
		return $return;
	}
	public function applyModuleConfiguration($template) {
		if ($template == '') {
			$this->save();
			return true;
		}
		$device = self::devicesParameters($template);
		if (!is_array($device) || !isset($device['cmd'])) {
			return true;
		}
		if (isset($device['configuration'])) {
			foreach ($device['configuration'] as $key => $value) {
				$this->setConfiguration($key, $value);
			}
		}
		$cmd_order = 0;
		$link_cmds = array();
		foreach ($device['cmd'] as $command) {
			if (isset($device['cmd']['logicalId'])) {
				continue;
			}
			$cmd = null;
			foreach ($this->getCmd() as $liste_cmd) {
				if (isset($command['name']) && $liste_cmd->getName() == $command['name']) {
					$cmd = $liste_cmd;	
					break;
				}
			}
			try {
				if ($cmd == null || !is_object($cmd)) {
					$cmd = new eibdCmd();
					$cmd->setOrder($cmd_order);
					$cmd->setEqLogic_id($this->getId());
				} else {
					$command['name'] = $cmd->getName();
				}
				utils::a2o($cmd, $command);
				if (isset($command['value']) && $command['value']!="") {
					$CmdValue=cmd::byEqLogicIdCmdName($this->getId(),$command['value']);
					if(is_object($CmdValue))
						$cmd->setValue('#'.$CmdValue->getId().'#');
					else
						$cmd->setValue(null);
				}
				if (isset($command['configuration']['option1']) && $command['configuration']['option1']!="") {
					$CmdValue=cmd::byEqLogicIdCmdName($this->getId(),$command['configuration']['option1']);
					if(is_object($CmdValue))
						$cmd->setConfiguration('option1','#'.$CmdValue->getId().'#');
					else
						$cmd->setConfiguration('option1',null);
				}
				if (isset($command['configuration']['option2']) && $command['configuration']['option2']!="") {
					$CmdValue=cmd::byEqLogicIdCmdName($this->getId(),$command['configuration']['option2']);
					if(is_object($CmdValue))
						$cmd->setConfiguration('option2','#'.$CmdValue->getId().'#');
					else
						$cmd->setConfiguration('option2',null);
				}
				if (isset($command['configuration']['option3']) && $command['configuration']['option3']!="") {
					$CmdValue=cmd::byEqLogicIdCmdName($this->getId(),$command['configuration']['option3']);
					if(is_object($CmdValue))
						$cmd->setConfiguration('option3','#'.$CmdValue->getId().'#');
					else
						$cmd->setConfiguration('option3',null);
				}
				if (isset($command['configuration']['option4']) && $command['configuration']['option4']!="") {
					$CmdValue=cmd::byEqLogicIdCmdName($this->getId(),$command['configuration']['option4']);
					if(is_object($CmdValue))
						$cmd->setConfiguration('option4','#'.$CmdValue->getId().'#');
					else
						$cmd->setConfiguration('option4',null);
				}
				if (isset($command['configuration']['option5']) && $command['configuration']['option5']!="") {
					$CmdValue=cmd::byEqLogicIdCmdName($this->getId(),$command['configuration']['option5']);
					if(is_object($CmdValue))
						$cmd->setConfiguration('option5','#'.$CmdValue->getId().'#');
					else
						$cmd->setConfiguration('option5',null);
				}
				$cmd->save();
				$cmd_order++;
			} catch (Exception $exc) {
				error_log($exc->getMessage());
			}
		$this->save();
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                                               //
	//                                                      Recherche automatique passerelle                                                       // 
	//                                                                                                                                               //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	public static function SearchBroadcastGateway(){	
		$result=array();
		$ServerPort=1024;
		$ServerAddr=config::byKey('internalAddr');
		set_time_limit(0); 
		$BroadcastSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if (!$BroadcastSocket) {
			log::add('eibd', 'debug', "socket_create() failed: reason: " . socket_strerror(socket_last_error($BroadcastSocket)));
			return false;
		}
		
		while(!socket_bind($BroadcastSocket, '0.0.0.0', $ServerPort)) 
			$ServerPort++;
		if (!socket_set_option($BroadcastSocket, IPPROTO_IP, MCAST_JOIN_GROUP, array("group"=>"224.0.23.12","interface"=>0))) 
			{
			log::add('eibd', 'debug', "socket_set_option() failed: reason: " . socket_strerror(socket_last_error($BroadcastSocket)));
			return false;
			}
		log::add('eibd', 'debug', 'Envoi de la trame search request');
		$msg = "06".						// 06 HEADER_SIZE
		"10".					// 10 KNX/IP v1.0
		"0201" .			// servicetypeidentifier
		"000E".						// totallength,14octets
		
		//Host Protocol Address Information (HPAI)		
		"08".						// structure length
		"01".						//host protocol code, e.g. 01h, for UDP over IPv4
		bin2hex(inet_pton($ServerAddr)).					//192.168.0.49
		sprintf('%04x', $ServerPort);						//portnumberofcontrolendpoint
		
		$hex_msg = hex2bin($msg);
		$dataBrute='0x';
		foreach (unpack("C*", $hex_msg) as $Byte)
			$dataBrute.=sprintf('%02x',$Byte).' ';
		log::add('eibd', 'debug', 'Data emise: ' . $dataBrute);
		if (!$len = socket_sendto($BroadcastSocket, $hex_msg, strlen($hex_msg), 0, "224.0.23.12", 3671)) 
			{
			$lastError = "socket_sendto() failed: reason: " . socket_strerror(socket_last_error($BroadcastSocket));
			return false;
			}
		while(!isset($result['KnxIpGateway'])) { 
			$buf = '';
			socket_recvfrom($BroadcastSocket, $buf , 2048, 0, $name, $port);
			$ReadFrame= unpack("C*", $buf);
			$dataBrute='0x';
			foreach ($ReadFrame as $Byte)
				$dataBrute.=sprintf('%02x',$Byte).' ';
			log::add('eibd', 'debug', 'Data recus: ' . $dataBrute);		
			
			$HeaderSize=array_slice($ReadFrame,0,1)[0];
			$Header=array_slice($ReadFrame,0,$HeaderSize);
			$Body=array_slice($ReadFrame,$HeaderSize);
			switch (array_slice($Header,2,1)[0]){
				case 0x02:
					switch (array_slice($Header,3,1)[0]){
						case 0x02:
							$result['KnxIpGateway'] =	array_slice($Body,2,1)[0]
											.".".	array_slice($Body,3,1)[0]
											.".".	array_slice($Body,4,1)[0]
											.".".	array_slice($Body,5,1)[0];
							$KnxPortGateway =	array_slice($Body,6,2);
							$result['KnxPortGateway'] =$KnxPortGateway[0]<<8|$KnxPortGateway[1];
							$result['IndividualAddressGateWay']=array_slice($Body,12,1)[0]<<8|array_slice($Body,13,1);
							//$result['DeviceName']= self::Hex2String(array_slice($Body,32,4));
						break;
					}
				break;
			}
		}
		socket_close($BroadcastSocket);
		return $result;
		}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                                               //
	//                                                      Gestion du de la communication KNX                                                       // 
	//                                                                                                                                               //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	private static function parseread ($len,$buf){
		$buf = unpack("C*", $buf->buffer);
		if ($buf[1] & 0x3 || ($buf[2] & 0xC0) == 0xC0)
			log::add('eibd', 'error', "Error: Unknown APDU: ".$buf[1]."X".$buf[2]);
		else if (($buf[2] & 0xC0) == 0x00){
			//if ($len == 2)
				return array ("Read", null);
		}else if (($buf[2] & 0xC0) == 0x40){
			if ($len == 2)
				return array ("Reponse", $buf[2] & 0x3F);
			else
				return array ("Reponse", array_slice($buf, 2));
		}else if (($buf[2] & 0xC0) == 0x80){
			if ($len == 2)
				return array ("Write", $buf[2] & 0x3F);
			else
				return array ("Write", array_slice($buf, 2));
		}
		else{
			return array ("Read", null);
			log::add('eibd','debug','Valeur du Header '.$buf[2] & 0xC0);
		}
	}
    	private static function gaddrparse ($addr)	{
		$addr = explode("/", $addr);
		if (count ($addr) >= 3)
			$r =(($addr[0] & 0x1f) << 11) | (($addr[1] & 0x7) << 8) | (($addr[2] & 0xff));
		if (count ($addr) == 2)
			$r = (($addr[0] & 0x1f) << 11) | (($addr[1] & 0x7ff));
		if (count ($addr) == 1)
			$r = (($addr[1] & 0xffff));
		return $r;
	}
	private static function formatiaddr ($addr){
		return sprintf ("%d.%d.%d", ($addr >> 12) & 0x0f, ($addr >> 8) & 0x0f, $addr & 0xff);
	}
	private static function formatgaddr ($addr)	{
		if (config::byKey('level', 'eibd'))
			return sprintf ("%d/%d/%d", ($addr >> 11) & 0x1f, ($addr >> 8) & 0x07,$addr & 0xff);
		else
			return sprintf ("%d/%d", ($addr >> 11) & 0x1f,$addr & 0x7ff);
	}
	public static function EibdRead($addr){
		$host=config::byKey('EibdHost', 'eibd');
		$port=config::byKey('EibdPort', 'eibd');
		$EibdConnexion = new EIBConnection($host,$port);
		$addr = self::gaddrparse($addr);

		if ($EibdConnexion->EIBOpenT_Group ($addr, 0) == -1)
			throw new Exception(__('Erreur de connexion au Bus KNX', __FILE__));
		$val =  0 & 0x3f;
		$val |= 0x0000;
		$data = pack ("n", $val);
		$len = $EibdConnexion->EIBSendAPDU($data);
		if ($len == -1)
			throw new Exception(__('Impossible de lire la valeur', __FILE__));
		$loop=0;
		$return=null;
		while (1){
			$data = new EIBBuffer();
			$src = new EIBAddr();
			$len = $EibdConnexion->EIBGetAPDU_Src($data, $src);
			if ($len == -1)	
				throw new Exception(__('Impossible de lire la valeur', __FILE__));
			if ($len < 2)
				throw new Exception(__('Paquet Invalide', __FILE__));
			$buf = unpack("C*", $data->buffer);
			if ($buf[1] & 0x3 || ($buf[2] & 0xC0) == 0xC0){
				throw new Exception(__("Error: Unknown APDU: ".$buf[1]."X".$buf[2], __FILE__));
			}
			else if (($buf[2] & 0xC0) == 0x40){
				if ($len == 2)                     
					$return=$buf[2] & 0x3F;
				else
					$return=array_slice($buf, 2);
				break;
			}	
		}
		$EibdConnexion->EIBClose();
		return $return;
	}		
    	public static function EibdReponse($addr, $val){
		$host=config::byKey('EibdHost', 'eibd');
		$port=config::byKey('EibdPort', 'eibd');
		$EibdConnexion = new EIBConnection($host,$port);
		if(!is_array($val)){
			$val = ($val + 0) & 0x3f;
			$val |= 0x0040;
			$data = pack ("n", $val);
		}	else {
			$header = 0x0040;
			$data = pack ("n", $header);
			for ($i = 0; $i < count ($val); $i++)
				$data .= pack ("C", $val[$i]);
		}
		$addr = self::gaddrparse ($addr);
		$r = $EibdConnexion->EIBOpenT_Group ($addr, 1);
		if ($r == -1)
			return -1;
		$r = $EibdConnexion->EIBSendAPDU($data);
		if ($r == -1)
			return -1;
		$EibdConnexion->EIBClose();
		return true;
	}
    	public static function EibdWrite($addr, $val){
		$host=config::byKey('EibdHost', 'eibd');
		$port=config::byKey('EibdPort', 'eibd');
		$EibdConnexion = new EIBConnection($host,$port);
		if(!is_array($val))
		{
			$val = ($val + 0) & 0x3f;
			$val |= 0x0080;
			$data = pack ("n", $val);
		} else	{
			$header = 0x0080;
			$data = pack ("n", $header);
			for ($i = 0; $i < count ($val); $i++)
				$data .= pack ("C", $val[$i]);
		}
		$addr = self::gaddrparse ($addr);
		$r = $EibdConnexion->EIBOpenT_Group ($addr, 1);
		if ($r == -1)
			return -1;
		$r = $EibdConnexion->EIBSendAPDU($data);
		if ($r == -1)
			return -1;
		$EibdConnexion->EIBClose();
		return true;
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                                               //
	//                                                            Gestion du BusMonitor                                                              // 
	//                                                                                                                                               //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	public static function BusMonitor() {  
		if (config::byKey('initInfo', 'eibd'))	{
			log::add('eibd', 'debug', 'Initialisation de valeur des objets KNX');
			foreach(eqLogic::byType('eibd') as $Equipement)		{
				if ($Equipement->getIsEnable()){
					foreach($Equipement->getCmd('info') as $Commande)	{
						if ($Commande->getConfiguration('init')){
							$ga=$Commande->getLogicalId();
							$dpt=$Commande->getConfiguration('KnxObjectType');
							$inverse=$Commande->getConfiguration('inverse');
							log::add('eibd', 'debug', 'Lecture de ['.$Equipement->getName().']['.$Commande->getName().'] sur le GAD '.$ga);
							$DataBus=self::EibdRead($ga);
							$option=null;
							switch($dpt){
								case '229.001':
								$option=array(
									"ValInfField"=>$Commande->getConfiguration('option1'),
									"StatusCommande"=>$Commande->getConfiguration('option2'),
									);
								break;
								case '235.001':
								$option=array(
									"Tarif"=>$Commande->getConfiguration('option1'),
									"validityTarif"=>$Commande->getConfiguration('option2'),
									"validityActiveElectricalEnergy"=>$Commande->getConfiguration('option3')
									);
								break;
								case "x.001":
								$option=array(
									"Mode"=>$Commande->getConfiguration('option1'),
									);
								break;
							}
							$BusValue=Dpt::DptSelectDecode($dpt, $DataBus, $inverse,$option);
							log::add('eibd', 'debug', '['.$Equipement->getName().']['.$Commande->getName().'] => '.$BusValue);
							$Commande->setCollectDate(date('Y-m-d H:i:s'));
							$Commande->setConfiguration('doNotRepeatEvent', 1);
							$Commande->event($BusValue);
							$Commande->save();
						}
					}
				}
			}
		}
		log::add('eibd', 'debug', 'Lancement du Bus Monitor');
		$host=config::byKey('EibdHost', 'eibd');
		$port=config::byKey('EibdPort', 'eibd');
		
		log::add('eibd', 'debug', 'Connexion a EIBD sur le serveur '.$host.':'.$port);
		$conBusMonitor = new EIBConnection($host,$port);
		$buf = new EIBBuffer();		
		if ($conBusMonitor->EIBOpen_GroupSocket(0) == -1)
			log::add('eibd', 'error',$conBusMonitor->getLastError);		
		while(true) {    
			$src = new EIBAddr;
			$dest = new EIBAddr;
			$len = $conBusMonitor->EIBGetGroup_Src($buf, $src, $dest);
			if ($len != -1 && $len >= 2) {
				$mon = self::parseread($len,$buf);
				$KnxFrameInfo=array(
					"Mode"=>$mon[0],
					"Data"=>$mon[1],
					"AdrSource"=>$src->addr,
					"AdrGroup"=>$dest->addr
					);
				self::BusMonitorTraitement($KnxFrameInfo);
			}
			else
				break;
		}
		$conBusMonitor->EIBClose();		
		log::add('eibd', 'debug', 'Deconnexion a EIBD sur le serveur '.$host.':'.$port);	
	}
	public static function BusMonitorTraitement($data){
		$monitor=array("Mode"=>$data["Mode"]);
		$monitor['AdresseGroupe']= self::formatgaddr($data["AdrGroup"]);
		$monitor['AdressePhysique']= self::formatiaddr($data["AdrSource"]);
		if(is_array($data["Data"])){
			$monitor['data']='0x ';
			foreach ($data["Data"] as $Byte)
				$monitor['data'].=sprintf(' %02x',$Byte);
			}
		else
			$monitor['data']='0x '.$data["Data"];
		$commandes=cmd::byLogicalId(trim($monitor['AdresseGroupe']));
		if(count($commandes)>0){
			foreach($commandes as $Commande){
				$monitor['valeur']=trim(self::UpdateCommande($Commande,$data["Mode"],$data["Data"]));
			}
		}else {
			$dpt=Dpt::getDptFromData($data["Data"]);
			if($dpt!=false)
				$monitor['valeur']=Dpt::DptSelectDecode($dpt, $data["Data"]);
			else
				$monitor['valeur']="Impossible de converire la valeur";
			log::add('eibd', 'debug', 'Aucune commande avec l\'adresse de groupe  '.$monitor['AdresseGroupe'].' n\'a pas été trouvée');
			if (config::byKey('autoAddDevice', 'eibd') && $monitor['AdressePhysique'] != config::byKey('EibdGad', 'eibd')){
				log::add('eibd', 'debug', 'Création de la commande '.$monitor['AdresseGroupe']);
				$Equipement=self::AddEquipement('Equipement '.$monitor['AdressePhysique'],$monitor['AdressePhysique']);
				if($dpt!=false){
					$Commande=self::AddCommande($Equipement,'Nouvelle_Commande_'.$monitor['AdresseGroupe'],$monitor['AdresseGroupe'],'info',$dpt);
					$monitor['valeur']=trim(self::UpdateCommande($Commande,$data["Mode"],$data["Data"]));
				}
			}
		}
		self::addCacheMonitor($monitor);
	}
	public static function addCacheMonitor($_monitor) {
		$cache = cache::byKey('eibd::Monitor');
		$value = json_decode($cache->getValue('[]'), true);
		$value[] = array('datetime' => date('d-m-Y H:i:s'), 'monitor' => $_monitor);
		cache::set('eibd::Monitor', json_encode(array_slice($value, -250, 250)), 0);
	}
	public static function UpdateCommande($Commande,$Mode,$data){	
		$valeur='';
		$unite='';
		if (is_object($Commande)) {		
			$dpt=$Commande->getConfiguration('KnxObjectType');
			$inverse=$Commande->getConfiguration('inverse');
			if ($dpt!= 'aucun' && $dpt!= ''){	
				$option=null;
				switch($dpt){
					case '229.001':
					$option=array(
						"ValInfField"=>$Commande->getConfiguration('option1'),
						"StatusCommande"=>$Commande->getConfiguration('option2'),
						);
					break;
					case '235.001':
					$option=array(
						"Tarif"=>$Commande->getConfiguration('option1'),
						"validityTarif"=>$Commande->getConfiguration('option2'),
						"validityActiveElectricalEnergy"=>$Commande->getConfiguration('option3')
						);
					break;
					case "x.001":
					$option=array(
						"Mode"=>$Commande->getConfiguration('option1'),
						);
					break;
				}
				if($Mode=="Read"){
					if($Commande->getConfiguration('transmitReponse')){
						log::add('eibd', 'debug','Mode Read sur le GAD '.$Commande->getLogicalId().': Transmettre une data en réponse');
						$ActionValue=cmd::byId(str_replace('#','',$Commande->getConfiguration('ObjetTransmit')));
						if(is_object($ActionValue)){
							log::add('eibd', 'debug','Transmission sur le GAD '.$Commande->getLogicalId().' la valeur '.$ActionValue->execCmd());
							$ActionData= Dpt::DptSelectEncode($dpt, $ActionValue->execCmd(), $inverse,$option);
							self::EibdReponse($Commande->getLogicalId(), $ActionData);
						}
					}
				} else {
					log::add('eibd', 'debug',$Commande->getLogicalId().' : Décodage de la valeur avec le DPT :'.$dpt);
					$valeur=Dpt::DptSelectDecode($dpt, $data, $inverse, $option);
					$unite=Dpt::getDptUnite($dpt);
					if($Commande->getConfiguration('noBatterieCheck')){
						switch(explode($dpt,'.')[0]){
							case 1 :
								$valeur=$valeur*100;
							break;
						}
						$Commande->getEqlogic()->batteryStatus($valeur,date('Y-m-d H:i:s'));
					}
					if($Commande->getType() == 'info' && $Commande->getConfiguration('eventOnly')){
						log::add('eibd', 'debug',$Commande->getLogicalId().' : Mise a jours de la valeur : '.$valeur.$unite);
						$Commande->setCollectDate(date('Y-m-d H:i:s'));
						$Commande->setConfiguration('doNotRepeatEvent', 1);
						$Commande->event($valeur);
						$Commande->save();
					}
				}
			}else{
				$valeur='Aucun DPT n\'est associé a cette adresse';
			}
		} 
		return $valeur.$unite ;
	}
	public static function AddEquipement($Name,$_logicalId) 	{
			$Equipement = self::byLogicalId($_logicalId, 'eibd');
			if (is_object($Equipement)) {
				$Equipement->setIsEnable(1);
				$Equipement->save();
			} else {
				$Equipement = new eibd();
				$Equipement->setName($Name);
				$Equipement->setLogicalId($_logicalId);
				$Equipement->setObject_id(null);
				$Equipement->setEqType_name('eibd');
				$Equipement->setIsEnable(1);
				$Equipement->setIsVisible(1);
				$Equipement->save();
			}
			return $Equipement;
		}
	public static function AddCommande($Equipement,$Name,$_logicalId,$Type="info", $Dpt='') {
		$Commande = $Equipement->getCmd(null,$_logicalId);
		if (!is_object($Commande))
		{
			$VerifName=$Name;
			$Commande = new EibdCmd();
			$Commande->setId(null);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($Equipement->getId());
			$count=0;
			while (is_object(cmd::byEqLogicIdCmdName($Equipement->getId(),$VerifName)))
			{
				$count++;
				$VerifName=$Name.'('.$count.')';
			}
			$Commande->setName($VerifName);
			$Commande->setIsVisible(1);
			$Commande->setType($Type);
			$Commande->setUnite($unite);
			if ($Dpt!=''){
				if($Type=='info')
					$Commande->setSubType(Dpt::getDptInfoType($Dpt));
				else
					$Commande->setSubType(Dpt::getDptActionType($Dpt));
				$Commande->setUnite(Dpt::getDptUnite($Dpt));
				$Commande->setConfiguration('KnxObjectType',$Dpt);
			}
			else{
				if($Type=='info')
					$Commande->setSubType('string');
				else
					$Commande->setSubType('other');
				$Commande->setConfiguration('KnxObjectType','1.xxx');
			}
			$Commande->save();
		}
		return $Commande;
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                                               //
	//                                                            Gestion du logiciel EIBD                                                           // 
	//                                                                                                                                               //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	public static function deamonRunning() {			
		$result=exec("ps aux | grep eibd | grep -v grep | awk '{print $2}'",$result);	
		$cron = cron::byClassAndFunction('eibd', 'BusMonitor');
		if(is_object($cron)){
			if($result != ""&& $cron->getState()=="run"){
				return true;
			}
		}
        	return false;
    	}
	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'eibd_update';
		$return['progress_file'] = '/tmp/compilation_eibd_in_progress';
		if(file_exists('/etc/eibd/bcusdk_VERSION')&&file_exists('/etc/eibd/pthsem_VERSION')){
			if(exec("cat /etc/eibd/bcusdk_VERSION")=="v0.0.5.1" && exec("cat /etc/eibd/pthsem_VERSION")=="v2.0.8.1")
				$return['state'] = 'ok';
		}
	/*	if(file_exists('/etc/eibd/knxd_VERSION')){
			if(exec("cat /etc/eibd/knxd_VERSION")=="v0.10")
				$return['state'] = 'ok';
		}*/
		else
			$return['state'] = 'nok';
		return $return;
	}
	public static function dependancy_install() {
		if (file_exists('/tmp/compilation_eibd_in_progress')) {
			return;
		}
		log::remove('eibd_update');
		$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/install-eibd.sh';
		//$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/install-knxd.sh';
		$cmd .= ' >> ' . log::getPathToLog('eibd_update') . ' 2>&1 &';
		exec($cmd);
	}
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'eibd';	
		if(self::deamonRunning())
			$return['state'] = 'ok';
		else
			$return['state'] = 'nok';
		if(config::byKey('EibdPort', 'eibd')!=''&&config::byKey('EibdGad', 'eibd')!=''&&config::byKey('KNXgateway', 'eibd')!='')
			$return['launchable'] = 'ok';
		else
			$return['launchable'] = 'nok';
		return $return;
	}
	public static function deamon_start($_debug = false) {
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') 
			return;
		log::remove('eibd');
		self::deamon_stop();
		$cmd = 'sudo eibd --daemon=/var/log/eibd.log --pid-file=/var/run/eibd.pid -D -S -T --listen-tcp='.config::byKey('EibdPort', 'eibd').' --eibaddr='.config::byKey('EibdGad', 'eibd');
		switch(config::byKey('TypeKNXgateway', 'eibd')){
			case 'ip':
				$cmd .=' ip:'.config::byKey('KNXgateway', 'eibd');
			break;
			case 'ipt':
				$cmd .=' ipt:'.config::byKey('KNXgateway', 'eibd');
			break;
			case 'iptn':
				$cmd .=' iptn:'.config::byKey('KNXgateway', 'eibd');
			break;
			case 'ft12':
				$cmd .=' ft12:'.config::byKey('KNXgateway', 'eibd');
			break;
			case 'bcu1':
				$cmd .=' bcu1:'.config::byKey('KNXgateway', 'eibd');
			break;
			case 'tpuarts':
				$cmd .=' tpuarts:'.config::byKey('KNXgateway', 'eibd');
			break;
			case 'usb':
				$cmd .=' usb:'.config::byKey('KNXgateway', 'eibd');
			break;
		}
		exec($cmd);
		
		$cron = cron::byClassAndFunction('eibd', 'BusMonitor');
		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass('eibd');
			$cron->setFunction('BusMonitor');
			$cron->setEnable(1);
			$cron->setDeamon(1);
			$cron->setSchedule('* * * * *');
			$cron->setTimeout('999999');
			$cron->save();
		}
		$cron->start();
		$cron->run();
	}
	public static function deamon_stop() {
		exec('sudo pkill eibd');
		$cron = cron::byClassAndFunction('eibd', 'BusMonitor');
		if (is_object($cron)) {
			$cron->stop();
			$cron->remove();
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//                                                                                                                                               //
	//                                                            Gestion du  parser ETS                                                             // 
	//                                                                                                                                               //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	private function unzipKnxProj($dir,$File){
		if (!is_dir($dir)) 
			mkdir($dir);
		$zip = new ZipArchive(); 
		// On ouvre l’archive.
		if($zip->open($File) == TRUE)
		{
			$zip->extractTo($dir);
			$zip->close();
		}
	}
	private function SearchFolder($dir,$Folder){
		if ($dh = opendir($dir)) 
		{
			while (($file = readdir($dh)) !== false)
			{
				if (substr($file,0,2) == $Folder)
				{
					if (opendir($dir.$file)) 
						return $dir . $file;
					}
			}
			closedir($dh);
		}	
	}
	private function AddCommandeETSParse($Projet,$ComObjectInstanceRef,$Equipement,$type){
		foreach($ComObjectInstanceRef->getElementsByTagName($type) as $Commande){
			$GroupAddressRefId=$Commande->getAttribute('GroupAddressRefId');
			foreach($Projet->getElementsByTagName('GroupRange') as $GroupRange){
				foreach($GroupRange->getElementsByTagName('GroupAddress') as $GroupAddress){
					$GroupAddressName=$GroupAddress->getAttribute('Name');
					$GroupAddressId=$GroupAddress->getAttribute('Id');
					if ($GroupAddressId!=""){
						if ($GroupAddressId == $GroupAddressRefId){
							$addr=$GroupAddress->getAttribute('Address');
							$AdressGroup=sprintf( "%d/%d/%d", ($addr >> 11) & 0xf, ($addr >> 8) & 0x7, $addr & 0xff);
							if($type == 'send')
								$type='action';
							else
								$type='info';
							$newCommande=self::AddCommande($Equipement,$GroupAddressName,$AdressGroup,$type,$DatapointType);
							foreach(eqLogic::byLogicalId($AdressGroup) as $Cmd){
								if($Cmd!=$newCommande){
									if($Cmd->getType() == 'info'){
										$newCommande->setValue($Cmd->getId());
										$newCommande->save();
									} else {
										$Cmd->setValue($newCommande->getId());
										$Cmd->save();
									}
								}
							}
						}
					}
				}
			}
		}
	}
	public static function ParserEtsFile($File){
		$dir='../../../../tmp/knxproj/';
		self::unzipKnxProj($dir,$File);
		$ProjetFile=self::SearchFolder($dir,"P-").'/0.xml';
		$Projet = new DomDocument();
		if ($Projet->load($ProjetFile)){ // XML décrivant le projet
			foreach($Projet->getElementsByTagName('Area') as $Area){
				$AreaName=$Area->getAttribute('Name');
				$AreaAddress=$Area->getAttribute('Address');
				foreach($Area->getElementsByTagName('Line') as $Line){
					$LineAddress=$Line->getAttribute('Address');
					foreach($Line->getElementsByTagName('DeviceInstance') as $Device){
						$DeviceId=$Device->getAttribute('Id');
						$DeviceProductRefId=$Device->getAttribute('ProductRefId');
						if ($DeviceProductRefId != ''){
							$DeviceAddress=$Device->getAttribute('Address');
							$PhysicalAdress=$AreaAddress.'.'.$LineAddress.'.'.$DeviceAddress;
							$DossierCataloge=$dir . substr($DeviceProductRefId,0,6).'/Catalog.xml';
							$Cataloge = new DomDocument();
							if ($Cataloge->load($DossierCataloge)) {//XMl décrivant les équipements
								foreach($Cataloge->getElementsByTagName('CatalogItem') as $CatalogItem){
									if ($DeviceProductRefId==$CatalogItem->getAttribute('ProductRefId'))
										$DeviceName=$CatalogItem->getAttribute('Name'). " - ".$PhysicalAdress;
								}
							}
							else{
								$DeviceName= "No name - ".$PhysicalAdress;
							}
							//Creation d'un equipement dans Jeedom
							$Equipement=self::AddEquipement($DeviceName,$PhysicalAdress);
							foreach($Device->getElementsByTagName('ComObjectInstanceRefs') as $ComObjectInstanceRefs){
								foreach($ComObjectInstanceRefs->getElementsByTagName('ComObjectInstanceRef') as $ComObjectInstanceRef){
									$DataPointType=explode('-',$ComObjectInstanceRef->getAttribute('DatapointType'));
									if ($DataPointType[1] >0)
										$DatapointType=$DataPointType[1].'.'.sprintf('%1$03d',$DataPointType[2]);
									else
										$DatapointType='aucun';
									self::AddCommandeETSParse($Projet,$ComObjectInstanceRef,$Equipement,'Receive');
									self::AddCommandeETSParse($Projet,$ComObjectInstanceRef,$Equipement,'Send');
								}
							}
						}
					}
				}
			}
		}
		else
		{
			throw new Exception(__( 'Impossible d\'analyser le document '.$ProjetFile, __FILE__));
		}
	}
  }
class eibdCmd extends cmd {
    public function preSave() 	{ 
        if ($this->getConfiguration('KnxObjectType') == '') 
            throw new Exception(__('Le type de commande ne peut etre vide', __FILE__));
		/*if ($this->getLogicalId() == '') 
            throw new Exception(__('Le GAD ne peut etre vide', __FILE__));		*/	
		$this->setLogicalId(trim($this->getLogicalId()));    
    }
	public function execute($_options = null){
		$ga=$this->getLogicalId();
		$dpt=$this->getConfiguration('KnxObjectType');
		$option=null;
		switch($dpt){
			case '229.001':
			$option=array(
				"ValInfField"=>$this->getConfiguration('option1'),
				"StatusCommande"=>$this->getConfiguration('option2'),
				);
			break;
			case '235.001':
			$option=array(
				"Tarif"=>$this->getConfiguration('option1'),
				"validityTarif"=>$this->getConfiguration('option2'),
				"validityActiveElectricalEnergy"=>$this->getConfiguration('option3')
				);
			break;
			case "x.001":
			$option=array(
				"Mode"=>$this->getConfiguration('option1'),
				);
			break;
		}
		$inverse=$this->getConfiguration('inverse');
		switch ($this->getType()) {
            case 'action' :
				$Listener=cmd::byId(str_replace('#','',$this->getValue()));
				if (isset($Listener) && is_object($Listener)) 
					$inverse=$Listener->getConfiguration('inverse');
                switch ($this->getSubType()) {
                    case 'slider':    
						$ActionValue = $_options['slider'];
                        break;
                    case 'color':
						$ActionValue = $_options['color'];
                        break;
                    case 'message':
						$ActionValue = $_options['message'];
                        break;
                    case 'other':				
						if (isset($Listener) && is_object($Listener)) {
							$BusValue=$Listener->execCmd();
							if($this->getConfiguration('KnxObjectValue') == "")
								$ActionValue =Dpt::OtherValue($dpt,$BusValue);
							else
								$ActionValue =$this->getConfiguration('KnxObjectValue');
						} else
								$ActionValue =$this->getConfiguration('KnxObjectValue');
                        break;
					}
				$data= Dpt::DptSelectEncode($dpt, $ActionValue, $inverse,$option);
				$BusValue=Dpt::DptSelectDecode($dpt, $data, $inverse,$option);
				$WriteBusValue=eibd::EibdWrite($ga, $data);
				if ($WriteBusValue != -1 && isset($Listener) && is_object($Listener) && $ga==$Listener->getLogicalId()){
					$Listener->setCollectDate(date('Y-m-d H:i:s'));
					$Listener->setConfiguration('doNotRepeatEvent', 1);
					$Listener->event($BusValue);
					$Listener->save();
				}
                break;
			case 'info':
				$inverse=$this->getConfiguration('inverse');
				log::add('eibd', 'debug', 'Lecture sur le bus de l\'adresse de groupe : '. $ga);
				$DataBus=eibd::EibdRead($ga);	
				$BusValue=Dpt::DptSelectDecode($dpt, $DataBus, $inverse,$option);
				$this->setCollectDate(date('Y-m-d H:i:s'));
				$this->setConfiguration('doNotRepeatEvent', 1);
				$this->event($BusValue);
				$this->save();
				break;
		}
		return $BusValue;
	}
}

?>
