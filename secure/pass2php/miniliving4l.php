<?php
$items=array('eettafel','zithoek','garage','inkom','hall','keuken','werkblad','wasbak','kookplaat');
foreach($items as $item)
	if(apcu_fetch('s'.$item)!='Off')
		sw($item,'Off');