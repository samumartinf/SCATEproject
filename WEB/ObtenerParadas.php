<?php
/*
	Este script genera una lista de texto que la app utilizará para poder mostrar las paradas disponibles
*/

INCLUDE 'DatosConex.php';

$query="SELECT ID_Parada,Nombre,Guagua,HoraLlegada,Linea FROM Paradas,Guaguas WHERE Guagua=ID_Guagua ORDER BY Guagua ASC";
$result = mysqli_query($dbc,$query);
$numberOfRows = mysqli_num_rows($result);

$salida_csv="";
if ($numberOfRows > 0) {
	while ($fila = mysqli_fetch_array($result)){
	if ($fila['Nombre']=="NO MICRO"){
		$salida_csv.=$fila['Nombre'] . ",";
		$salida_csv.=$fila['ID_Parada'] . "\n";
		} else {
		$salida_csv.=$fila['Nombre'] . " - " . substr($fila['HoraLlegada'],0,-3 ). "h - L" . $fila['Linea'] .",";
		$salida_csv.=$fila['ID_Parada'] . "\n";
		}
	}
}
echo utf8_encode($salida_csv);
?>
