<?php
if(apcu_fetch('cron10')<time-10){
	$prevrun=apcu_fetch('cron10');
	apcu_store('cron10',time);
	$maxtime=apcu_fetch('maxtime');
	if(time-$prevrun>$maxtime)apcu_store('maxtime',time-$prevrun);
	include('pass2php/verwarming.php');
	include('pass2php/verwarmingbadkamer.php');
	if($s['pirgarage']=='Off'&&apcu_fetch('tpirgarage')<time-150&&apcu_fetch('tpoort')<time-150&&apcu_fetch('tgarage')<time-150&&$s['garage']=='On'&&$s['lichten_auto']=='On'){
		sw(apcu_fetch('igarage'),'Off','licht garage');
		$s['garage']='Off';
	}
	if($s['garage']=='On'&&apcu_fetch('tgarage')<time-180){
		if($s['dampkap']=='Off')double(apcu_fetch('idampkap'),'On','dampkap');
	}elseif($s['garage']=='Off'&&apcu_fetch('tgarage')<time-600){
		if($s['dampkap']=='On')double(apcu_fetch('idampkap'),'Off','dampkap');
	}
	if(apcu_fetch('tpirinkom')<time-90&&apcu_fetch('tpirhall')<time-90&&apcu_fetch('tinkom')<time-90&&apcu_fetch('thall')<time-90&&$s['lichten_auto']=='On'){if($s['inkom']=='On')sw(apcu_fetch('iinkom'),'Off','licht inkom');if($s['hall']=='On')sw(apcu_fetch('ihall'),'Off','licht hall');}
	if(apcu_fetch('tpirkeuken')<time-90&&apcu_fetch('twasbak')<time-90&&$s['pirkeuken']=='Off'&&$s['wasbak']=='On'&&$s['werkblad']=='Off'&&$s['keuken']=='Off'&&$s['kookplaat']=='Off')sw(apcu_fetch('iwasbak'),'Off','wasbak pir keuken');
}

if(apcu_fetch('cron30')<time-29){
	apcu_store('cron30',time);
	$items=array('eettafel','zithoek','tobi','kamer','alex');
	foreach($items as $item){
		if($s[$item]!='Off'){
			$action=apcu_fetch('dimaction'.$item);
			if($action==1){$level=filter_var($s[$item],FILTER_SANITIZE_NUMBER_INT);$level=floor($level*0.95);if($level<2)$level=0;if($level==20)$level=19;sl(apcu_fetch('i'.$item),$level,$item);if($level==0)apcu_store('dimaction'.$item,0);}
			elseif($action==2){$level=filter_var($s[$item],FILTER_SANITIZE_NUMBER_INT);$level=$level+2;if($level==20)$level=21;if($level>30)$level=30;sl(apcu_fetch('i'.$item),$level,$item);if($level==30)apcu_store('dimaction'.$item,0);}
		}
	}
}

if(apcu_fetch('cron60')<time-59){
	apcu_store('cron60',time);
	$buiten_temp=apcu_fetch('buiten_temp');
	$stamp=sprintf("%s",date("Y-m-d H:i"));$living=$s['living_temp'];$badkamer=$s['badkamer_temp'];$kamer=$s['kamer_temp'];$tobi=$s['tobi_temp'];$alex=$s['alex_temp'];$zolder=$s['zolder_temp'];$s_living=$s['living_set'];$s_badkamer=$s['badkamer_set'];$s_kamer=$s['kamer_set'];$s_tobi=$s['tobi_set'];$s_alex=$s['alex_set'];if($s['brander']=='On')$brander=1;else $brander=0;if($s['badkamervuur']=='On')$badkamervuur=1;else $badkamervuur=0;
	$query="INSERT IGNORE INTO `temp` (`stamp`,`buiten`,`living`,`badkamer`,`kamer`,`tobi`,`alex`,`zolder`,`s_living`,`s_badkamer`,`s_kamer`,`s_tobi`,`s_alex`,`brander`,`badkamervuur`) VALUES ('$stamp','$buiten_temp','$living','$badkamer','$kamer','$tobi','$alex','$zolder','$s_living','$s_badkamer','$s_kamer','$s_tobi','$s_alex','$brander','$badkamervuur');";
	$db=new mysqli('localhost','kodi','kodi','domotica');if($db->connect_errno>0)die('Unable to connect to database [' . $db->connect_error . ']');if(!$result=$db->query($query))die('There was an error running the query ['.$query .' - ' . $db->error . ']');$db->close();

}

