socket = new WebSocket('ws://192.168.56.101:10000');

socket.onopen = function () {
	console.log("Conecto");
	sendInfoCTI('registrarAgente', {agente: 'agent123'});
};

socket.onmessage = function (msg) {
	var peticion = JSON.parse(msg.data);
	console.log(peticion);
};

socket.onclose = function () {
	console.log("cerro cx");
};
socket.onerror = function (e) {
	console.log("error cx");
	console.log(e);
};

function sendInfoCTI(accion, parametros)
{
    var msg={};
    msg.accion = accion;
    for (var propertyName in parametros) {
        msg[propertyName] = parametros[propertyName];
    }
    socket.send(JSON.stringify(msg));
}