<?php
class Dpt{
	public function DptSelectEncode ($dpt, $value, $inverse=false, $option=null){
		$All_DPT=self::All_DPT();
		$type= substr($dpt,0,strpos( $dpt, '.' ));
		switch ($type)
		{
		case "1":
			if ($value != 0 && $value != 1)
				{
				$ValeurDpt=$All_DPT["Boolean"][$dpt][Valeurs];
				$value = array_search($value, $ValeurDpt); 
				}
			if ($inverse)
				{
				if ($value == 0 )
					$value = 1;
				else
					$value = 0;
				}
			$data= $value;
			break;
		case "2":
			$data= $value;
			break;
		case "3":
			$ctrl = 1 ;
			if ($value > 0)
				$stepCode = abs($value) & 0x07;
			$data = $ctrl << 3 | $stepCode;
			break;
		case "5":
			switch ($dpt)
			{
				case "5.001":
					if ($inverse)
						$value=100-$value;
					$value = round(intval($value) * 255 / 100);
					break;
				case "5.003":
					if ($inverse)
						$value=360-$value;
					$value = round(intval($value) * 255 / 360);
					break;
				case "5.004":
					$value = round(intval($value) * 255);
					break;
			}
			$data= array($value);
			break;
		case "6":
			if ($value < 0)
				$value = (abs($value) ^ 0xff) + 1 ; # twos complement
			$data= array($value);
			break;
		case "7":
			$data= array(($value >> 8)&0xff, ($value& 0xff));
			break;
		case "8":
		  /*      if data >= 0x8000:
				data = -((data - 1) ^ 0xffff)  # invert twos complement
			else:
				data = data
			if self._dpt is self.DPT_DeltaTime10Msec:
				value = data * 10.
			elif self._dpt is self.DPT_DeltaTime100Msec:
				value =data * 100.
			elif self._dpt is self.DPT_Percent_V16:
				value = data / 100.
			else:
				value = data*/
			$data= array($value);
			break;
		case "9": 
			if($value<0)
			{
				$sign = 1;
				$value = - $value;
			}
			else
				$sign = 0;
			$value = $value * 100.0;
			$exp = 0;
			while ($value > 2047)
			{
				$exp ++;
				$value = $value / 2;
			}
			if ($sign)
				$value = - $value;
			$value = $value & 0x7ff;
			$data= array(($sign << 7) | (($exp & 0x0f)<<3)| (($value >> 8)&0x07), ($value& 0xff));
			break;
		case "10": 
			$value   = new DateTime($value);
			$wDay = $value->format('N');
			$hour = $value->format('H');
			$min = $value->format('i');
			$sec = $value->format('s');
			$data = array(($wDay << 5 )| $hour  , $min , $sec);
			break;
		case "11":
			$value   = new DateTime($value);
			$day = $value->format('d');
			$month = $value->format('m');
			$year = $value->format('y');
			$data = array($day,$month ,$year);
			break;
		case "12":
			$data= array($value);
			break;
		case "13":
		 if ($value < 0)
			   $value = (abs($value) ^ 0xffffffff) + 1 ; # twos complement
		   /* if self._dpt is self.DPT_Value_FlowRate_m3h:
				$data = int(round($value * 10000.))
			else*/
			$data= array(($value>>24) & 0xFF, ($value>>16) & 0xFF,($value>>8) & 0xFF,$value & 0xFF);
			break;
		case "14":
			$value = unpack("L",pack("f", $value)); 
			$data = array(($value[1]>>24)& 0xff, ($value[1]>>16)& 0xff, ($value[1]>>8)& 0xff,$value[1]& 0xff);
			break;
		case "16":
			$data=array();
			foreach(str_split($value) as $chr)
				$data[]=ord($chr);
			break;
		case "17":
			$data= array($value& 0x3f);
			break;
		case "18":
			$control=cmd::byId(str_replace('#','',$option["control"]));
			$data= array(($control << 8) & 0x80 | $value & 0x3f);
			break;
		case "19": 
			$value   = new DateTime($value);
			$wDay = $value->format('N');
				$hour = $value->format('H');
				$min = $value->format('i');
				$sec = $value->format('s');
				$day = $value->format('d');
				$month = $value->format('m');
				$year = $value->format('Y')-1900;
				$data = array($year,$month ,$day,($wDay << 5 )| $hour  , $min , $sec,0,0 );
			break;
		case "20":
			if ($dpt != "20.xxx")
				{
				$ValeurDpt=$All_DPT["8BitEncAbsValue"][$dpt]["Valeurs"];
				$value = array_search($value, $ValeurDpt); 
				}
			$data= array($value);
			break;
		case "229":
			if ($dpt != "229.001")
				{
				if ($value < 0)
				   $value = (abs($value) ^ 0xffffffff) + 1 ; 
				$ValInfField=cmd::byId(str_replace('#','',$option["ValInfField"]));
				$StatusCommande=cmd::byId(str_replace('#','',$option["StatusCommande"]));
				$data= array(($value>>24) & 0xFF, ($value>>16) & 0xFF,($value>>8) & 0xFF,$value & 0xFF,$ValInfField->execCmd(),$StatusCommande->execCmd());
				}
			break;
		case "235":
			if ($dpt != "235.001")
				{
				if ($value < 0)
				   $value = (abs($value) ^ 0xffffffff) + 1 ; 
				$TarifCommande=cmd::byId(str_replace('#','',$option["Tarif"]));
				$validityTarifCommande=cmd::byId(str_replace('#','',$option["validityTarif"]));
				$validityActiveElectricalEnergyCommande=cmd::byId(str_replace('#','',$option["validityActiveElectricalEnergy"]));
				$data= array(($value>>24) & 0xFF, ($value>>16) & 0xFF,($value>>8) & 0xFF,$value & 0xFF,$TarifCommande->execCmd(),($validityTarifCommande->execCmd()<< 1) & 0x02 | $validityActiveElectricalEnergyCommande->execCmd());
				}
			break;
			case "232":	
				$data= self::html2rgb($value);
			break;
			default:
				switch($dpt){
					case "x.001":
						if ($option["Mode"] !=''){		
							$Mode=cmd::byId(str_replace('#','',$option["Mode"]));
							if (is_object($Mode)){
								$Mode->setCollectDate(date('Y-m-d H:i:s'));
								$Mode->setConfiguration('doNotRepeatEvent', 1);
								$Mode->event(($data[0]>>1) & 0xEF);
								$Mode->save();
							}
						}
						$data= array(($Mode->execCmd()<< 1) & 0xEF | $value& 0x01);
					break;
				}
			break;
		};
		return $data;
	}
	public function DptSelectDecode ($dpt, $data, $inverse=false, $option=null){
		if ($inverse)
			log::add('eibd', 'debug','La commande sera inversée');
		$All_DPT=self::All_DPT();
		$type= substr($dpt,0,strpos( $dpt, '.' ));
		switch ($type)
		{
		case "1":
			$value = $data;		
			if ($inverse)
				{
				if ($value == 0 )
					$value = 1;
				else
					$value = 0;
				}
			break;
		case "2":
			$value = $data;	
			break;
		case "3": 
			$ctrl = ($data & 0x08) >> 3;
			$stepCode = $data & 0x07;
			if ($ctrl)
				$value = $stepCode;
			else 
				$value = -$stepCode;
			break;
		case "5":  
			switch ($dpt)
			{
				case "5.001":
					$value = round((intval($data[0]) * 100) / 255);
					if ($inverse)
						$value=100-$value;
					break;
				case "5.003":
					$value = round((intval($data[0]) * 360) / 255);
					if ($inverse)
						$value=360-$value;
					break;
				case "5.004":
					$value = round(intval($data[0]) / 255);
					break;
				default:
					$value = intval($data[0]);
					break;
			}     
			break;
		case "6":
			if ($data[0] >= 0x80)
				$value = -(($data[0] - 1) ^ 0xff);  # invert twos complement
			else
				$value = $data[0];
			break;
		case "7":
			$value = $data[0] << 8 | $data[1];
			break;
		case "8":
			if ($data[0] >= 0x8000)
				$data[0] = -(($data - 1) ^ 0xffff);  # invert twos complement
			$value = $data[0];
			break;
		case "9": 
			$exp = ($data[0] & 0x78) >> 3;
			$sign = ($data[0] & 0x80) >> 7;
			$mant = ($data[0] & 0x07) << 8 | $data[1];
			if ($sign)
				$sign = -1 << 11;
			else
				$sign = 0;
			$value = ($mant | $sign) * pow (2, $exp) * 0.01;   
			break;
		case "10": 
			$wDay =($data[0] >> 5) & 0x07;
			$hour =$data[0]  & 0x1f;
			$min = $data[1] & 0x3f;
			$sec = $data[2] & 0x3f;
			$value = /*new DateTime(*/$hour.':'.$min.':'.$sec;//);
			break;
		case "11":
			$day = $data[0] & 0x1f;
			$month = $data[1] & 0x0f;
			$year = $data[2] & 0x7f;
			if ($year<90)
				$year+=2000;
			else
				$year+=1900;
			$value =/* new DateTime(*/$day.'/'.$month.'/'.$year;//);
			break;
		case "12":
			$value = $data[0];
			break;
		case "13":
			$value = $data[0] << 24 | $data[1] << 16 | $data[2] << 8 | $data[3] ;
			if ($value >= 0x80000000)
				$value = -(($value - 1) ^ 0xffffffff);  # invert twos complement           
			break;
		case "14":
			$value= $data[0]<<24 |  $data[1]<<16 |  $data[2]<<8 |  $data[3]; 
			$value = unpack("f", pack("L", $value))[1];
			break;
		case "16":
			$value='';
			foreach($data as $chr)
					$value.=chr(($chr));
			break;
		
		case "17":
			$value = $data[0] & 0x3f;
			break;
		case "18":
			if ($option != null)	{
				//Mise a jours de l'objet Jeedom ValInfField
				if ($option["control"] !=''){	
					//log::add('eibd', 'debug', 'Mise a jours de l\'objet Jeedom ValInfField: '.$option["ValInfField"]);
					$control=cmd::byId(str_replace('#','',$option["control"]));
					if (is_object($control)){
						$ctrl = ($data[0] >> 7) & 0x01;
						log::add('eibd', 'debug', 'L\'objet '.$control->getName().' à été trouvé et vas etre mis a jours avec la valeur '. $ctrl);
						$control->setCollectDate(date('Y-m-d H:i:s'));
						$control->setConfiguration('doNotRepeatEvent', 1);
						$control->event($ctrl);
						$control->save();
					}
				}
			}
			$value = $data[0] & 0x3f;
			break;
		case "19":
			$year=$data[0]+1900;
			$month=$data[1];
			$day=$data[2];
			$wDay =($data[3] >> 5) & 0x07;
				$hour =$data[3]  & 0x1f;
				$min = $data[4] & 0x3f;
				$sec = $data[5] & 0x3f;
			$Fault=($data[6] >> 7) & 0x01;
			$WorkingDay=($data[6] >> 6) & 0x01;
			$noWorkingDay=($data[6] >> 5) & 0x01;
			$noYear=($data[6] >> 4) & 0x01;
			$noDate=($data[6] >> 3) & 0x01;
			$noDayOfWeek=($data[6] >> 2) & 0x01;
			$NoTime=($data[6] >> 1) & 0x01;
			$SummerTime=$data[6] & 0x01;
			$QualityOfClock=($data[7] >> 7) & 0x01;
			$value = new DateTime();
			$value->setDate($year ,$month ,$day );
			$value->setTime($hour ,$min ,$sec );	
			break;
		case "20":
			$value = $data[0];
			if ($dpt != "20.xxx")
				{
				if ($dpt == "20.102_2")
					{
					if (dechex($value)>0x80)
						$value = dechex($value)-0x80;
					if (dechex($value)>0x20)
						$value = dechex($value)-0x20;
					switch ($value)
						{
						case "1":
							$value ="Comfort";
							break;
						case "2":
							$value ="Standby";
							break;
						case "4":
							$value ="Night";
							break;
						case "8":
							$value ="Frost";
							break;
						}
					}
				else
					$value = $All_DPT["8BitEncAbsValue"][$dpt]["Valeurs"][$data[0]];
				}
			break;
		case "229":
			if ($dpt != "229.001")
				{
				/*if ($value < 0)
				   $value = (abs($value) ^ 0xffffffff) + 1 ; 
				$ValInfField=cmd::byId(str_replace('#','',$option["ValInfField"]));
				$StatusCommande=cmd::byId(str_replace('#','',$option["StatusCommande"]));
				$data= array(($value>>24) & 0xFF, ($value>>16) & 0xFF,($value>>8) & 0xFF,$value & 0xFF,$ValInfField->execCmd(),$StatusCommande->execCmd());*/
				$value = $data[0] << 24 | $data[1] << 16 | $data[2] << 8 | $data[3] ;
				if ($value >= 0x80000000)
					$value = -(($value - 1) ^ 0xffffffff);  # invert twos complement       
				if ($option != null)
					{
					//Mise a jours de l'objet Jeedom ValInfField
					if ($option["ValInfField"] !='' /*&& is_numeric($data[4])&& $data[4]!=''*/)
						{	
						//log::add('eibd', 'debug', 'Mise a jours de l\'objet Jeedom ValInfField: '.$option["ValInfField"]);
						$ValInfField=cmd::byId(str_replace('#','',$option["ValInfField"]));
						if (is_object($ValInfField))
							{
							$valeur=$data[4];
							log::add('eibd', 'debug', 'L\'objet '.$ValInfField->getName().' à été trouvé et vas etre mis a jours avec la valeur '. $valeur);
							$ValInfField->setCollectDate(date('Y-m-d H:i:s'));
							$ValInfField->setConfiguration('doNotRepeatEvent', 1);
							$ValInfField->event($valeur);
							$ValInfField->save();
							}
						}
					//Mise a jours de l'objet Jeedom StatusCommande
					if ($option["StatusCommande"] !='' /*&& is_numeric(($data[5]>>1) & 0x01)&& $data[5]!=''*/)
						{
						//log::add('eibd', 'debug', 'Mise a jours de l\'objet Jeedom StatusCommande: '.$option["StatusCommande"]);
						$StatusCommande=cmd::byId(str_replace('#','',$option["StatusCommande"]));
						if (is_object($StatusCommande))
							{
							$valeur=($data[5]>>1) & 0x01;
							log::add('eibd', 'debug', 'L\'objet '.$StatusCommande->getName().' à été trouvé et vas etre mis a jours avec la valeur '. $valeur);
							$StatusCommande->setCollectDate(date('Y-m-d H:i:s'));
							$StatusCommande->setConfiguration('doNotRepeatEvent', 1);
							$StatusCommande->event($valeur);
							$StatusCommande->save();
							}
						}
					}
				}
			break;
		case "235":
			if ($dpt == "235.001")
				{
				$value = $data[0] << 24 | $data[1] << 16 | $data[2] << 8 | $data[3] ;
				if ($value >= 0x80000000)
					$value = -(($value - 1) ^ 0xffffffff);  # invert twos complement       
				if ($option != null)
					{
					//Mise a jours de l'objet Jeedom Tarif
					if ($option["Tarif"] !='' /*&& is_numeric($data[4])&& $data[4]!=''*/)
						{	
						//log::add('eibd', 'debug', 'Mise a jours de l\'objet Jeedom Tarif: '.$option["Tarif"]);
						$TarifCommande=cmd::byId(str_replace('#','',$option["Tarif"]));
						if (is_object($TarifCommande))
							{
							$valeur=$data[4];
							log::add('eibd', 'debug', 'L\'objet '.$TarifCommande->getName().' à été trouvé et vas etre mis a jours avec la valeur '. $valeur);
							$TarifCommande->setCollectDate(date('Y-m-d H:i:s'));
							$TarifCommande->setConfiguration('doNotRepeatEvent', 1);
							$TarifCommande->event($valeur);
							$TarifCommande->save();
							}
						}
					//Mise a jours de l'objet Jeedom validityTarif
					if ($option["validityTarif"] !='' /*&& is_numeric(($data[5]>>1) & 0x01)&& $data[5]!=''*/)
						{
						//log::add('eibd', 'debug', 'Mise a jours de l\'objet Jeedom validityTarif: '.$option["validityTarif"]);
						$validityTarifCommande=cmd::byId(str_replace('#','',$option["validityTarif"]));
						if (is_object($validityTarifCommande))
							{
							$valeur=($data[5]>>1) & 0x01;
							log::add('eibd', 'debug', 'L\'objet '.$validityTarifCommande->getName().' à été trouvé et vas etre mis a jours avec la valeur '. $valeur);
							$validityTarifCommande->setCollectDate(date('Y-m-d H:i:s'));
							$validityTarifCommande->setConfiguration('doNotRepeatEvent', 1);
							$validityTarifCommande->event($valeur);
							$validityTarifCommande->save();
							}
						}
					//Mise a jours de l'objet Jeedom validityActiveElectricalEnergy
					if ($option["validityActiveElectricalEnergy"] !='' /*&& is_numeric($data[5]& 0x01) && $data[5]!=''*/)
						{
						//log::add('eibd', 'debug', 'Mise a jours de l\'objet Jeedom validityActiveElectricalEnergy: '.$option["validityActiveElectricalEnergy"]);
						$validityActiveElectricalEnergyCommande=cmd::byId(str_replace('#','',$option["validityActiveElectricalEnergy"]));		
						if (is_object($validityActiveElectricalEnergyCommande))
							{
							$valeur=$data[5] & 0x01;
							log::add('eibd', 'debug', 'L\'objet '.$validityActiveElectricalEnergyCommande->getName().' à été trouvé et vas etre mis a jours avec la valeur '. $valeur);
							$validityActiveElectricalEnergyCommande->setCollectDate(date('Y-m-d H:i:s'));
							$validityActiveElectricalEnergyCommande->setConfiguration('doNotRepeatEvent', 1);
							$validityActiveElectricalEnergyCommande->event($valeur);
							$validityActiveElectricalEnergyCommande->save();
							}
						}
					}
				}
			break;
			case "232":
				$value= self::rgb2html($data[0] << 16 ,$data[1] << 8 , $data[2]);
			break;
			default:
				switch($dpt){
					case "x.001":
					$value = $data[0]& 0x01;      
					if ($option != null){
						//Mise a jours de l'objet Jeedom Mode
						if ($option["Mode"] !=''){		
							$Mode=cmd::byId(str_replace('#','',$option["Mode"]));
							if (is_object($Mode)){
								$Mode->setCollectDate(date('Y-m-d H:i:s'));
								$Mode->setConfiguration('doNotRepeatEvent', 1);
								$Mode->event(($data[0]>>1) & 0xEF);
								$Mode->save();
							}
						}
					}
					break;
				}
			break;
		};
		return $value;
	}
	public function OtherValue ($dpt, $oldValue){
		$All_DPT=self::All_DPT();
		$type= substr($dpt,0,strpos( $dpt, '.' ));
		switch ($type)
		{
		case "1":
			if ($oldValue == 1)
				$value=0;
			else
				$value=1;
			break;
		case "2":
			break;
		case "3": 
			break;
		case "4": 
			break;
		case "5":  
			break;
		case "6":
			break;
		case "7":
			break;
		case "8":
			break;
		case "9": 
			$value=$oldValue+1;
			break;
		case "10": 
			$value = new DateTime();
			break;
		case "11":
			$value = new DateTime();
			break;
		case "12":
			break;
		case "13":
			break;
		case "14":
			break;
		case "15":
			break;
		case "16":
			break;
		case "17":
			break;
		case "18":
			break;
		case "19":
			break;
		case "20":
			$value=$oldValue+1;
			break;
		case "232":
			$value=$oldValue+1;
			break;
		};
		return $value;
	}
	private function html2rgb($color){
		if ($color[0] == '#')
			$color = substr($color, 1);
		if (strlen($color) == 6)
			list($r, $g, $b) = array($color[0].$color[1],
		$color[2].$color[3],
		$color[4].$color[5]);
		elseif (strlen($color) == 3)
			list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		else
			return false;
		$r = hexdec($r); 
		$g = hexdec($g);
		$b = hexdec($b);
		return array($r, $g, $b);
	}
	private function rgb2html($r, $g=-1, $b=-1)	{
		if (is_array($r) && sizeof($r) == 3)
			list($r, $g, $b) = $r;
		$r = intval($r); 
		$g = intval($g);
		$b = intval($b);
		
		$r = dechex($r<0?0:($r>255?255:$r));
		$g = dechex($g<0?0:($g>255?255:$g));
		$b = dechex($b<0?0:($b>255?255:$b));
		
		$color = (strlen($r) < 2?'0':'').$r;
		$color .= (strlen($g) < 2?'0':'').$g;
		$color .= (strlen($b) < 2?'0':'').$b;
		return '#'.$color;
	}
	public function getDptUnite($dpt){
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["Unite"];
				next($Type);
				}
			next($All_DPT);
			}
		return ;
		}
	public function getDptOption($dpt)	{
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["Option"];
				next($Type);
				}
			next($All_DPT);
			}
		return ;
		}
	public function getDptActionType($dpt)	{
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["ActionType"];
				next($Type);
				}
			next($All_DPT);
			}
		return ;
		}
	public function getDptInfoType($dpt)	{
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["InfoType"];
				next($Type);
				}
			next($All_DPT);
			}
		return ;
		}
	public function getDptGenericType($dpt)	{
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["GenericType"];
				next($Type);
				}
			next($All_DPT);
			}
		return ;
		}
	public function getDptFromData($data)	{
		if(!is_array($data))
			return "1.xxx";
		switch(count($data)){
			case 1:
				return "5.xxx";
			break;
			case 2:
				return "9.xxx";
			break;
			case 3:
				return "10.xxx";
			break;
			case 4:
				return "14.xxx";
			break;
			default:
				return false;
			break;
		}
	}
	public function All_DPT()	{
		return array (
		"Boolean"=> array(
			"1.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0, 1),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.001"=> array(
				"Name"=>"Switch",
				"Valeurs"=>array("Off", "On"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.002"=> array(
				"Name"=>"Boolean",
				"Valeurs"=>array("False", "True"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.003"=> array(
				"Name"=>"Enable",
				"Valeurs"=>array("Disable", "Enable"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.004"=> array(
				"Name"=>"Ramp",
				"Valeurs"=>array("No ramp", "Ramp"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.005"=> array(
				"Name"=>"Alarm",
				"Valeurs"=>array("No alarm", "Alarm"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.006"=> array(
				"Name"=>"Binary value",
				"Valeurs"=>array("Low", "High"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.007"=> array(
				"Name"=>"Step",
				"Valeurs"=>array("Decrease", "Increase"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.008"=> array(
				"Name"=>"Up/Down",
				"Valeurs"=>array("Up", "Down"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.009"=> array(
				"Name"=>"Open/Close",
				"Valeurs"=>array("Open", "Close"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.010"=> array(
				"Name"=>"Start",
				"Valeurs"=>array("Stop", "Start"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.011"=> array(
				"Name"=>"State",
				"Valeurs"=>array("Inactive", "Active"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.012"=> array(
				"Name"=>"Invert",
				"Valeurs"=>array("Not inverted", "Inverted"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.013"=> array(
				"Name"=>"Dimmer send-style",
				"Valeurs"=>array("Start/stop", "Cyclically"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.014"=> array(
				"Name"=>"Input source",
				"Valeurs"=>array("Fixed", "Calculated"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.015"=> array(
				"Name"=>"Reset",
				"Valeurs"=>array("No action", "Reset"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.016"=> array(
				"Name"=>"Acknowledge",
				"Valeurs"=>array("No action", "Acknowledge"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.017"=> array(
				"Name"=>"Trigger",
				"Valeurs"=>array("Trigger", "Trigger"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.018"=> array(
				"Name"=>"Occupancy",
				"Valeurs"=>array("Not occupied", "Occupied"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.019"=> array(
				"Name"=>"Window/Door",
				"Valeurs"=>array("Closed", "Open"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.021"=> array(
				"Name"=>"Logical function",
				"Valeurs"=>array("OR", "AND"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.022"=> array(
				"Name"=>"Scene A/B",
				"Valeurs"=>array("Scene A", "Scene B"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.023"=> array(
				"Name"=>"Shutter/Blinds mode",
				"Valeurs"=>array("Only move Up/Down", "Move Up/Down + StepStop"),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"1BitPriorityControl"=> array(
			"2.001"=> array(
				"Name"=>"DPT_Switch_Control",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.002"=> array(
				"Name"=>"DPT_Bool_Control",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.003"=> array(
				"Name"=>"DPT_Enable_Controll",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.004"=> array(
				"Name"=>"DPT_Ramp_Controll",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.005"=> array(
				"Name"=>"DPT_Alarm_Controll",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.006"=> array(
				"Name"=>"DPT_BinaryValue_Controll",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.007"=> array(
				"Name"=>"DPT_Step_Controll",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.010"=> array(
				"Name"=>"DPT_Start_Controll",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.011"=> array(
				"Name"=>"DPT_State_Controll",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.012"=> array(
				"Name"=>"DPT_Invert_Controll",
				"Valeurs"=>array(),
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"3BitControl"=> array(
			"3.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(-7, 7),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"3.007"=> array(
				"Name"=>"Dimming",
				"Valeurs"=>array(-7, 7),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"3.008"=> array(
				"Name"=>"Blinds",
				"Valeurs"=>array(-7, 7),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"8BitUnsigned"=> array(
			"5.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0, 255),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"5.001"=> array(
				"Name"=>"Scaling",
				"Valeurs"=>array(0, 100),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"5.003"=> array(
				"Name"=>"Angle",
				"Valeurs"=>array(0, 360),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°"),
			"5.004"=> array(
				"Name"=>"Percent (8 bit)",
				"Valeurs"=>array(0, 255),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"5.005"=> array(
				"Name"=>"Decimal factor",
				"Valeurs"=>array(0, 1),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ratio"),
			"5.006"=> array(
				"Name"=>"Tariff",
				"Valeurs"=>array(0, 254),
				"Unite"=>"ratio"),
			"5.010"=> array(
				"Name"=>"Unsigned count",
				"Valeurs"=>array(0, 255),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses")),
		"8BitSigned"=> array(
			"6.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(-128, 127),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"6.001"=> array(
				"Name"=>"Percent (8 bit)",
				"Valeurs"=>array(-128, 127),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"6.010"=> array(
				"Name"=>"Signed count",
				"Valeurs"=>array(-128, 127),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses")),	
		"2ByteUnsigned"=> array(
			"7.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"7.001"=> array(
				"Name"=>"Unsigned count",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses"),
			"7.002"=> array(
				"Name"=>"Time period (resol. 1ms)",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"7.003"=> array(
				"Name"=>"Time period (resol. 10ms)",
				"Valeurs"=>array(0, 655350),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"7.004"=> array(
				"Name"=>"Time period (resol. 100ms)",
				"Valeurs"=>array(0, 6553500),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"7.005"=> array(
				"Name"=>"Time period (resol. 1s)",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s"),
			"7.006"=> array(
				"Name"=>"Time period (resol. 1min)",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"min"),
			"7.007"=> array(
				"Name"=>"Time period (resol. 1h)",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"h"),
			"7.010"=> array(
				"Name"=>"Interface object property ID",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"7.011"=> array(
				"Name"=>"Length",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mm"),
			"7.012"=> array(
				"Name"=>"Electrical current",
				"Valeurs"=>array(0, 65535),
                                "InfoType"=>'numeric',
                                "ActionType"=>'slider',
				"Unite"=>"mA"),  # Add special meaning for 0 (create Limit object)
			"7.013"=> array(
				"Name"=>"Brightness",
				"Valeurs"=>array(0, 65535),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"lx")),
		"2ByteSigned"=> array(
			"8.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(-32768, 32767),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"8.001"=> array(
				"Name"=>"Signed count",
				"Valeurs"=>array(-32768, 32767),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses"),
			"8.002"=> array(
				"Name"=>"Delta time (ms)",
				"Valeurs"=>array(-32768, 32767),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"8.003"=> array(
				"Name"=>"Delta time (10ms)",
				"Valeurs"=>array(-327680, 327670),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"8.004"=> array(
				"Name"=>"Delta time (100ms)",
				"Valeurs"=>array(-3276800, 3276700),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"8.005"=> array(
				"Name"=>"Delta time (s)",
				"Valeurs"=>array(-32768, 32767),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s"),
			"8.006"=> array(
				"Name"=>"Delta time (min)",
				"Valeurs"=>array(-32768, 32767),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"min"),
			"8.007"=> array(
				"Name"=>"Delta time (h)",
				"Valeurs"=>array(-32768, 32767),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"h"),
			"8.010"=> array(
				"Name"=>"Percent (16 bit)",
				"Valeurs"=>array(-327.68, 327.67),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"8.011"=> array(
				"Name"=>"Rotation angle",
				"Valeurs"=>array(-32768, 32767),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°")),
		"2ByteFloat"=> array(
			"9.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(-671088.64, +670760.96),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"9.001"=> array(
				"Name"=>"Temperature",
				"Valeurs"=>array(-273., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°C"),
			"9.002"=> array(
				"Name"=>"Temperature difference",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K"),
			"9.003"=> array(
				"Name"=>"Temperature gradient",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K/h"),
			"9.004"=> array(
				"Name"=>"Luminous emittance",
				"Valeurs"=>array(0., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"lx"),
			"9.005"=> array(
				"Name"=>"Wind speed",
				"Valeurs"=>array(0., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m/s"),
			"9.006"=> array(
				"Name"=>"Air pressure",
				"Valeurs"=>array(0., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Pa"),
			"9.007"=> array(
				"Name"=>"Humidity",
				"Valeurs"=>array(0., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"9.008"=> array(
				"Name"=>"Air quality",
				"Valeurs"=>array(0., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ppm"),
			"9.010"=> array(
				"Name"=>"Time difference 1",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s"),
			"9.011"=> array(
				"Name"=>"Time difference 2",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"9.020"=> array(
				"Name"=>"Electrical voltage",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mV"),
			"9.021"=> array(
				"Name"=>"Electric current",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mA"),
			"9.022"=> array(
				"Name"=>"Power density",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W/m²"),
			"9.023"=> array(
				"Name"=>"Kelvin/percent",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K/%"),
			"9.024"=> array(
				"Name"=>"Power",
				"Valeurs"=>array(-670760., +670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kW"),
			"9.025"=> array(
				"Name"=>"Volume flow",
				"Valeurs"=>array(-670760., 670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"l/h"),
			"9.026"=> array(
				"Name"=>"Rain amount",
				"Valeurs"=>array(-670760., 670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"l/m²"),
			"9.027"=> array(
				"Name"=>"Temperature (°F)",
				"Valeurs"=>array(-459.6, 670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°F"),
			"9.028"=> array(
				"Name"=>"Wind speed (km/h)",
				"Valeurs"=>array(0., 670760.),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"km/h")),
		"Time"=> array(
			"10.xxx"=> array(
				"Name"=>"Generic", 
				"Valeurs"=>array(0, 16777215),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"10.001"=> array(
				"Name"=>"Time of day",
				"Valeurs"=>array(
					array(0, 0, 0, 0), 
					array(7, 23, 59, 59)),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Date"=> array(
			"11.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0, 16777215),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"11.001"=> array(
				"Name"=>"Date",
				"Valeurs"=>array(
					array(1, 1, 1969), 
					array(31, 12, 2068)),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"4ByteUnsigned"=> array(
			"12.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0, 4294967295),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"12.001"=> array(
				"Name"=>"Unsigned count",
				"Valeurs"=>array(0, 4294967295),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses")),
		"4ByteSigned"=> array(
			"13.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(-2147483648, 2147483647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"13.001"=> array(
				"Name"=>"Signed count",
				"Valeurs"=>array(-2147483648, 2147483647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses"),
			"13.001"=> array(
				"Name"=>"Flow rate",
				"Valeurs"=>array(-214748.3648, 214748.3647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m³/h"),
			"13.010"=> array(
				"Name"=>"Active energy",
				"Valeurs"=>array(-214748.3648, 214748.3647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W.h"),
			"13.011"=> array(
				"Name"=>"Apparent energy",
				"Valeurs"=>array(-214748.3648, 214748.3647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"VA.h"),
			"13.012"=> array(
				"Name"=>"Reactive energy",
				"Valeurs"=>array(-214748.3648, 214748.3647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"VAR.h"),
			"13.013"=> array(
				"Name"=>"Active energy (kWh)",
				"Valeurs"=>array(-214748.3648, 214748.3647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kW.h"),
			"13.014"=> array(
				"Name"=>"Apparent energy (kVAh)",
				"Valeurs"=>array(-214748.3648, 214748.3647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kVA.h"),
			"13.015"=> array(
				"Name"=>"Reactive energy (kVARh)",
				"Valeurs"=>array(-214748.3648, 214748.3647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kVAR.h"),
			"13.100"=> array(
				"Name"=>"Long delta time",
				"Valeurs"=>array(-214748.3648, 214748.3647),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s")),
		"4ByteFloat"=> array(
			"14.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"14.000"=> array(
				"Name"=>"Acceleration",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m/s²"),
			"14.001"=> array(
				"Name"=>"Acceleration, angular",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad/s²"),
			"14.002"=> array(
				"Name"=>"Activation energy",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J/mol"),
			"14.003"=> array(
				"Name"=>"Activity (radioactive)",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s⁻¹"),
			"14.004"=> array(
				"Name"=>"Amount of substance",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mol"),
			"14.005"=> array(
				"Name"=>"Amplitude",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"14.006"=> array(
				"Name"=>"Angle, radiant",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad"),
			"14.007"=> array(
				"Name"=>"Angle, degree",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°"),
			"14.008"=> array(
				"Name"=>"Angular momentum",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J.s"),
			"14.009"=> array(
				"Name"=>"Angular velocity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad/s"),
			"14.010"=> array(
				"Name"=>"Area",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m²"),
			"14.011"=> array(
				"Name"=>"Capacitance",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"F"),
			"14.012"=> array(
				"Name"=>"Charge density (surface)",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m²"),
			"14.013"=> array(
				"Name"=>"Charge density (volume)",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m³"),
			"14.014"=> array(
				"Name"=>"Compressibility",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m²/N"),
			"14.015"=> array(
				"Name"=>"Conductance",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"S"),
			"14.016"=> array(
				"Name"=>"Conductivity, electrical",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"S/m"),
			"14.017"=> array(
				"Name"=>"Density",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kg/m³"),
			"14.018"=> array(
				"Name"=>"Electric charge",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C"),
			"14.019"=> array(
				"Name"=>"Electric current",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A"),
			"14.020"=> array(
				"Name"=>"Electric current density",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A/m²"),
			"14.021"=> array(
				"Name"=>"Electric dipole moment",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Cm"),
			"14.022"=> array(
				"Name"=>"Electric displacement",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m²"),
			"14.023"=> array(
				"Name"=>"Electric field strength",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V/m"),
			"14.024"=> array(
				"Name"=>"Electric flux",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"c"),  # unit??? C
			"14.025"=> array(
				"Name"=>"Electric flux density",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m²"),
			"14.026"=> array(
				"Name"=>"Electric polarization",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m²"),
			"14.027"=> array(
				"Name"=>"Electric potential",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V"),
			"14.028"=> array(
				"Name"=>"Electric potential difference",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V"),
			"14.029"=> array(
				"Name"=>"Electromagnetic moment",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A.m²"),
			"14.030"=> array(
				"Name"=>"Electromotive force",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V"),
			"14.031"=> array(
				"Name"=>"Energy",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J"),
			"14.032"=> array(
				"Name"=>"Force",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N"),
			"14.033"=> array(
				"Name"=>"Frequency",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Hz"),
			"14.034"=> array(
				"Name"=>"Frequency, angular (pulsatance)",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad/s"),
			"14.035"=> array(
				"Name"=>"Heat capacity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J/K"),
			"14.036"=> array(
				"Name"=>"Heat flow rate",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W"),
			"14.037"=> array(
				"Name"=>"Heat quantity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J"),
			"14.038"=> array(
				"Name"=>"Impedance",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Ohm"),
			"14.039"=> array(
				"Name"=>"Length",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m"),
			"14.040"=> array(
				"Name"=>"Light quantity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J"),
			"14.041"=> array(
				"Name"=>"Luminance",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"cd/m²"),
			"14.042"=> array(
				"Name"=>"Luminous flux",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"lm"),
			"14.043"=> array(
				"Name"=>"Luminous intensity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"cd"),
			"14.044"=> array(
				"Name"=>"Magnetic field strengh",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A/m"),
			"14.045"=> array(
				"Name"=>"Magnetic flux",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Wb"),
			"14.046"=> array(
				"Name"=>"Magnetic flux density",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"T"),
			"14.047"=> array(
				"Name"=>"Magnetic moment",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A.m²"),
			"14.048"=> array(
				"Name"=>"Magnetic polarization",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"T"),
			"14.049"=> array(
				"Name"=>"Magnetization",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A/m"),
			"14.050"=> array(
				"Name"=>"Magnetomotive force",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A"),
			"14.051"=> array(
				"Name"=>"Mass",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kg"),
			"14.052"=> array(
				"Name"=>"Mass flux",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kg/s"),
			"14.053"=> array(
				"Name"=>"Momentum",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N/s"),
			"14.054"=> array(
				"Name"=>"Phase angle, radiant",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad"),
			"14.055"=> array(
				"Name"=>"Phase angle, degree",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°"),
			"14.056"=> array(
				"Name"=>"Power",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W"),
			"14.057"=> array(
				"Name"=>"Power factor",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"cos phi"),
			"14.058"=> array(
				"Name"=>"Pressure",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Pa"),
			"14.059"=> array(
				"Name"=>"Reactance",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Ohm"),
			"14.060"=> array(
				"Name"=>"Resistance",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Ohm"),
			"14.061"=> array(
				"Name"=>"Resistivity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Ohm.m"),
			"14.062"=> array(
				"Name"=>"Self inductance",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"H"),
			"14.063"=> array(
				"Name"=>"Solid angle",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"sr"),
			"14.064"=> array(
				"Name"=>"Sound intensity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W/m²"),
			"14.065"=> array(
				"Name"=>"Speed",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m/s"),
			"14.066"=> array(
				"Name"=>"Stress",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Pa"),
			"14.067"=> array(
				"Name"=>"Surface tension",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N/m"),
			"14.068"=> array(
				"Name"=>"Temperature, common",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°C"),
			"14.069"=> array(
				"Name"=>"Temperature, absolute",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K"),
			"14.070"=> array(
				"Name"=>"Temperature difference",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K"),
			"14.071"=> array(
				"Name"=>"Thermal capacity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J/K"),
			"14.072"=> array(
				"Name"=>"Thermal conductivity",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W/m/K"),
			"14.073"=> array(
				"Name"=>"Thermoelectric power",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V/K"),
			"14.074"=> array(
				"Name"=>"Time",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s"),
			"14.075"=> array(
				"Name"=>"Torque",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N.m"),
			"14.076"=> array(
				"Name"=>"Volume",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m³"),
			"14.077"=> array(
				"Name"=>"Volume flux",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m³/s"),
			"14.078"=> array(
				"Name"=>"Weight",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N"),
			"14.079"=> array(
				"Name"=>"Work",
				"Valeurs"=>array(-3.4028234663852886e+38, 3.4028234663852886e+38),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J")),
		"String"=> array(
			"16.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0, 5192296858534827628530496329220095),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"16.000"=> array(
				"Name"=>"String",
				"Valeurs"=>array(/*14 * (0,), 14 * (127,)*/),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"16.001"=> array(
				"Name"=>"String",
				"Valeurs"=>array(/*14 * (0,), 14 * (255,)*/),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Scene"=> array(
			"17.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"17.001"=> array(
				"Name"=>"Scene",
				"Valeurs"=>array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Scene Control"=> array(
			"18.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"18.001"=> array(
				"Name"=>"Scene Control",
				"Valeurs"=>array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"DateTime"=> array(
			"19.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"19.001"=> array(
				"Name"=>"DateTime",
				"Valeurs"=>array(),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"8BitEncAbsValue"=> array(
			"20.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0, 255),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"20.003"=> array(
				"Name"=>"Occupancy mode",
				"Valeurs"=>array("occupied","standby","not occupied"),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"20.102"=> array(
				"Name"=>"Heating mode",
				"Valeurs"=>array("Auto","Comfort","Standby","Night","Frost"),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"20.102_2"=> array(
				"Name"=>"MDT Heating mode",
				"Valeurs"=>array("Auto","Comfort","Standby","Night","Frost"),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"20.105"=> array(
				"Name"=>"Heating controle mode",
				"Valeurs"=>array("Auto","Heat","Morning Warmup","Cool","Night Purge","Precool","Off","Test","Emergency Heat","Fan only","Free Cool","Ice"),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"3Bytes"=> array(
			"232.600"=> array(
				"Name"=>"Colour RGB",
				"Valeurs"=>array(),
				"InfoType"=>'string',
				"ActionType"=>'color',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Other"=> array(
			"235.001"=> array(
				"Name"=>"Tarif ActiveEnergy",
				"Valeurs"=>array(),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"229.001"=> array(
				"Name"=>"Metering value",
				"Valeurs"=>array(),
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Spécifique"=> array(
			"x.001"=> array(
				"Name"=>"Hager Etat/Mode",
				"Valeurs"=>array(),
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""))
		);
	}
}?>
