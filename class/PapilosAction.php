<?php
require_once 'Websocket.php';

/**
 * CTIAction es la clase encargada de la comunicación entre serverCti y los clientes
 * por websockets (Agentes, Paneles entre otros)
 * 
 * @property resource $socketPeticion Socket por el que llega la petición
 * @property array $mensaje Mensaje que contiene el Socket por el que se recibe y el mensaje en JSON 
 *                          [ 'socket' => resource, 'mensaje' => JSON(mensajeRecibido) ]
 * @property string $accion Almacena la acción recibida
 * @property array $agentes Clase de Agentes y sus funciones
 * 
 * @author Leonardo Jurado
 */
class PapilosAction extends \Websocket {
    
    /*    C O N S T A N T E S   D E   L A   C L A S E    */
    const nombreClase = 'PapilosAction';
    const llaveAccion = 'accion';
    const prefijoMetodoAccion = 'action';
    /*****************************************************/
    
    public $socketPeticion;
    public $ipPeticion;
    public $mensaje;
    public $accion;
    
    public $agentes;
    
    public function __construct($port) 
    {
        parent::__construct($port);
        $this->agentes =  array();
        
    }
    /**
     * Indica los parametros que debe tener cada accion
     */
    protected function parametrosAcciones($accion)
    {
        $parametros = array(
            'RegistrarAgente' => array('agente'),
        );
        
        if(key_exists($accion, $parametros)){
            return $parametros[$accion];
        } else {
            return array();
        }
    }
    
    /**
     * Ejecuta la acción si existe
     * @param array $mensaje
     */
    public function EjecutarMensaje($mensaje)
    {
        if($this->ValidarMensaje($mensaje) === TRUE){
            $accion = $this->accion;
            $this->$accion();
        }
    }
    
    /**
     * Registra un Agente en el CTI Server
     * @return type
     */
    public function actionRegistrarAgente()
    {
        if(!key_exists($this->mensaje->agente, $this->agentes)){                // Verifica si ya se ha registrado este agente en alguna conexion
            $this->agentes[$this->mensaje->agente] = array(                     // Registra al Agente en el Arreglo agentes
                    'sockets' => array(),
                    'llamada' => NULL,
                );
        }
                                                                                // Verifica si ya se encuentra el Socket en el arreglo sockets de agentes
        if(in_array($this->socketPeticion, $this->agentes[$this->mensaje->agente]['sockets']) === FALSE){
            $this->agentes[$this->mensaje->agente]['sockets'][] = $this->socketPeticion;
        }
        
        $socket = str_replace('Resource id #', '', $this->socketPeticion);   
        $this->datosConexiones[$socket]['agente'] = $this->mensaje->agente;     // Registra el agente en los datos de las conexiones
        
        
        $this->responseSolicitud("Se ha registrado " . $this->mensaje->agente);
        echo "[" . date('Y-m-d H:i:s') . "] [" . $this->ipPeticion .  "]: Se ha registrado " . $this->mensaje->agente . chr(10);
    }

    public function actionSumarOferta()
    {
        $this->broadcastAgente('agent123', '{"oferta":'.$this->mensaje->oferta.'}' , false);
    }
    
    
    /**
     * Envía un mensaje a todos los sockets con un agente especifico. Tiene la
     * opción de excluir el socket que origino la petición.
     * @param type $agente
     * @param type $mensaje
     * @param type $incluyeOrigen
     */
    public function broadcastAgente($agente, $mensaje, $incluyeOrigen = TRUE)
    {
        if(!key_exists($agente, $this->agentes)){               // En caso de que el agente aún no se encuentre registrado
            return;
        }
        
        $sockets = $this->agentes[$agente]['sockets'];
        
        if(!$incluyeOrigen){
            $keySocketOrigen = array_search($this->socketPeticion, $sockets);
            if(!($keySocketOrigen === FALSE)){
                unset($sockets[$keySocketOrigen]);
            }
        }
        
        foreach($sockets as $conexion){
            $this->sendMessage($conexion, $mensaje);
        }
    }
    
    /**
     * Envia mensaje al socket que envió la petición
     * @param array $mensaje mensaje['socket' => resource, 'ip' => string, 'mensaje' => JSON]
     */
    protected function responseSolicitud($mensaje)
    {
        $this->sendMessage($this->socketPeticion, $mensaje);
    }
    
    /**
     * Valida que la petición indique la acción y que la misma se encuentre
     * implementada.
     * @param array $mensaje mensaje['socket' => resource, 'ip' => string, 'mensaje' => JSON]
     * @return boolean
     */
    protected function ValidarMensaje($mensaje)
    {
        $this->socketPeticion = $mensaje['socket'];
        $this->ipPeticion = $mensaje['ip'];
        
        if(!is_null($mensajeJSON = json_decode($mensaje['mensaje'])) && key_exists(self::llaveAccion, $mensajeJSON) ){
            
            if($this->validarParametros($mensajeJSON, $this->parametrosAcciones($mensajeJSON->accion))){
                $this->accion = self::prefijoMetodoAccion . $mensajeJSON->accion;
                unset($mensajeJSON->accion);
                $this->mensaje = $mensajeJSON;
                return method_exists(self::nombreClase, $this->accion) ? TRUE : FALSE ;
            }
        }
        return FALSE;
        
    }
    
    /**
     * Verifica que el mensaje contenga los parametros indicados
     * @param object $mensajeJSON
     * @param array $parametros
     * @return boolean
     */
    protected function validarParametros($mensajeJSON, $parametros)
    {
        foreach($parametros as $parametro){
            if(!property_exists($mensajeJSON, $parametro)){
                echo "[" . date('Y-m-d H:i:s') . "] " . "Parametro que falta: " . $parametro . chr(10);                
                return FALSE;
            }
        }
        return TRUE;
    }
}
