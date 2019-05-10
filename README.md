Servidor CTI
============================

Servidor CTI es un proyecto que pretende ser el intermediario entre los Clientes CTI y Asterisk Manager. Dado lo anterior, los Clientes CTI recibiran la información correspondiente a las llamadas ingresadas, estados propios del servidor de telefonía y con esto tener de primera mano información sobre los clientes que están atendiendo.

ESTRUCTURA DEL DIRECTORIO
-------------------

      config/             Contiene la configuración de la aplicación.
      class/		  Contiene las clases implementadas para las conexiones tanto con los Clientes CTI como con el Asterisk Manager


REQUERIMIENTOS
------------

Los requerimientos míminos para este proyecto que el Servidor Web soporte PHP 5.3.3.


CONFIGURACION
-------------

Editar el archivo `config/config_cti.php` con los datos reales según como se muestra.

### Configuración del Servidor
```php
	/****  W E B S O C K E T     S E R V E R ****/
	define('PUERTO_ESCUCHA_WS', 10000);
```

### Configuración del Cliente de Asterisk Manager
```php
	/****  A S T E R I S K    M A N A G E R   C L I E N T ****/
	define('AST_MANAGER_HOST', Ip Asterisk Manager);
	define('AST_MANAGER_PORT', Puerto Escucha Asterisk Manager);
	define('AST_MANAGER_USER', Usuario);
	define('AST_MANAGER_PASS', Clave);
```
