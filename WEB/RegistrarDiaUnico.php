<?php
/*
Este script registra una solicitud de cambio de transporte en la tabla 'Solicitudes' con una vigencia de un sólo día: el día en que se realiza la solicitud.
La app utiliza este script para registrar una solicitud de cambio de transporte con una vigencia "Sólo para HOY".
*/

INCLUDE 'DatosConex.php';

$Usuario=$_GET['Usuario'];
$IDsAlumnos=explode(";",$_GET['IDsAlumnos']);
$NuevaParada=$_GET['NuevaParada'];
$FechaInicio=date("Y-m-d");
$FechaFinal=$FechaInicio;
$Dias=$_GET['Dias'];

$RegistroEntrada=date("Y-m-d H:i:s");

for ($n=0;$n<$NumAlumnos;$n++){
	$IDsAlumnos[$n]=intval($IDsAlumnos[$n]);
}

$NumAlumnos=count($IDsAlumnos);
for ($n=0;$n<$NumAlumnos;$n++){
	$query="INSERT INTO Solicitudes (IDPeticionario,IDAlumno,NuevaParada,FechaInicio,FechaFinal,DiasActivacion,RegistroDeEntrada) VALUES ('$Usuario','$IDsAlumnos[$n]','$NuevaParada','$FechaInicio','$FechaFinal','$Dias','$RegistroEntrada')";
	$result = mysqli_query($dbc,$query);
	$NumRegistros+=$result;
}

echo utf8_encode("Cambios registrados para " . $NumRegistros . " alumnos.");
?>
