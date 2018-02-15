<?php
/* Obtiene la lista de paradas de la base de datos. Utilizada para llenar la lista desplegable
 de paradas del formulario de entrada manual de Usuarios y alumnos */

	include('DatosConex.php');
	
	$query="SELECT ID_Parada,Nombre,Guagua FROM Paradas ORDER BY ID_Parada";
	$result = mysqli_query($dbc,$query);
	if(mysqli_num_rows($result)){
		$datos=array();
		while($fila=mysqli_fetch_array($result)){
			$datos[]=array(
						   'id'=>$fila['ID_Parada'],
						   'nombre'=>htmlentities($fila['Nombre']),
						   'linea'=>$fila['Guagua']
						   );
			//echo $fila['ID_Parada'] . " - " . $fila['Nombre'] . " - " . $fila['Guagua'] . "<br>";
		}
		header('Content-type: application/json');
		echo json_encode($datos);
	}
?>