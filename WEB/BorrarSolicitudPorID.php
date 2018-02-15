<?php
// Este script recibe un identificador de solicitud desde la app, lo busca en la tabla 'Solicitudes' y lo elimina

INCLUDE 'DatosConex.php';

$IdSol=$_GET['IDSolicitud'];

$query="DELETE FROM Solicitudes WHERE ID_Peticion=$IdSol";
$result = mysqli_query($dbc,$query);
$numberOfRows = mysqli_affected_rows($result);

echo "Borrado {$numberOfRows} registro.";

?>

