<?php
/*
Este script lo utiliza la app. A partir del la clave de usuario almacenada en el móvil al activar
la aplicación, genera la lista de de alumnos (con ID, nombre y apellidos) que están a cargo de dicho usuario.
*/

INCLUDE 'DatosConex.php';

$ClaveUsuarioEnviada=$_GET['ClaveUsuario'];

$query="SELECT ID_Usuario FROM Usuarios WHERE CodigoConexion='{$ClaveUsuarioEnviada}'";
$result = mysqli_query($dbc,$query);
$numberOfRows = mysqli_num_rows($result);
if ($numberOfRows==1) { // Sólo debe haber una clave coincidente en la BD. Si la encuentra, localiza los alumnos a su cargo...
	$fila=mysqli_fetch_array($result);
	$Usr=$fila[ID_Usuario];
	$query="SELECT Alumnos.Nombre,Alumnos.Ape1,Alumnos.Ape2,ID_Alumno,ID_Usuario FROM LuT_UsuarioAlumno, Alumnos, Usuarios WHERE IdUsuario={$Usr} AND ID_Alumno=IdAlumno AND ID_Usuario={$Usr}";

	$result = mysqli_query($dbc,$query);

	$numberOfRows = mysqli_num_rows($result);

	if ($numberOfRows > 0) {
		while ($fila = mysqli_fetch_array($result)){
			$salida_csv.=$fila['Nombre'] . " " . $fila['Ape1'] . " " . $fila['Ape2'] . ",";
			$salida_csv.=$fila['ID_Alumno'] . ",";
			$salida_csv.=$Usr;
			$salida_csv.="\n";
		}
	}
		echo utf8_encode(trim($salida_csv)."\n");
	} else {echo "Clave inexistente";}
?>