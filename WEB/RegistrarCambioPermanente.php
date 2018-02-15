<?php
/*
Este script registra las solicitudes de cambios de transporte permanente
en la tabla 'TransporteRegular', modificando o eliminando si es necesario cualquier registro anterior.
Los días para los que se aplica una parada se almacenan como una variable de 5 caracteres (una por día laborable de la semana) donde un 1 indica que la parada se aplica a ese día y un 0, que no se aplica.
P. ej.: si un registro de TransporteRegular o de Solicitudes tiene un valor de 10101 en su campo 'Días',
Significa que esa parada se aplicará los Lunes, Miércoles y Viernes.
La app utiliza este script para registrar una solicitud de cambio de transporte con una vigencia "permanente".
*/

INCLUDE 'DatosConex.php';

$Usuario=$_GET['Usuario'];
$IDsAlumnos=explode(";",$_GET['IDsAlumnos']);
$NuevaParada=$_GET['NuevaParada'];
$FechaInicio=$_GET['FechaInicio'];
$FechaFinal=$_GET['FechaFinal'];
$Dias=$_GET['Dias'];
$RegistroEntrada=date("Y-m-d H:i:s");
$AlumnosConSusDiasVacios=array();

echo "</br>";
echo "<PRE>";
print_r($_GET);
echo "</PRE>";
echo "</br>";
echo "<PRE>";
print_r($IDsAlumnos);
echo "</PRE>";

for ($n=0;$n<$NumAlumnos;$n++){
	$IDsAlumnos[$n]=intval($IDsAlumnos[$n]);
}


$NumAlumnos=count($IDsAlumnos);
if ($Dias=="11111"){
	for ($n=0;$n<$NumAlumnos;$n++){
		$query="DELETE FROM TransporteRegular WHERE Alumno=$IDsAlumnos[$n]";
		$result = mysqli_query($dbc,$query);
		$query="INSERT INTO TransporteRegular (IDPeticionario,Alumno,Parada,Dias,RegistroEntrada) VALUES ('$Usuario','$IDsAlumnos[$n]','$NuevaParada','$Dias','$RegistroEntrada')";
		$result = mysqli_query($dbc,$query);
		$query="DELETE FROM Solicitudes WHERE IDAlumno=$IDsAlumnos[$n]";
		$result = mysqli_query($dbc,$query);
		echo "</br>" . $query . "</br>";
		echo "Borrando los registros con IDAlumno=" . $IDsAlumnos[$n] . " de la tabla Solicitudes.</br>";
	}
} else {
	for ($n=0;$n<$NumAlumnos;$n++){
		$DiasDeCadaAlumno=array();
		$query="SELECT ID_TR, Dias FROM TransporteRegular WHERE Alumno=$IDsAlumnos[$n]";
		echo "ID Alumno: " . $IDsAlumnos[$n];
		echo "</br>" . $query . "</br>";
		$result = mysqli_query($dbc,$query);
		$NumRegistros=mysqli_num_rows($result);
		for ($i=0;$i<$NumRegistros;$i++){
			$fila=mysqli_fetch_array($result);
			$DiasParaCambiar=$fila[Dias];
			$DiasCorregidos=PonerAntiguosUnosACeros($Dias,$DiasParaCambiar);
			echo "Dias Corregidos: " . $DiasCorregidos . "</br>";
			array_push($DiasDeCadaAlumno,$DiasCorregidos);

			$query="UPDATE TransporteRegular SET Dias=LPAD($DiasCorregidos,5,'0') WHERE ID_TR=$fila[ID_TR]";
			echo "</br>" . $query . "</br>";
			$result = mysqli_query($dbc,$query);
			// Avisar en la APP, que esta opción anula los cambios en 'Solicitudes'.				
		}
		$query="DELETE FROM Solicitudes WHERE IDAlumno='$IDsAlumnos[$n]'";
		$result = mysqli_query($dbc,$query);
		$DiasVaciosDeAlumno=BuscaDiasVacios($DiasDeCadaAlumno);
		if ($DiasVaciosDeAlumno){
			$cadenaAlumnosDias=$IDs_Alumnos[$n]; /////////// ¿Se usa?
			array_push($AlumnosConSusDiasVacios,$IDs_Alumnos[$n]);
			for ($j=0;$j<count($DiasVaciosDeAlumno);$j++){
				array_push($AlumnosConSusDiasVacios,$DiasVaciosDeAlumno[$j]);
			}
			array_push($AlumnosConSusDiasVacios,"\n");
			///////// Incluir los días vacíos de cada alumno
		}
		$query="INSERT INTO TransporteRegular (IDPeticionario,Alumno,Parada,Dias,RegistroEntrada) VALUES ('$Usuario','$IDsAlumnos[$n]','$NuevaParada','$Dias','$RegistroEntrada')";
		$result = mysqli_query($dbc,$query);
		echo "</br>" . $query . "</br>";
	}
	$query="DELETE FROM TransporteRegular WHERE Dias='00000'";
	$result = mysqli_query($dbc,$query);
}

echo utf8_encode(implode($AlumnosConSusDiasVacios));



function PonerAntiguosUnosACeros($NuevosDias,$AntiguosDias){
	$Devolver="";
	for ($n=0;$n<strlen($AntiguosDias);$n++){
		if ($NuevosDias[$n]=="1"){
			$Devolver.="0";
		} else {
			$Devolver.=$AntiguosDias[$n];
		}
	}
	return $Devolver;
}

function BuscaDiasVacios($ListaDeDias){
	$DiasVacios=array();
	for($posicion=0;$posicion<5;$posicion++){
		$encontradoUNO=false;
		for ($n=0;$n<count($ListaDeDias);$n++){
			if (substr($ListaDeDias[$n],$posicion,1)=="1") {
				$encontradoUNO=true;
			}
		}
		if (!$encontradoUNO) {
			array_push($DiasVacios,$posicion);
		}
	}
	return $DiasVacios;
}
?>

