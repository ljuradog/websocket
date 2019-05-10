<?php
//require_once 'class/HodecallWebsocket.php';
/**
 * @param PapilosAction $papilos Descripc
 */

require_once 'class/PapilosAction.php';
require_once 'config/config.php';

$papilos = new PapilosAction(PUERTO_ESCUCHA_WS);
while (TRUE) {                          
	$papilos->checkNewCx(); 
        if(!($messages = $papilos->checkForNewMessages()) === FALSE){
            if(ord($messages['mensaje']) !== 13){                               // En caso de ser un mensaje vacio, sólo Enter, no hace nada.
                $papilos->EjecutarMensaje($messages);
            }
        }
}
$papilos->closeSocket();