if(apcu_fetch('cron120')<time-119){
	apcu_store('cron120',time);
	$buienradar=apcu_fetch('buien');
	$Tregenpomp=apcu_fetch('tregenpomp');
	if($buienradar>0){
		$pomppauze=3600/max(array(1,($buienradar*20)));
		if($pomppauze>10800)$pomppauze=10800;
	}else $pomppauze=3600;
	if($s['regenpomp']=='On'&&$Tregenpomp<time-57)sw(apcu_fetch('iregenpomp'),'Off','regenpomp off, was on for '.convertToHours(time-$Tregenpomp));
	elseif($s['regenpomp']=='Off'&&$Tregenpomp<time-$pomppauze)sw(apcu_fetch('iregenpomp'),'On','regenpomp on, was off for '.convertToHours(time-$Tregenpomp));
	if($s['kodi']=='On'&&apcu_fetch('tkodi')<time-298){if(pingport('192.168.2.7',1597)==1){$prevcheck=apcu_fetch('check192.168.2.57:1597');if($prevcheck>0)apcu_store('check192.168.2.57:1597',0);}else{$check=apcu_fetch('check192.168.2.57:1597')+1;if($check>0)apcu_store('check192.168.2.57:1597',$check);if($check>=5)sw(apcu_fetch('ikodi'),'Off','kodi');}}
}

