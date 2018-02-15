<?php
/*
Este script registra las solicitudes de cambios temporales de transporte en la tabla 'Solicitudes'.
Las solicitudes que se registran en esta tabla se añaden sin modificar las anteriores, de forma que se pueda eliminar cualquiera de ellas si fuera necesario.
A la hora de calcular el transporte del día, en caso de conflicto, se da preferencia a la entrada más reciente.
Los días para los que se aplica una parada se almacenan como una variable de 5 caracteres (una por día laborable de la semana) donde un 1 indica que la parada se aplica a ese día y un 0, que no se aplica.
P. ej.: si un registro de TransporteRegular o de Solicitudes tiene un valor de 10101 en su campo 'Días',
Significa que esa parada se aplicará los Lunes, Miércoles y Viernes.
La app utiliza este script para registrar una solicitud de cambio de transporte con una vigencia "De... hasta...".
*/

INCLUDE 'DatosConex.php';

$Usuario=$_GET['Usuario'];
$IDsAlumnos=explode(";",$_GET['IDsAlumnos']);
$NuevaParada=$_GET['NuevaParada'];
$FechaInicio=$_GET['FechaInicio'];

$FechaFinal=$_GET['FechaFinal'];

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

