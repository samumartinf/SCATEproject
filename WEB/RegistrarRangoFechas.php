<?php
/*
Este script registra las solicitudes de cambios temporales de transporte en la tabla 'Solicitudes'.
Las solicitudes que se registran en esta tabla se a�aden sin modificar las anteriores, de forma que se pueda eliminar cualquiera de ellas si fuera necesario.
A la hora de calcular el transporte del d�a, en caso de conflicto, se da preferencia a la entrada m�s reciente.
Los d�as para los que se aplica una parada se almacenan como una variable de 5 caracteres (una por d�a laborable de la semana) donde un 1 indica que la parada se aplica a ese d�a y un 0, que no se aplica.
P. ej.: si un registro de TransporteRegular o de Solicitudes tiene un valor de 10101 en su campo 'D�as',
Significa que esa parada se aplicar� los Lunes, Mi�rcoles y Viernes.
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

