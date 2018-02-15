<?php
/*
Este script genera una secuencia de texto a partir de la tabla 'Pasajeros' que leerá "Cacharrito"
para obtener la lista de pasajeros previstos para el día actual.
*/

INCLUDE 'DatosConex.php';

$result = mysqli_query($dbc, "SHOW COLUMNS FROM Pasajeros");
$numberOfRows = mysqli_num_rows($result);
if ($numberOfRows > 0) {
	$values = mysqli_query($dbc, "SELECT * FROM Pasajeros");
		while ($rowr = mysqli_fetch_row($values)) {
		for ($j=0;$j<$numberOfRows;$j++) {
			$csv_output .= $rowr[$j].";";
		}

	}

}

echo "-2222;";
echo $csv_output;
exit;
?>