if(apcu_fetch('cron180')<time-179){
	apcu_store('cron180',time);
	$wu=json_decode(file_get_contents('http://api.wunderground.com/api/c41234567895e/conditions/q/BX/Beitem.json'),true);
	if(isset($wu['current_observation'])){
		$lastobservation=apcu_fetch('wu-observation');
		if(isset($wu['current_observation']['estimated']['estimated']))goto exitwunderground;
		elseif($wu['current_observation']['observation_epoch']<=$lastobservation)goto exitwunderground;
		else apcu_store('wu-observation',$wu['current_observation']['observation_epoch']);
		if($wu['current_observation']['feelslike_c']!=apcu_fetch('buiten_temp'))apcu_store('buiten_temp',$wu['current_observation']['feelslike_c']);
		if($wu['current_observation']['wind_kph']!=apcu_fetch('wind'))apcu_store('wind',$wu['current_observation']['wind_kph']);
		if($wu['current_observation']['wind_dir']!=apcu_fetch('wind_dir'))apcu_store('wind_dir',$wu['current_observation']['wind_dir']);
		apcu_store('icon',str_replace('http','https',$wu['current_observation']['icon_url']));
		//lg('Wunderground '.number_format($wu['current_observation']['feelslike_c'],1).'	'.number_format($wu['current_observation']['temp_c'],1).'	'.number_format($wu['current_observation']['wind_kph'],1).' '.number_format($wu['current_observation']['wind_gust_kph'],1));
	}
	exitwunderground:
	$rains=file_get_contents('http://gadgets.buienradar.nl/data/raintext/?lat=51.89&lon=4.11');
	$rains=str_split($rains,11);$totalrain=0;$aantal=0;
	foreach($rains as $rain){
		$aantal=$aantal+1;
		$totalrain=$totalrain+substr($rain,0,3);
		if($aantal==7)break;
	}
	$newbuien=pow(10,((($totalrain/7)-109)/32));
	if($newbuien<0.0004)$newbuien=0;
	if($newbuien!=apcu_fetch('buien'))apcu_store('buien',$newbuien);
}
if(apcu_fetch('cron300')<time-299){
	apcu_store('cron300',time);
	if($s['weg']=='Off'&&$s['slapen']=='Off'){if($s['GroheRed']=='Off')if(apcu_fetch('tslapen')<time-900)double(apcu_fetch('iGroheRed'),'On',$item);if($s['poortrf']=='Off')if(apcu_fetch('tslapen')<time-900)double(apcu_fetch('ipoortrf'),'On',$item);}
	if($s['meldingen']=='On'){
		$items=array('living_temp','badkamer_temp','kamer_temp','tobi_temp','alex_temp','zolder_temp');$avg=0;
		foreach($items as $item)$avg=$avg+$s[$item];$avg=$avg/6;
		foreach($items as $item){
			$temp=$s[$item];
			if($temp>$avg+5&&$temp>25){
				$msg='T '.$item.'='.$temp.'°C. AVG='.round($avg,1).'°C';
				if(apcu_fetch('alerttemp'.$item)<time-3598){telegram($msg,false,2);ios($msg);apcu_store('alerttemp'.$item,time);}
			}
			if(apcu_fetch('t'.$item)<time-21590){if(apcu_fetch('alerttempupd'.$item)<time-43190){telegram($item.' not updated');apcu_store('alerttempupd'.$item,time);}}}
		$devices=array('tobiZ','alexZ','livingZ','livingZZ','livingZE','kamerZ');
		foreach($devices as $device){
			if(apcu_fetch('t'.$device)<time-2000){if(apcu_fetch('nocom'.$device)<time-43190){telegram($device.' geen communicatie',true);apcu_store('nocom'.$device,time);}}}
		$buiten_temp=apcu_fetch('buiten_temp');
		if($s['weg']=='Off'&&$s['slapen']=='Off'){if(($buiten_temp>$s['kamer_temp']&&$buiten_temp>$s['tobi_temp']&&$buiten_temp>$s['alex_temp'])&&$buiten_temp>22&&($s['kamer_temp']>20||$s['tobi_temp']>20||$s['alex_temp']>20)&&($s['raamkamer']=='Open'||$s['raamtobi']=='Open'||$s['raamalex']=='Open'))if((int)apcu_fetch('timeramen')<time-43190){telegram('Ramen boven dicht doen, te warm buiten. Buiten = '.$buiten_temp.',kamer = '.$s['kamer_temp'].', Tobi = '.$s['tobi_temp'].', Alex = '.$s['alex_temp'],false,2);apcu_store('timeramen',time);}elseif(($buiten_temp<=$s['kamer_temp']||$buiten_temp<=$s['tobi_temp']||$buiten_temp<=$s['alex_temp'])&&($s['kamer_temp']>20||$s['tobi_temp']>20||$s['alex_temp']>20)&&($s['raamkamer']=='Closed'||$s['raamkamer']=='Closed'||$s['raamkamer']=='Closed'))if((int)apcu_fetch('timeramen')<time-43190){telegram('Ramen boven open doen, te warm binnen. Buiten = '.$buiten_temp.',kamer = '.$s['kamer_temp'].', Tobi = '.$s['tobi_temp'].', Alex = '.$s['alex_temp'],false,2);apcu_store('timeramen',time);}}
	}
	if($s['voordeur']=='On'&&apcu_fetch('tvoordeur')<time-598)sw(apcu_fetch('ivoordeur'),'Off','Voordeur uit');
	$nodes=json_decode(file_get_contents('http://127.0.0.1:8084/json.htm?type=openzwavenodes&idx=3'),true);
	if($nodes['NodesQueried']==1){
		$timehealnetwork=apcu_fetch('healnetwork');
		if($timehealnetwork<time-3600*24*7){$result=json_decode(file_get_contents('http://127.0.0.1:8084/json.htm?type=command&param=zwavenetworkheal&idx=3'),true);if($result['status']=="OK"){apcu_store('healnetwork',time);exit;}}
		$kamers=array('living','tobi','alex','kamer');
		foreach($kamers as $kamer)${'dif'.$kamer}=number_format($s[$kamer.'_temp']-$s[$kamer.'_set'],1);
		foreach($nodes['result'] as $node){
			if(in_array($node['NodeID'],array(2,3,4,5,6,7,8,9,10,11,12,13,14,15,17,18,19,20,22,23,25,26,27,29))){if($timehealnetwork<time-1800&&apcu_fetch('healnode-'.$node['Name'])<time-3600*24*7&&apcu_fetch('healnode')<time-300){$healnode=json_decode(file_get_contents('http://127.0.0.1:8084/json.htm?type=command&param=zwavenodeheal&idx=3&node='.$node['NodeID']),true);if($healnode['status']=="OK"){apcu_store('healnode',time);/*lg('     Heal Node '.$node['Name'].' started');*/apcu_store('healnode-'.$node['Name'],time);exit;}unset($healnode);}}
			/*if($node['Product_name']=='Z Thermostat 014G0013'){if(is_array($node['config'])){$confs=$node['config'];foreach($confs as $conf){if($conf['label']=='Wake-up Interval'){
				if($node['Name']=='LivingZ'){$Uwake=1200;if(time>=strtotime('17:00'))$Uwake=480;if($difliving<1)$Uwake=240;if($conf['value']!=$Uwake&&time>apcu_fetch('UwakeLivingZ')){$result=json_decode(file_get_contents('http://192.168.2.10:8084/json.htm?type=command&param=applyzwavenodeconfig&idx='.$node['idx'].'&valuelist=2000_'.base64_encode($Uwake).'_3001_VW5wcm90ZWN0ZWQ=_'),true);if($result['status']=='OK')apcu_store('UwakeLivingZ',time+$conf['value']);lg(' Update Wakeupinterval for '.$node['Name'].' from '.$conf['value'].' to '.$Uwake.'. difliving='.$difliving);}}
				elseif($node['Name']=='LivingZE'){$Uwake=1200;if(time>=strtotime('17:00'))$Uwake=480;if($difliving<1)$Uwake=240;if($conf['value']!=$Uwake&&time>apcu_fetch('UwakeLivingZE')){$result=json_decode(file_get_contents('http://192.168.2.10:8084/json.htm?type=command&param=applyzwavenodeconfig&idx='.$node['idx'].'&valuelist=2000_'.base64_encode($Uwake).'_3001_VW5wcm90ZWN0ZWQ=_'),true);if($result['status']=='OK')apcu_store('UwakeLivingZE',time+$conf['value']);lg(' Update Wakeupinterval for '.$node['Name'].' from '.$conf['value'].' to '.$Uwake.'. difliving='.$difliving);}}
				elseif($node['Name']=='LivingZZ'){$Uwake=1200;if(time>=strtotime('17:00'))$Uwake=480;if($difliving<1)$Uwake=240;if($conf['value']!=$Uwake&&time>apcu_fetch('UwakeLivingZZ')){$result=json_decode(file_get_contents('http://192.168.2.10:8084/json.htm?type=command&param=applyzwavenodeconfig&idx='.$node['idx'].'&valuelist=2000_'.base64_encode($Uwake).'_3001_VW5wcm90ZWN0ZWQ=_'),true);if($result['status']=='OK')apcu_store('UwakeLivingZZ',time+$conf['value']);lg(' Update Wakeupinterval for '.$node['Name'].' from '.$conf['value'].' to '.$Uwake.'. difliving='.$difliving);}}
				elseif($node['Name']=="KamerZ"){$Uwake=1200;if(time<strtotime('5:00')||time>strtotime('20:00'))$Uwake=600;if($difkamer<1)$Uwake=300;if($conf['value']!=$Uwake&&time>apcu_fetch('UwakeKamerZ')){$result=json_decode(file_get_contents('http://192.168.2.10:8084/json.htm?type=command&param=applyzwavenodeconfig&idx='.$node['idx'].'&valuelist=2000_'.base64_encode($Uwake).'_3001_VW5wcm90ZWN0ZWQ=_'),true);if($result['status']=='OK')apcu_store('UwakeKamerZ',time+$conf['value']);lg(' Update Wakeupinterval for '.$node['Name'].' from '.$conf['value'].' to '.$Uwake.'. difkamer='.$difkamer);}}
				elseif($node['Name']=="TobiZ"){$Uwake=1200;if($s['heating']=='On'){if(date('W')%2==1){if(date('N')==3){if(time>strtotime('20:00'))$Uwake=600;}elseif(date('N')==4){if(time<strtotime('5:00')||time>strtotime('20:00'))$Uwake=600;}elseif(date('N')==5){if(time<strtotime('5:00'))$Uwake=600;}}else{if(date('N')==3){if(time>strtotime('20:00'))$Uwake=600;}elseif(in_array(date('N'),array(4,5,6))){if(time<strtotime('5:00')||time>strtotime('20:00'))$Uwake=600;}elseif(date('N')==7){if(time<strtotime('5:00'))$Uwake=600;}}}if($diftobi<1)$Uwake=240;if($conf['value']!=$Uwake&&time>apcu_fetch('UwakeTobiZ')){$result=json_decode(file_get_contents('http://192.168.2.10:8084/json.htm?type=command&param=applyzwavenodeconfig&idx='.$node['idx'].'&valuelist=2000_'.base64_encode($Uwake).'_3001_VW5wcm90ZWN0ZWQ=_'),true);if($result['status']=='OK')apcu_store('UwakeTobiZ',time+$conf['value']);lg(' Update Wakeupinterval for '.$node['Name'].' from '.$conf['value'].' to '.$Uwake.'. diftobi='.$diftobi);}}
				elseif($node['Name']=="AlexZ"){$Uwake=1200;if(time<strtotime('5:00')||time>strtotime('18:00'))$Uwake=600;if($difalex<1)$Uwake=240;if($conf['value']!=$Uwake&&time>apcu_fetch('UwakeAlexZ')){$result=json_decode(file_get_contents('http://192.168.2.10:8084/json.htm?type=command&param=applyzwavenodeconfig&idx='.$node['idx'].'&valuelist=2000_'.base64_encode($Uwake).'_3001_VW5wcm90ZWN0ZWQ=_'),true);if($result['status']=='OK')apcu_store('UwakeAlexZ',time+$conf['value']);lg(' Update Wakeupinterval for '.$node['Name'].' from '.$conf['value'].' to '.$Uwake.'. difalex='.$difalex);}}
			}

			unset($confs,$conf);}}}*/
		}
	}else apcu_store('healnetwork',0);
	checkport('192.168.2.11',80);checkport('192.168.2.12',80);checkport('192.168.2.13',80);checkport('192.168.2.2',53);checkport('192.168.2.2',80);
	include('gcal/gcal.php');
}

