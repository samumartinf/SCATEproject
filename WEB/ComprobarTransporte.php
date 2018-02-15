<?php
/* Este script permite listar en la opción 'Comprobar transporte' de la app,
las paradas previstas durante un número de días especificado desde la app
para un alumno con una ID de alumno también suministrada desde la app.
Al mostrar las paradas previstas, el script no tiene en cuenta los sábados
ni los domingos */

INCLUDE 'DatosConex.php';

$numDias=$_GET['NumDias'];
$IdAl=$_GET['IDAlumno'];
$listaDias=array("Lunes","Martes","Miércoles","Jueves","Viernes","Sábado","Domingo");

//$FechaHoy=date("Y-m-d");
//$diaDeHoy=date('N')-1;
$cursor=0;
$d=0;
while($d<$numDias) {
	$diasASumar="+" . $cursor . " days";
	$FechaHoy=date("Y-m-d",strtotime($diasASumar));
	$FechaHoyESP=date("d-M-Y",strtotime($diasASumar));
	$diaDeHoy=date('N',strtotime($diasASumar))-1;
	$completo=false;
	if ($diaDeHoy<=4) {	
		//echo $FechaHoy . " -" . $diaDeHoy . "- ";
		if ($diaDeHoy==0) {echo "--------------------\n";}
		$query2="SELECT Nombre, DiasActivacion,Guagua FROM Solicitudes LEFT JOIN `Paradas` ON `NuevaParada`=`ID_Parada` WHERE IDAlumno=$IdAl AND Activa=1 AND FechaInicio<='$FechaHoy' AND FechaFinal>='$FechaHoy' ORDER BY RegistroDeEntrada DESC";
		$result2=mysqli_query($dbc,$query2);
		$numFilas=mysqli_num_rows($result2);
		//echo "*" . $numFilas . "*";
		if ($numFilas>0){
			for ($i=0; $i<$numFilas; $i++){
				$Solicitud=mysqli_fetch_array($result2);
				//echo $Solicitud[Nombre] . ":" . $Solicitud[DiasActivacion] . " Hoy:" . $diaDeHoy . " ";
				if (substr($Solicitud[DiasActivacion],$diaDeHoy,1)=='1' and !$completo){
					$completo=true;
					$texto=$FechaHoyESP . " " . $listaDias[$diaDeHoy] . ": \n" . utf8_encode($Solicitud[Nombre]);
					if ($Solicitud[Nombre]=="NO MICRO"){
						$texto.= "\n";
					} else {
						$texto.= " - Línea: " . $Solicitud[Guagua] . "\n\n";
					}
					//echo utf8_encode($texto);
					echo $texto;
					$d++;
				}
			//echo "</br>";
			}
		}
		if (($numFilas==0) OR ($completo==false)){
			$query2="SELECT Nombre,Dias,Guagua FROM TransporteRegular LEFT JOIN Paradas ON Parada=ID_Parada WHERE Alumno=$IdAl ORDER BY RegistroEntrada DESC";
			$result2=mysqli_query($dbc,$query2);
			$numFilas=mysqli_num_rows($result2);
			for ($i=0; $i<$numFilas; $i++){
				$TR=mysqli_fetch_array($result2);
				if (substr($TR[Dias],$diaDeHoy,1)=='1' ){
					$texto=$FechaHoyESP . " " . $listaDias[$diaDeHoy] . ": \n" . utf8_encode($TR[Nombre]);
					if ($TR[Nombre]=="NO MICRO"){
						$texto.= "\n";
					} else {
						$texto.= " - Línea: " . $TR[Guagua] . "\n\n";	
					}
					echo $texto;
					$d++;
				}
			}
	
		}
	}
	$cursor++;
}

?>

