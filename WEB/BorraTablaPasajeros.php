<?php
// Este script elimina la tabla 'Pasajeros' para que se pueda crear de nuevo en la próxima petición que realice "Cacharrito"

INCLUDE 'DatosConex.php';

$query="DROP TABLE IF EXISTS `Pasajeros`;";
$result = mysqli_query($dbc,$query);
?>