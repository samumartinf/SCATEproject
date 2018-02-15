<?php
// Para incluir en los demás scripts. Contiene los parámetros para conectar a la base de datos.

DEFINE ('DBUSER', 'hbwbmstr_codecan'); 
DEFINE ('DBPW', 'Codecan_3'); 
DEFINE ('DBHOST', 'localhost'); 
DEFINE ('DBNAME', 'hbwbmstr_codecan'); 

date_default_timezone_set("Europe/London");

$dbc = mysqli_connect(DBHOST,DBUSER,DBPW);
if (!$dbc) {
    die("Error al conectar con el servidor de base de datos: " . mysqli_error($dbc));
    exit();
}

$dbs = mysqli_select_db($dbc, DBNAME);
if (!$dbs) {
    die("Error al seleccionar la base de datos: " . mysqli_error($dbc));
    exit(); 
}
?>