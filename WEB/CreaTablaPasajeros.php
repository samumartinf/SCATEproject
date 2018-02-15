<?php
/* Este script crea la tabla de pasajeros con los transportes previstos para el día de hoy,
en cuanto "Cacharrito" se conecta al servidor */

INCLUDE 'DatosConex.php';

for ($n=0;$n<$NumAlumnos;$n++){
	$IDsAlumnos[$n]=intval($IDsAlumnos[$n]);
}

$query="SELECT ID_Alumno FROM Alumnos ORDER BY ID_Alumno ASC";
$result = mysqli_query($dbc,$query);
$NumRegistros=mysqli_num_rows($result);
if ($NumRegistros>0){
	
	$queryBT="DROP TABLE IF EXISTS `Pasajeros`"; /* Sólo para el concurso de CODECAN.
	Permite generar una nueva tabla Pasajeros cada vez que se reinicia el dispositivo,
	independientemente de la hora que sea. Normalmente, la tabla 'Pasajeros' se crearía una vez al día
	con la primera conexión de los dispositivos y se borraría madiante CRON a una hora en la que ya no
	fuera necesaria.*/
	
	$resultBT=mysqli_query($dbc,$queryBT);
	
	/* Sólo para el concurso CODECAN. Permite forzar el día de la semana para las demostraciones */
	
	$queryDiaForzado="SELECT  `Dia` FROM `DiaForzado` WHERE `ID`=0"; 
	$resultDiaForzado=mysqli_query($dbc,$queryDiaForzado);
	$filaDiaForzado=mysqli_fetch_array($resultDiaForzado);
	$diaForzado=$filaDiaForzado[Dia];
	
	/******************************************************************************************************/
	
	// Crea la tabla de PASAJEROS
	
	$queryCT="CREATE TABLE IF NOT EXISTS `Pasajeros` (
  `ID` int(11) NOT NULL,
  `UID` int(11) NOT NULL,
  `Linea` tinyint(4) NOT NULL,
  `Parada` tinyint(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	$resultCT = mysqli_query($dbc,$queryCT);

	$queryAT="ALTER TABLE `Pasajeros`
  ADD PRIMARY KEY (`ID`);";
	$resultAT = mysqli_query($dbc,$queryAT);

	$queryAT="ALTER TABLE `Pasajeros`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;";
	$resultAT = mysqli_query($dbc,$queryAT);
	
}

$FechaHoy=date("Y-m-d");
$diaDeHoy=date('N')-1;
if ($diaDeHoy>4) {$diaDeHoy=0;}

if ($diaForzado<=4){$diaDeHoy=$diaForzado;} ////////// fuerza el día para pruebas. El valor de $diaForzado se lee de la base de datos. Si el valor es 0,1,2,3 ó 4, se fuerza el día a lunes, martes, miércoles, jueves o viernes.

for ($n=0; $n<$NumRegistros; $n++){
	$ID=mysqli_fetch_array($result);
	$completo=false;
	$query2="SELECT ID_Peticion,IDAlumno,NuevaParada,DiasActivacion,TAG_NUID,Guagua FROM Solicitudes LEFT JOIN Alumnos ON IDAlumno=ID_Alumno LEFT JOIN `Paradas` ON `NuevaParada`=`ID_Parada` WHERE IDAlumno=$ID[ID_Alumno] AND Activa=1 AND FechaInicio<='$FechaHoy' AND FechaFinal>='$FechaHoy' AND `AusenteHoy`=0 ORDER BY RegistroDeEntrada DESC";
	$result2=mysqli_query($dbc,$query2);
	$numFilas=mysqli_num_rows($result2);
	if ($numFilas>0){
		for ($i=0; $i<$numFilas; $i++){
			$Solicitud=mysqli_fetch_array($result2);
			if (substr($Solicitud[DiasActivacion],$diaDeHoy,1)=='1' AND !$completo){
				$completo=true;
				$query4="INSERT INTO Pasajeros (UID,Linea,Parada) Values ('$Solicitud[TAG_NUID]','$Solicitud[Guagua]','$Solicitud[NuevaParada]')";
				$result4 = mysqli_query($dbc,$query4);
			}
		}
	}
	if (($numFilas==0) OR ($completo==false)){
		$query2="SELECT ID_TR,Alumno,Parada,Dias,TAG_NUID,Guagua FROM TransporteRegular LEFT JOIN Alumnos ON Alumno=ID_Alumno LEFT JOIN Paradas ON Parada=ID_Parada WHERE Alumno=$ID[ID_Alumno] AND `AusenteHoy`=0 ORDER BY RegistroEntrada DESC";
		$result2=mysqli_query($dbc,$query2);
		$numFilas=mysqli_num_rows($result2);
		for ($i=0; $i<$numFilas; $i++){
			$TR=mysqli_fetch_array($result2);
			if (substr($TR[Dias],$diaDeHoy,1)=='1' ){
			$query4="INSERT INTO Pasajeros (UID,Linea,Parada) Values ('$TR[TAG_NUID]','$TR[Guagua]','$TR[Parada]')";
			$result4 = mysqli_query($dbc,$query4);
			}
		}
	
	}
}
$result = mysqli_query($dbc, "SHOW COLUMNS FROM Pasajeros");
$numberOfRows = mysqli_num_rows($result);
if ($numberOfRows > 0) {
$values = mysqli_query($dbc, "SELECT * FROM Pasajeros");
while ($rowr = mysqli_fetch_row($values)) {
 for ($j=0;$j<$numberOfRows;$j++) {
  $csv_output .= $rowr[$j].";";
 }
 //$csv_output .= "\n";
}

}
//echo "</br>";
echo "-2222;";
echo $csv_output;
exit;

?>

