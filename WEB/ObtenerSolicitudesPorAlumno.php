<?php
/*
Este script proporciona a la app toda la información sobre las solicitudes de cambio de
transporte de un alumno en concreto (opción "Revisar/Eliminar solicitudes")
*/

INCLUDE 'DatosConex.php';

$IdAl=$_GET['IDAlumno'];
$listaDias=array("Lun","Mar","Mié","Jue","Vie","Sáb","Dom");
$salida_csv="";

$query="SELECT ID_Peticion,Nombre,FechaInicio,FechaFinal,DiasActivacion,Guagua FROM Solicitudes LEFT JOIN Paradas ON ID_Parada=NuevaParada WHERE IDAlumno=$IdAl AND Activa=1";
$result = mysqli_query($dbc,$query);
$numberOfRows = mysqli_num_rows($result);


if ($numberOfRows > 0) {
	while ($fila = mysqli_fetch_array($result)){
		$diasStr="";
		$FeIni=date("d-M-Y",strtotime($fila['FechaInicio']));
		$FeFin=date("d-M-Y",strtotime($fila['FechaFinal']));
		for ($d=0;$d<5;$d++){
			if (substr($fila['DiasActivacion'],$d,1)=="1") {
				$diasStr.=utf8_decode($listaDias[$d]) . " - ";
			}
		}
		$diasStr=substr($diasStr,0,strlen($diasStr)-3);
		$salida_csv.=$FeIni . "," . $FeFin . ",";
		$salida_csv.=$fila['Guagua'] . ",";
		$salida_csv.=$fila['Nombre'] . ",";
		$salida_csv.=$diasStr .",";
		$salida_csv.=$fila['ID_Peticion'] . "\r\n";
	}
}

if (strlen($salida_csv)>0) {
	echo htmlentities(substr($salida_csv,0,strlen($salida_csv)-2));
}

?>

