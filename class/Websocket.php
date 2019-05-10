<?php

/**
 * Clase para la utilización de Websockets en la implementación del CTI
 * @author Leonardo Jurado
 */
class Websocket {
    
    /*    C O N S T A N T E S   D E   L A   C L A S E    */
    const direccionSocket = 0;                                                  // Verificar si tiene que ser Dirección IP
    const tamañoBuffer = 2048;
    const timeOutSec = 0;                                                       // Tiempo de espera en Segundos
    const timeOutUSec = 10;                                                     // Tiempo de espera en MicroSegundos
    const optVal = 1;
    const lenMinRec = 0;                                                        // Longitud Mínima del Mensaje para ser Atendida
    const cxWebsocket = 'Websocket';
    const cxTelnet = 'Telnet';
    
    
    public $socket;
    public $clients;
    public $changed;
    
    public $datosConexiones;

    /**
     * @param integer $port Puerto de escucha del WebSocket
     */
    public function __construct($port) {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);           // Crea TCP/IP sream socket
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, self::optVal);// Puerto Reusable
        socket_bind($this->socket, self::direccionSocket, $port);               // Asigna un Socket a un cliente específico
        socket_listen($this->socket);                                           // Deja en escucha

        /* Se inicializan los atributos del Servidor */
        $this->clients = array($this->socket);
        $this->datosConexiones = array();
    }
    
    /**
     * Lista los clientes activos
     * @return array
     */
    public function getClients() {
        return $this->clients;
    }

    public function closeSocket() {
        socket_close($this->socket);
    }

    /**
     * Verifica y establece conexiones nuevas
     */
    public function checkNewCx() {
        $write = $except = $ip = NULL;
        $this->changed = $this->clients;
                                                                                // returns the socket resources in $changed array
        socket_select($this->changed, $write, $except, self::timeOutSec, self::timeOutUSec);
        if (in_array($this->socket, $this->changed)) {
            $socket_new = socket_accept($this->socket);                         // accept new socket
            $this->clients[] = $socket_new;                                     // add socket to client array
            
            $header = socket_read($socket_new, self::tamañoBuffer);             // read data sent by the socket                                                         
            
            if(is_null($peticion = $this->perform_handshaking($header, $socket_new))){
                $this->datosConexiones[$socket_new]['cx'] = self::cxWebsocket;
            } else{
                $this->datosConexiones[$socket_new]['cx'] = self::cxTelnet;
            }
            
            socket_getpeername($socket_new, $ip);                               //get ip address of connected socket
            
            echo "[" . date('Y-m-d H:i:s') . "] " . "Nueva conexion: $ip" . chr(10);
            $found_socket = array_search($this->socket, $this->changed);
            unset($this->changed[$found_socket]);
        }
    }

    
    /**
     * Verifica las conexiones que presentaron alguna novedad en busca de nuevos
     * mensajes. Retorna False en caso de ningún mensaje o el mensaje recibido
     * @return mixed boolean/string
     */
    public function checkForNewMessages() {
        $buf = $ip = NULL;
        foreach ($this->changed as $changed_socket) {                           // Verifica el mensaje de entrada
            
            $cifrado = $this->verificaSiCodifica($changed_socket);
            
            while (socket_recv($changed_socket, $buf, self::tamañoBuffer, self::lenMinRec) >= 1) {
                socket_getpeername($changed_socket, $ip);                       // Obtiene la Ip del Socket que envió la petición
                $received_text = $cifrado ? $this->unmask($buf) : $buf;
                echo "[" . date('Y-m-d H:i:s') . "] [" . $ip . ']: Mensaje recibido: ' . $received_text . chr(10);
                return array(
                    'socket' => $changed_socket, 
                    'mensaje' => $received_text, 
                    'ip' => $ip);
            }

            $buf = @socket_read($changed_socket, self::tamañoBuffer, PHP_NORMAL_READ);
            if ($buf === false) {                                               // Verifica clientes desconectados
                echo "[" . date('Y-m-d H:i:s') . "] " . 'Error: ' . socket_strerror(socket_last_error()) . chr(10);
                $this->disconnectedSocketClient($changed_socket);
            }
        }
        return FALSE;
    }
    
    /**
     * Permite cerrar la conexión cuando se presenta un error en la lectura del
     * puerto.
     * @param string $changed_socket Socket que presentó la novedad y por ende
     * requiere ser desconectado
     */
    public function disconnectedSocketClient($changed_socket) {
        $ip = NULL;
        $found_socket = array_search($changed_socket, $this->clients);
        socket_getpeername($changed_socket, $ip);
        unset($this->clients[$found_socket]);
        echo "[" . date('Y-m-d H:i:s') . "] " . "Cierre de conexion: $ip" . chr(10);
    }

    /**
     * Envía el mensaje indicado. Teniendo en cuenta si se trata de una conexion
     * por Telnet o por Websocket el parámetro codificar debe ser FALSE o TRUE
     * @param string $destino Socket
     * @param string $mensaje Mensaje que se desea enviar
     * @param boolean $codificar Determina si es necesario codificar
     */
    public function sendMessage($destino, $mensaje) {
        $codificar = $this->verificaSiCodifica($destino);
        $mensajeSalida = $codificar ? $this->mask($mensaje) : $mensaje;
        socket_write($destino, $mensajeSalida, strlen($mensajeSalida));
    }
    
    protected function verificaSiCodifica($socket)
    {
        $numeroCliente = str_replace('Resource id #', '', $socket);
        if(key_exists($numeroCliente, $this->datosConexiones)){
            return $this->datosConexiones[$numeroCliente]['cx'] == self::cxWebsocket ? TRUE : FALSE;
        }
        return FALSE;
    }

    /**
     * Permite establecer la conexión del WebSocket de acuerdo al RFC6455
     * @param string $receved_header Encabezado de la nueva conexion
     * @param string $client_conn Conexión Involucrada
     * @return mixed NULL en caso de WS, string con encabezado en caso contrario
     */
    protected function perform_handshaking($receved_header, $client_conn) {
        $matches = $headers = array();
        $lines = preg_split("/\r\n/", $receved_header);
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }
        if (key_exists('Sec-WebSocket-Key', $headers)) {
            $secKey = $headers['Sec-WebSocket-Key'];
            $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .       // Hand Shaking header
                    "Upgrade: websocket\r\n" .
                    "Connection: Upgrade\r\n" .
                    "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
            $this->sendMessage($client_conn, $upgrade);
            return NULL;
        }
        //$this->sendMessage($client_conn, '<cross-domain-policy><allow-access-from domain="localhost" to-ports="*"/></cross-domain-policy>'.chr(0x00));
        $this->sendMessage($client_conn, 'Bienvenido'.chr(10));
        return $receved_header;
    }

    /**
     * Decodifica el contenido del mensaje
     * @param string $text
     * @return string
     */
    protected function unmask($text) {
        $length = ord($text[1]) & 127;
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } elseif ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text_response = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text_response .= $data[$i] ^ $masks[$i % 4];
        }
        return $text_response;
    }

    /**
     * Codifica el contenido del mensaje para que pueda ser enviado
     * @param type $text
     * @return type
     */
    protected function mask($text) {
        $b1 = 0x80 | (0x1 & 0x0f);
        echo print_r($text, true);
        $length = strlen($text);

        if ($length <= 125){
            $header = pack('CC', $b1, $length);
        }
        elseif ($length > 125 && $length < 65536){
            $header = pack('CCn', $b1, 126, $length);
        }
        elseif ($length >= 65536){
            $header = pack('CCNN', $b1, 127, $length);
        }
        return $header . $text;
    }

}