if(apcu_fetch('cron600')<time-599){
	apcu_store('cron600',time);
	//if($s['water']=='On'&&apcu_fetch('twater')<time-3598))sw(apcu_fetch('iwater'),'Off');
	if($s['lichten_auto']=='Off')if(apcu_fetch('tlichten_auto')<time-10795)sw(apcu_fetch('ilichten_auto'),'Off','lichten_auto aan');
	if($s['meldingen']=='Off'&&apcu_fetch('tmeldingen')<time-10795)sw(apcu_fetch('imeldingen'),'On','meldingen');
	if(apcu_fetch('tpirliving')<time-14395&&apcu_fetch('tpirgarage')<time-14395&&apcu_fetch('tpirinkom')<time-14395&&apcu_fetch('tpirhall')<time-14395&&apcu_fetch('tslapen')<time-14395&&apcu_fetch('tweg')<time-14395&&$s['weg']=='Off'&&$s['slapen']=="Off"){sw(apcu_fetch('islapen'),'On');telegram('slapen ingeschakeld na 4 uur geen beweging',false,2);}
	if(apcu_fetch('tpirliving')<time-43190&&apcu_fetch('tpirgarage')<time-43190&&apcu_fetch('tpirinkom')<time-43190&&apcu_fetch('tpirhall')<time-43190&&apcu_fetch('tslapen')<time-43190&&apcu_fetch('tweg')<time-43190&&$s['weg']=='Off'&&$s['slapen']=="On"){sw(apcu_fetch('islapen'),'Off');sw(apcu_fetch('iweg'),'On','weg');telegram('weg ingeschakeld na 12 uur geen beweging',false,2);}
	if($s['weg']=='On'||$s['slapen']=='On'){
		if(apcu_fetch('tweg')>time-59||apcu_fetch('tslapen')>time-59)$uit=60;else $uit=900;
		if($s['weg']=='On'){
			$items=array('denon','bureel','tv','tvled','kristal','eettafel','zithoek','garage','terras','tuin','voordeur','keuken','werkblad','wasbak','kookplaat','sony','kamer','tobi','alex');
			foreach($items as $item)if($s[$item]!='Off'&&apcu_fetch('t'.$item)<time-600)sw(apcu_fetch('i'.$item),'Off',$item);
			$items=array('lichtbadkamer1','lichtbadkamer2','badkamervuur');
			foreach($items as $item)if($s[$item]!='Off'&&apcu_fetch('t'.$item)<time-600)sw(apcu_fetch('i'.$item),'Off',$item);
		}
		if($s['slapen']=='On'){
			$items=array('hall','bureel','denon','tv','tvled','kristal','eettafel','zithoek','garage','terras','tuin','voordeur','keuken','werkblad','wasbak','kookplaat');
			foreach($items as $item)if($s[$item]!='Off')sw(apcu_fetch('i'.$item),'Off',$item);
			$items=array('pirkeuken','pirgarage','pirinkom','pirhall');
			foreach($items as $item)if($s[$item]!='Off')ud(apcu_fetch('i'.$item),0,'Off');
		}
		$items=array('living','badkamer','kamer','tobi','alex');
		foreach($items as $item){${'setpoint'.$item}=apcu_fetch('setpoint'.$item);if(${'setpoint'.$item}!=0&&apcu_fetch('t'.$item)<time-3598)apcu_store('setpoint'.$item,0);}
		$items=array('tobi','living','kamer','alex');
		foreach($items as $item)if(apcu_fetch('t'.$item.'_set')<time-86398)ud(apcu_fetch('i'.$item.'_set'),0,$s[$item.'_set'],'Update '.$item);
		if(apcu_fetch('tweg')<time-57)if($s['poortrf']=='On')sw(apcu_fetch('ipoortrf'),'Off','Poort uit');
	}
	$items=array(5=>'keukenzolderg',6=>'wasbakkookplaat',7=>'werkbladtuin',8=>'inkomvoordeur',11=>'badkamer');
	foreach($items as $item => $name)if(apcu_fetch('refresh'.$item)<time-7198&&apcu_fetch('healnode')<time-900){RefreshZwave($item,'time',$name);break;}
	$items=array('living','badkamer','kamer','tobi','alex','zolder');
	foreach($items as $item){
		if($s[$item.'_temp']!=apcu_fetch($item.'_temp'))apcu_store($item.'_temp',$s[$item.'_temp']);
	}

}
/*if(apcu_fetch('cron3600')<time-3599){
	apcu_store('cron3600',time);
}*/
/*if(apcu_fetch('cron86400')<time-86399){
	apcu_store('cron86400',time);
}*/
if(apcu_fetch('cron604800')<time-604799){
	apcu_store('cron604800',time);
	$cron.=' + 604800';
	$domoticz=json_decode(file_get_contents(domoticz.'json.htm?type=devices&used=true'),true);
	if($domoticz){
		foreach($domoticz['result'] as $dom){
			$name=$dom['Name'];
			if(strtotime($dom['LastUpdate'])!=apcu_fetch('t'.$name))apcu_store('t'.$name,strtotime($dom['LastUpdate']));
			if($dom['idx']!=apcu_fetch('i'.$name))apcu_store('i'.$name,$dom['idx']);
		}
	}
}
//if($s['zwembadfilter']=='On'){if(apcu_fetch('tzwembadfilter') < time-14395&&time>strtotime("18:00")&&$s['zwembadwarmte']=='Off')sw(apcu_fetch('izwembadfilter'),'Off','zwembadfilter');}else{if(apcu_fetch('tzwembadfilter')<time-14395&&time>strtotime("12:00")&&time<strtotime("16:00"))sw(apcu_fetch('izwembadfilter'),'On','zwembadfilter');}if($s['zwembadwarmte']=='On'){if(apcu_fetch('tzwembadwarmte')<time-86398)sw(apcu_fetch('izwembadwarmte'),'Off','warmtepomp zwembad');if($s['zwembadfilter']=='Off')sw(apcu_fetch('izwembadfilter'),'On','zwembadfilter');}
/*$wind=$weer['wind'];
	$maxbuien=20;$maxwolken=80;$zonopen=1500;$zontoe=200;$zon=apcu_fetch('zon');
	if(in_array($weer['wind_dir'],array('W','S','SE')))$maxwind=6;
	else $maxwind=8;
	if($s['luifel']!='Open'&&($wind>=$maxwind||$buienradar>=$maxbuien||$zon)<$zontoe)){
		lg('  --- Luifel: Wind='.$wind.'|Buien='.round($buienradar,0).'|Zon='.$zon.'|Luifel='.$s['luifel'].'|Last='.apcu_fetch('tluifel'));
		if($wind>=$maxwind){sw(apcu_fetch('iluifel'),'Off');if(apcu_fetch('tluifel')<time-3598)sw(apcu_fetch('iluifel'),'Off');}
		elseif($buienradar>=$maxbuien){sw(apcu_fetch('iluifel'),'Off');if(apcu_fetch('tluifel')<time-3598)sw(apcu_fetch('iluifel'),'Off');}
		elseif($zon<$zontoe){sw(apcu_fetch('iluifel'),'Off');if(apcu_fetch('tluifel')<time-3598)sw(apcu_fetch('iluifel'),'Off');}
	}
	elseif($s['luifel']!='Closed'&&time>strtotime('10:25')&&$wind<$maxwind-1&&$buienradar<$maxbuien-1&&$s['living_temp']>22&&$zon>$zonopen&&apcu_fetch('tluifel')<time-598){
		lg('  --- Luifel: Wind='.$wind.'|Buien='.round($buienradar,0).'|Zon='.$zon.'|Luifel='.$s['luifel'].'|Last='.apcu_fetch('tluifel'));
		sw(apcu_fetch('iluifel'),'On',$msg);
	}
}*/
function setradiator($name,$dif,$koudst=false,$set){
	$setpoint=$set-ceil($dif*4);
	if($koudst==true)$setpoint=28.0;
	if($setpoint>28)$setpoint=28.0;elseif($setpoint<4)$setpoint=4.0;
	return round($setpoint,0).".0";
}
function checkport($ip,$port){if(pingport($ip,$port)==1){$prevcheck=apcu_fetch($ip.':'.$port);if($prevcheck>=3)telegram($ip.':'.$port.' online',true);if($prevcheck>0)apcu_store($ip.':'.$port,0);}else{$check=apcu_fetch($ip.':'.$port)+1;if($check>0)apcu_store($ip.':'.$port,$check);if($check==3)telegram($ip.':'.$port.' Offline',true);if($check%12==0)telegram($ip.':'.$port.' nog steeds Offline',true);}}
function pingport($ip,$port){$file=fsockopen($ip,$port,$errno,$errstr,10);$status=0;if(!$file)$status=-1;else{fclose($file);$status=1;}return $status;}
