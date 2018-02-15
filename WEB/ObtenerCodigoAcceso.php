<?php
/*
Este script recibe desde la app, en su fase de activación, un número de teléfono del posible usuario.
Este número se busca en la tabla 'Usuarios' y si se encuentra, se envía a la dirección de e-mail registrada
en la misma tabla, un mensaje con la clave de activación.
*/

INCLUDE 'DatosConex.php';

$TlfEnviado=$_GET['Tlf'];
 
$query="SELECT CodigoConexion,Email FROM Usuarios WHERE Tlf={$TlfEnviado}";
$result = mysqli_query($dbc,$query);

$numberOfRows = mysqli_num_rows($result);

if ($numberOfRows > 0) {
	while ($fila = mysqli_fetch_array($result)){
		$para=$fila['Email'];
		$asunto='Código acceso App transporte Colegio Heidelberg.';
		$mensaje="Su código de acceso para la aplicación móvil para el transporte escolar del Colegio Heidelberg es el siguiente: ". $fila['CodigoConexion'];
		$cabecera="From: guaguas@heidelbergschule.com" . "\r\n" .
		mail($para,$asunto,$mensaje,$cabecera);
	}
} else {
	echo "<p>ERROR</p>";
}

?>