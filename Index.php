<?php

/** 
 * Función de conexión a la base de datos 
 */
function connectionDB() {
   // Datos
   $dbhost = 'mysql.hostinger.es';
   $dbuser = 'u509340381_rest';
   $dbpass = '123456';
   $dbname = 'u509340381_rest';
   
   try {
      // Inicia conexión a la base de datos
      $dbcon = new PDO("mysql:host=$dbhost; dbname=$dbname", $dbuser, $dbpass);
      // Activa las excepciones en el controlador PDO
      $dbcon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      // Fuerza la codificación de caracteres a UTF8
      $dbcon->exec("SET NAMES 'utf8'");
      // Devuelve la conexión
      return $dbcon;
   } catch (PDOException $e) {
      // Muestra el error de conexión
      echo '{"error":-1, "message": "Error de conexión con la base de datos: '.$e->getMessage().'"}';
      return null;
   }
}

// Función para validar la fecha
function validateDate($date, $format = 'YmdHi'){
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function validateLatitud($val){
   if(!preg_match('/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/', $val)){
      return false;
   }
   return true;
}

function validateLongitud($val){
   if(!preg_match('/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $val)){
      return false;
   }
   return true;
}

// Función para validar un email
function validateEmail($val){
   if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
      return false;
   }
   return true;
}

// Función para validar pagina web
function validateWeb($val){
    if(!filter_var($val, FILTER_VALIDATE_URL)){
        return false;   
    }
    return true;
}

// Función para validar URL facebook
function validateFacebook($val){
    if(!preg_match('/^(http\:\/\/|https\:\/\/)?((w{3}\.)?)facebook\.com\/(?:#!\/)?(?:pages\/)?(?:[\w\-\.]*\/)*([\w\-\.]*)+$/', $val)){
        return false;
    }
    return true;
}

// Función para validar URL twitter
function validateTwitter($val){
    if(!preg_match('/^(https?:\/\/)?((w{3}\.)?)twitter\.com\/(#!\/)?[a-z0-9_]+$/', $val)){
        return false;   
    }
    return true;
}

// Registra la librería Slim
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

// Crea la aplicación con el servidor REST
$app = new \Slim\Slim();

// Deshabilita el modo depuración. Activar con el servidor REST en producción
// $app->config('debug', false);

// Inserta en la cabecera de las respuesta de las operaciones GET el tipo de contenido
$app->response->headers->set('Content-Type', 'application/json; charset=utf-8');

/**
* Opración GET de todos los acontecimientos
*/
$app->get('/acontecimientos', function () {

  $sql_acontecimiento = "SELECT * FROM acontecimiento";

     try{
      // Conecta con la base de datos
      $db = connectionDB();
      
      if ($db != null){
        $stmt_acontecimiento = $db->prepare($sql_acontecimiento);
        $stmt_acontecimiento->execute();

        // Obtiene un array asociativo con los registros
        $record_acontecimiento = $stmt_acontecimiento->fetchAll(PDO::FETCH_ASSOC);

        if ($record_acontecimiento != false){
          // Elimina los valores vacíos del registro
          $record_acontecimiento = array_filter($record_acontecimiento);
            
          // Convierte el array a formato JSON con caracteres Unicode y modo tabulado
          // Deshabilitar JSON_PRETTY_PRINT con el servidor REST en producción
          $output = json_encode($record_acontecimiento, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
          
          echo $output;
         } else {
            echo '{"error": -11, "message": "El acontecimiento no existe"}';
         }

         // Cierra la conexión con la base de datos
         $db = null;
      }
   } catch (PDOException $e) {
      echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
   }
});

/**
* Opración GET de un acontecimiento por id
*/
$app->get('/ac/:param_id', function ($param_id) {

  $sql_acontecimiento = "SELECT *, DATE_FORMAT(inicio, '%d %b %Y %H:%i') AS inicio, DATE_FORMAT(fin, '%d %b %Y %H:%i') AS fin FROM acontecimiento WHERE id = :bind_id";

     try{
      // Conecta con la base de datos
      $db = connectionDB();
      
      if ($db != null){
        $stmt_acontecimiento = $db->prepare($sql_acontecimiento);
        $stmt_acontecimiento->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
        $stmt_acontecimiento->execute();

        // Obtiene un array asociativo con un registro
        $record_acontecimiento = $stmt_acontecimiento->fetch(PDO::FETCH_ASSOC);

        if ($record_acontecimiento != false){
          // Elimina los valores vacíos del registro
          $record_acontecimiento = array_filter($record_acontecimiento);


            
          // Convierte el array a formato JSON con caracteres Unicode y modo tabulado
          // Deshabilitar JSON_PRETTY_PRINT con el servidor REST en producción
          $output .= json_encode($record_acontecimiento, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            
          echo $output;
         } else {
            echo '{"error": -11, "message": "El acontecimiento no existe"}';
         }

         // Cierra la conexión con la base de datos
         $db = null;
      }
   } catch (PDOException $e) {
      echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
   }
});

/**
 * Operación GET de recuperación de un acontecimiento con sus eventos mediante su identificador
 */
$app->get('/acontecimiento/:param_id', function ($param_id) {
   // Comprueba el parámetro de entrada
   $param_id = intval($param_id);
   
   // Sentencias SQL
   $sql_acontecimiento = "SELECT *, DATE_FORMAT(inicio, '%Y%m%d%H%i') AS inicio, DATE_FORMAT(fin, '%Y%m%d%H%i') AS fin FROM acontecimiento WHERE id=:bind_id";
   $sql_eventos = "SELECT *, DATE_FORMAT(inicio, '%Y%m%d%H%i') AS inicio, DATE_FORMAT(fin, '%Y%m%d%H%i') AS fin FROM evento WHERE id_acontecimiento=:bind_id";

   try{
      // Conecta con la base de datos
      $db = connectionDB();
      
      if ($db != null){
         // Prepara y ejecuta la sentencia
         $stmt_acontecimiento = $db->prepare($sql_acontecimiento);
         $stmt_acontecimiento->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
         $stmt_acontecimiento->execute();
         
         // Obtiene un array asociativo con un registro
         $record_acontecimiento = $stmt_acontecimiento->fetch(PDO::FETCH_ASSOC);

         if ($record_acontecimiento != false){
            // Elimina los valores vacíos del registro
            $record_acontecimiento = array_filter($record_acontecimiento);

            $output = '{"acontecimiento":';
            
            // Convierte el array a formato JSON con caracteres Unicode y modo tabulado
            // Deshabilitar JSON_PRETTY_PRINT con el servidor REST en producción
            $output .= json_encode($record_acontecimiento, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            // Prepara y ejecuta la sentencia
            $stmt_eventos = $db->prepare($sql_eventos);
            $stmt_eventos->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
            $stmt_eventos->execute();

            // Obtiene uno a uno los registros para eliminar los valores vacíos en ellos
            $record_eventos = array();
            while ($record = $stmt_eventos->fetch(PDO::FETCH_ASSOC))
               array_push($record_eventos, array_filter($record));
            
            if (sizeof($record_eventos) != 0){
               $output .= ',"eventos":';
               
               // Convierte el array a formato JSON con caracteres Unicode y modo tabulado
               // Deshabilitar JSON_PRETTY_PRINT con el servidor REST en producción
               $output .= json_encode($record_eventos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            $output .= '}';
            
            echo $output;
         } else {
            echo '{"error": -11, "message": "El acontecimiento no existe"}';
         }

         // Cierra la conexión con la base de datos
         $db = null;
      }
   } catch (PDOException $e) {
      echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
   }
});

/*
* Función de buscar por nombre
*/

$app->get('/buscar/nombre/:param_palabras', function ($param_palabras) { 

// Comprueba el parámetro de entrada y lo separa en palabras
   $array_palabras = explode(' ', $param_palabras);

   if (sizeof($array_palabras) != 0) {
      // Crea la sentencia SQL añadiendo la condición por cada palabra buscada
      // A la palabra se le añade el carácter '%' para la búsqueda
      // Se elimina de la sentencia el último 'AND' para evitar errores de sintaxis
      $sql_busqueda = "SELECT id, nombre, DATE_FORMAT(inicio, '%Y%m%d%H%i') AS inicio FROM acontecimiento WHERE";
      foreach ($array_palabras as $indice=>$valor){
         $array_palabras[$indice] = '%'.$valor.'%';
         $sql_busqueda .= " nombre LIKE ? AND";
      }
      $sql_busqueda = substr($sql_busqueda, 0, -4);

      try{
         // Conecta con la base de datos
         $db = connectionDB();
      
         if ($db != null){
            // Prepara y ejecuta la sentencia
            $stmt_busqueda = $db->prepare($sql_busqueda);
            $stmt_busqueda->execute($array_palabras);
         
            // Obtiene un array asociativo con los registros
            $records_busqueda = $stmt_busqueda->fetchAll(PDO::FETCH_ASSOC);

            if ($records_busqueda != false){
               $output = '{"acontecimientos":';
            
               // Convierte el array a formato JSON con caracteres Unicode y modo tabulado
               // Deshabilitar JSON_PRETTY_PRINT con el servidor REST en producción
               $output .= json_encode($records_busqueda, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

               $output .= '}';
            
               echo $output;
            } else {
               echo '{"error": -13, "message": "No se han encontrado acontecimientos"}';
            }

            // Cierra la conexión con la base de datos
            $db = null;
         }
      } catch (PDOException $e) {
         echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
      }
   } else {
      echo '{“error”:-15, “message”:”Parámetros de búsqueda incorrectos”}';
   }
});

/*
* Función de buscar por localidad
*/

$app->get('/buscar/localidad/:param_palabras', function ($param_palabras) { 
// Comprueba el parámetro de entrada y lo separa en palabras
   $array_palabras = explode(' ', $param_palabras);

   if (sizeof($array_palabras) != 0) {
      // Crea la sentencia SQL añadiendo la condición por cada palabra buscada
      // A la palabra se le añade el carácter '%' para la búsqueda
      // Se elimina de la sentencia el último 'AND' para evitar errores de sintaxis
      $sql_busqueda = "SELECT id, nombre, DATE_FORMAT(inicio, '%Y%m%d%H%i') AS inicio FROM acontecimiento WHERE";
      foreach ($array_palabras as $indice=>$valor){
         $array_palabras[$indice] = '%'.$valor.'%';
         $sql_busqueda .= " localidad LIKE ? AND";
      }
      $sql_busqueda = substr($sql_busqueda, 0, -4);

      try{
         // Conecta con la base de datos
         $db = connectionDB();
      
         if ($db != null){
            // Prepara y ejecuta la sentencia
            $stmt_busqueda = $db->prepare($sql_busqueda);
            $stmt_busqueda->execute($array_palabras);
         
            // Obtiene un array asociativo con los registros
            $records_busqueda = $stmt_busqueda->fetchAll(PDO::FETCH_ASSOC);

            if ($records_busqueda != false){
               $output = '{"acontecimientos":';
            
               // Convierte el array a formato JSON con caracteres Unicode y modo tabulado
               // Deshabilitar JSON_PRETTY_PRINT con el servidor REST en producción
               $output .= json_encode($records_busqueda, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

               $output .= '}';
            
               echo $output;
            } else {
               echo '{"error": -13, "message": "No se han encontrado acontecimientos"}';
            }

            // Cierra la conexión con la base de datos
            $db = null;
         }
      } catch (PDOException $e) {
         echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
      }
   } else {
      echo '{“error”:-15, “message”:”Parámetros de búsqueda incorrectos”}';
   }
});

/**-------------------- FUNCIONES POR BUSQUEDAS DE LATITUD Y LONGITUD -------------------- **/

function getBoundaries($lat, $lng, $km, $earthRadius = 6371){
    $return = array();
     
    // Los angulos para cada dirección
    $cardinalCoords = array('north' => '0',
                            'south' => '180',
                            'east' => '90',
                            'west' => '270');
    $rLat = deg2rad($lat);
    $rLng = deg2rad($lng);
    $rAngDist = $km/$earthRadius;
    foreach ($cardinalCoords as $name => $angle)
    {
        $rAngle = deg2rad($angle);
        $rLatB = asin(sin($rLat) * cos($rAngDist) + cos($rLat) * sin($rAngDist) * cos($rAngle));
        $rLonB = $rLng + atan2(sin($rAngle) * sin($rAngDist) * cos($rLat), cos($rAngDist) - sin($rLat) * sin($rLatB));
         $return[$name] = array('lat' => (float) rad2deg($rLatB), 
                                'lng' => (float) rad2deg($rLonB));
    }
    return array('min_lat'  => $return['south']['lat'],
                 'max_lat' => $return['north']['lat'],
                 'min_lng' => $return['west']['lng'],
                 'max_lng' => $return['east']['lng']);
}


/** 
* Método para buscar por proximidad
*/

$app->get('/buscar/proximidad/:param_distancia', function ($param_distancia) { 
// Comprueba el parámetro de entrada y lo separa.
   $array_distancia = explode(' ', $param_distancia);

   if (sizeof($array_distancia) == 3 ) {

      $lat = $array_distancia[0];
      $lng = $array_distancia[1];
      $km = $array_distancia[2];

      $box = getBoundaries($lat, $lng, $km);

      $sql_buscar = 'SELECT id, nombre, DATE_FORMAT(inicio, "%Y%m%d%H%i") AS inicio, ( 6371 * ACOS( 
                                             COS( RADIANS(' . $lat . ') ) * COS(RADIANS( latitud ) ) * COS(RADIANS( longitud ) 
                                             - RADIANS(' . $lng . ') ) + SIN( RADIANS(' . $lat . ') ) 
                                             * SIN(RADIANS( latitud ) ) )) AS kms
                     FROM acontecimiento WHERE (latitud BETWEEN ' . $box['min_lat']. ' AND ' . $box['max_lat'] . ')
                     AND (longitud BETWEEN ' . $box['min_lng']. ' AND ' . $box['max_lng']. ')
                           HAVING kms  < ' . $km . '                                       
                           ORDER BY kms ASC';

      try{
         // Conecta con la base de datos
         $db = connectionDB();
      
         if ($db != null){
            // Prepara y ejecuta la sentencia
            $stmt_busqueda = $db->prepare($sql_buscar);
            $stmt_busqueda->execute($array_distancia);
         
            // Obtiene un array asociativo con los registros
            $records_busqueda = $stmt_busqueda->fetchAll(PDO::FETCH_ASSOC);

            if ($records_busqueda != false){
               $output = '{"acontecimientos":';
            
               // Convierte el array a formato JSON con caracteres Unicode y modo tabulado
               // Deshabilitar JSON_PRETTY_PRINT con el servidor REST en producción
               $output .= json_encode($records_busqueda, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

               $output .= '}';
            
               echo $output;
            } else {
               echo '{"error": -13, "message": "No se han encontrado acontecimientos"}';
            }

            // Cierra la conexión con la base de datos
            $db = null;
         }
      } catch (PDOException $e) {
         echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
      }

   } else {
      echo '{“error”:-15, “message”:”Parámetros de búsqueda incorrectos”}';
   }

});

/**
 * Operación POST de inserción de un acontecimiento
 */
$app->post('/acontecimiento', function () {
   // Obtiene la petición que ha recibido el servidor REST
   $request = \Slim\Slim::getInstance()->request();

   // Obtiene el body de la petición recibida
   $request_body = $request->getBody();

   // Transforma el contenido JSON del body en un array
   $acontecimiento = json_decode($request_body, true, 10);
   
   // Comprueba los errores en el contenido JSON
   if (json_last_error() != JSON_ERROR_NONE) {
      echo '{"error":-21, "message": "Contenido JSON con errores"}';
   } else if(!in_array($acontecimiento['acontecimiento'], $acontecimiento)){
      echo '{"error":-21, "message": "Contenido JSON con errores, falta la apertura acontecimiento"}';
   } else {

      // Creamos una variable booleana para indicar si es verdado o falso, para así indicar en un if si puede insertar en la base de datos
      // los datos del acontecimiento o ni puede
      $bol = true;

      // Creamos un String con el mensaje de error, el cual concatenará en el foreach
      $campo_error = '{"error": -22, "message": "Campo con error: ';

      //Comprueba los valores del contenido JSON | con empty comprobemos que exista y esté vacio y luego comprobamos la longitud del campo
      if(empty($acontecimiento['acontecimiento']['nombre'])){
         $campo_error .= 'nombre vacio, ';
         $bol = false;
      } else if (strlen($acontecimiento['acontecimiento']['nombre']) > 256) {
         $campo_error .= 'nombre tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['organizador'])){
         $acontecimiento['acontecimiento']['organizador'] = '';
      } else if (strlen($acontecimiento['acontecimiento']['organizador']) > 256) {
         $campo_error .= 'organizador tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['descripcion'])){
         $campo_error .= 'descripcion vacío, ';
         $bol = false;
      } else if (strlen($acontecimiento['acontecimiento']['descripcion']) > 2014) {
         $campo_error .= 'descripcion tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['tipo'])){
         $campo_error .= 'tipo vacío, ';
         $bol = false;
      } else if (strlen($acontecimiento['acontecimiento']['tipo']) > 11) {
         $campo_error .= 'tipo tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['inicio'])){
         $campo_error .= 'fecha inicio vacío, ';
         $bol = false;
      } else if (validateDate($acontecimiento['acontecimiento']['inicio']) == false) {
         $campo_error .= 'la fecha de inicio no es correcta, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['fin'])){
         $campo_error .= 'fecha fin vacío, ';
         $bol = false;
      } else if (validateDate($acontecimiento['acontecimiento']['fin']) == false) {
         $campo_error .= 'la fecha de fin no es correcta, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['direccion'])){
         $acontecimiento['acontecimiento']['direccion'] = '';
      } else if(strlen($acontecimiento['acontecimiento']['direccion']) > 256){
         $campo_error .= 'direccion tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['localidad'])){
         $acontecimiento['acontecimiento']['localidad'] = '';
      } else if(strlen($acontecimiento['localidad']) > 256){
         $campo_error .= 'localidad tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['cod_postal'])){
         $acontecimiento['acontecimiento']['cod_postal'] = '';
      } else if(strlen($acontecimiento['acontecimiento']['cod_postal']) > 5){
         $campo_error .= 'cod_postal tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['provincia'])){
         $acontecimiento['acontecimiento']['provincia'] = '';
      } else if(strlen($acontecimiento['acontecimiento']['provincia']) > 256){
         $campo_error .= 'provincia tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['latitud'])){
         $acontecimiento['acontecimiento']['latitud'] = '';
      } else if(validateLatitud($acontecimiento['acontecimiento']['latitud']) == false){
         $campo_error .= 'latitud incorrecta, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['longitud'])){
         $acontecimiento['acontecimiento']['longitud'] = '';
      } else if(validateLongitud($acontecimiento['acontecimiento']['longitud']) == false){
         $campo_error .= 'longitud incorrecta, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['telefono'])){
         $acontecimiento['acontecimiento']['telefono'] = '';
      } else if(strlen($acontecimiento['acontecimiento']['telefono']) > 9){
         $campo_error .= 'telefono tiene más longitud de lo permitido, ';
         $bol = false;
      }
      
      if(empty($acontecimiento['acontecimiento']['email'])){
         $acontecimiento['acontecimiento']['email'] = '';
      } else if (validateEmail($acontecimiento['acontecimiento']['email']) == false) {
         $campo_error .= 'el email incorrecto, ';
         $bol = false;
      } else if(strlen($acontecimiento['acontecimiento']['email']) > 256){
         $campo_error .= 'email tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['web'])){
         $acontecimiento['acontecimiento']['web'] = '';
      } else if (validateWeb($acontecimiento['acontecimiento']['web']) == false) {
         $campo_error .= 'web incorrecta, ';
         $bol = false;
      } else if(strlen($acontecimiento['acontecimiento']['web']) > 256){
         $campo_error .= 'web tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['facebook'])){
         $acontecimiento['acontecimiento']['facebook'] = '';
      } else if (validateFacebook($acontecimiento['acontecimiento']['facebook']) == false) {
         $campo_error .= 'url incorrecta, ';
         $bol = false;
      } else if(strlen($acontecimiento['acontecimiento']['facebook']) > 256){
         $campo_error .= 'facebook tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['twitter'])){
         $acontecimiento['acontecimiento']['twitter'] = '';
      } else if (validateTwitter($acontecimiento['acontecimiento']['twitter']) == false) {
         $campo_error .= 'url de twitter incorrecta, ';
         $bol = false;
      } else if(strlen($acontecimiento['acontecimiento']['twitter']) > 256){
         $campo_error .= 'twitter tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($acontecimiento['acontecimiento']['instagram'])){
         $acontecimiento['acontecimiento']['instagram'] = '';
      } else if(strlen($acontecimiento['acontecimiento']['instagram']) > 256){
         $campo_error .= 'instagram tiene más longitud de lo permitido, ';
         $bol = false;
      }

      // si la variable bol es falsa mostrará el mensaje, en caso contrario ejecutará la consulta
      if($bol == false){
         $campo_error = substr($campo_error, 0, -2);
         echo $campo_error .= '"}';
      } else{
         // Formateamos las fechas
         $acontecimiento['acontecimiento']['inicio'] = date("Y-m-d H:i", strtotime($acontecimiento['acontecimiento']['inicio']));
         $acontecimiento['acontecimiento']['fin'] = date("Y-m-d H:i", strtotime($acontecimiento['acontecimiento']['fin']));
         // Sentencias SQL
         $sql_insert = "INSERT INTO acontecimiento (nombre, organizador, descripcion, tipo, inicio, fin, direccion, localidad, cod_postal, provincia, latitud, longitud, telefono, email, web, facebook, twitter, instagram) 
                        VALUES (:bind_nombre, :bind_organizador, :bind_descripcion, :bind_tipo, :bind_inicio, :bind_fin, :bind_direccion, :bind_localidad, :bind_cod_postal, :bind_provincia, :bind_latitud, :bind_longitud, :bind_telefono, :bind_email, :bind_web, :bind_facebook, :bind_twitter, :bind_instagram)";
      
         try {
            // Conecta con la base de datos
            $db = connectionDB();
            
            if ($db != null){
               // Prepara y ejecuta de la sentencia
               $stmt_insert = $db->prepare($sql_insert);
               $stmt_insert->bindParam(":bind_nombre", $acontecimiento['acontecimiento']['nombre'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_organizador", $acontecimiento['acontecimiento']['organizador'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_descripcion", $acontecimiento['acontecimiento']['descripcion'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_tipo", $acontecimiento['acontecimiento']['tipo'], PDO::PARAM_INT);
               $stmt_insert->bindParam(":bind_inicio", $acontecimiento['acontecimiento']['inicio'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_fin", $acontecimiento['acontecimiento']['fin'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_direccion", $acontecimiento['acontecimiento']['direccion'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_localidad", $acontecimiento['acontecimiento']['localidad'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_cod_postal", $acontecimiento['acontecimiento']['cod_postal'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_provincia", $acontecimiento['acontecimiento']['provincia'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_latitud", $acontecimiento['acontecimiento']['latitud'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_longitud", $acontecimiento['acontecimiento']['longitud'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_telefono", $acontecimiento['acontecimiento']['telefono'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_email", $acontecimiento['acontecimiento']['email'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_web", $acontecimiento['acontecimiento']['web'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_facebook", $acontecimiento['acontecimiento']['facebook'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_twitter", $acontecimiento['acontecimiento']['twitter'], PDO::PARAM_STR);
               $stmt_insert->bindParam(":bind_instagram", $acontecimiento['acontecimiento']['instagram'], PDO::PARAM_STR);
               $stmt_insert->execute();

               echo '{"error": 1, "message": "Acontecimiento insertado correctamente con el id '.$db->lastInsertId().'"}';
               
               // Cierra la conexión con la base de datos
               $db = null;
            }
         } catch(PDOException $e) {
            echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
         }
      }
   }
});

/**
 * Operación POST de inserción de un evento
 */
$app->post('/evento/:param_idAcontecimiento', function ($param_idAcontecimiento) {
   // Comprueba el parámetro de entrada
   $param_idAcontecimiento = intval($param_idAcontecimiento);
   // Obtiene la petición que ha recibido el servidor REST
   $request = \Slim\Slim::getInstance()->request();

   // Obtiene el body de la petición recibida
   $request_body = $request->getBody();

   // Transforma el contenido JSON del body en un array
   $evento = json_decode($request_body, true, 10);
   
   // Comprueba los errores en el contenido JSON
   if (json_last_error() != JSON_ERROR_NONE) {
      echo '{"error":-21, "message": "Contenido JSON con errores"}';
      // Comprueba que estén abiertas las llaves del acontecimiento en el json
   } else if(!in_array($evento['evento'], $evento)){
      echo '{"error":-21, "message": "Contenido JSON con errores, falta la apertura evento"}';
   } else {

      // Creamos una variable booleana para indicar si es verdado o falso, para así indicar en un if si puede insertar en la base de datos
      // los datos del evento o ni puede
      $bol = true;

      // Creamos un String con el mensaje de error, el cual concatenará en el foreach
      $campo_error = '{"error": -22, "message": "Campo con error: ';

      //Comprueba los valores del contenido JSON | con empty comprobemos que exista y esté vacio y luego comprobamos la longitud del campo
      if(empty($evento['evento']['nombre'])){
         $campo_error .= 'nombre vacio, ';
         $bol = false;
      } else if (strlen($evento['evento']['nombre']) > 256) {
         $campo_error .= 'nombre tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($evento['evento']['descripcion'])){
         $campo_error .= 'descripcion vacío, ';
         $bol = false;
      } else if (strlen($evento['evento']['descripcion']) > 2014) {
         $campo_error .= 'descripcion tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($evento['evento']['inicio'])){
         $campo_error .= 'fecha inicio vacío, ';
         $bol = false;
      } else if (validateDate($evento['evento']['inicio']) == false) {
         $campo_error .= 'la fecha de inicio no es correcta, ';
         $bol = false;
      }

      if(empty($evento['evento']['fin'])){
         $campo_error .= 'fecha fin vacío, ';
         $bol = false;
      } else if (validateDate($evento['evento']['fin']) == false) {
         $campo_error .= 'la fecha de fin no es correcta, ';
         $bol = false;
      }

      if(empty($evento['evento']['direccion'])){
         $evento['evento']['direccion'] = '';
      } else if(strlen($evento['evento']['direccion']) > 256){
         $campo_error .= 'direccion tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($evento['evento']['localidad'])){
         $evento['evento']['localidad'] = '';
      } else if(strlen($evento['localidad']) > 256){
         $campo_error .= 'localidad tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($evento['evento']['cod_postal'])){
         $evento['evento']['cod_postal'] = '';
      } else if(strlen($evento['evento']['cod_postal']) > 5){
         $campo_error .= 'cod_postal tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($evento['evento']['provincia'])){
         $evento['evento']['provincia'] = '';
      } else if(strlen($evento['evento']['provincia']) > 256){
         $campo_error .= 'provincia tiene más longitud de lo permitido, ';
         $bol = false;
      }

      if(empty($evento['evento']['latitud'])){
         $evento['evento']['latitud'] = '';
      } else if(validateLatitud($evento['evento']['latitud']) == false){
         $campo_error .= 'latitud incorrecta, ';
         $bol = false;
      }

      if(empty($evento['evento']['longitud'])){
         $evento['evento']['longitud'] = '';
      } else if(validateLongitud($evento['evento']['longitud']) == false){
         $campo_error .= 'longitud incorrecta, ';
         $bol = false;
      }

      // si la variable bol es falsa mostrará el mensaje, en caso contrario ejecutará la consulta
      if($bol == false){
         $campo_error = substr($campo_error, 0, -2);
         echo $campo_error .= '"}';
      } else{
         // Formateamos las fechas para que las recoga correctamente mysql
          $evento['evento']['inicio'] = date("Y-m-d H:i", strtotime($evento['evento']['inicio']));
           $evento['evento']['fin'] = date("Y-m-d H:i", strtotime($evento['evento']['fin']));
         // Sentencias SQL
         $sql_acontecimiento = "SELECT id FROM acontecimiento WHERE id=:bind_idAcontecimiento";

         $sql_insert = "INSERT INTO evento (id_acontecimiento, nombre, descripcion, inicio, fin, direccion, localidad, cod_postal, provincia, latitud, longitud) 
                        VALUES (:bind_idAcontecimiento, :bind_nombre, :bind_descripcion, :bind_inicio, :bind_fin, :bind_direccion, :bind_localidad, :bind_cod_postal, :bind_provincia, :bind_latitud, :bind_longitud)";
      
         try {
            // Conecta con la base de datos
            $db = connectionDB();
            
            if ($db != null){

               // Prepara y ejecuta de la sentencia del select del acontecimiento. 
               $stmt_comprueba = $db->prepare($sql_acontecimiento);
               $stmt_comprueba->bindParam(":bind_idAcontecimiento", $param_idAcontecimiento, PDO::PARAM_INT);
               $stmt_comprueba->execute();

               // Obtiene un array asociativo con un registro
               $record_acontecimiento = $stmt_comprueba->fetch(PDO::FETCH_ASSOC);

               if ($record_acontecimiento == false){
                  echo '{"error": -11, "message": "El acontecimiento no existe:"}';               
               }else{

                  // Prepara y ejecuta de la sentencia
                  $stmt_insert = $db->prepare($sql_insert);
                  $stmt_insert->bindParam(":bind_idAcontecimiento", $param_idAcontecimiento, PDO::PARAM_INT);
                  $stmt_insert->bindParam(":bind_nombre", $evento['evento']['nombre'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_descripcion", $evento['evento']['descripcion'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_inicio", $evento['evento']['inicio'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_fin", $evento['evento']['fin'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_direccion", $evento['evento']['direccion'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_localidad", $evento['evento']['localidad'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_cod_postal", $evento['evento']['cod_postal'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_provincia", $evento['evento']['provincia'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_latitud", $evento['evento']['latitud'], PDO::PARAM_STR);
                  $stmt_insert->bindParam(":bind_longitud", $evento['evento']['longitud'], PDO::PARAM_STR);
                  $stmt_insert->execute();

                  echo '{"error": 2, "message": "Evento insertado correctamente con el id '.$db->lastInsertId().'"}';
               }
               
               // Cierra la conexión con la base de datos
               $db = null;
            }
         } catch(PDOException $e) {
            echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
         }
      }
   }
});

/**
 * Operación PUT para actualización de un acontecimiento
 */
$app->put('/acontecimiento/:param_id', function ($param_id) {
   // Comprueba el parámetro de entrada
   $param_id = intval($param_id);
   // Obtiene la petición que ha recibido el servidor REST
   $request = \Slim\Slim::getInstance()->request();

   // Obtiene el body de la petición recibida
   $request_body = $request->getBody();

   // Transforma el contenido JSON del body en un array
   $acontecimiento = json_decode($request_body, true, 10);
   
   // Comprueba los errores en el contenido JSON
   if (json_last_error() != JSON_ERROR_NONE) {
      echo '{"error":-21, "message": "Contenido JSON con errores"}';
      // Comprueba que estén abiertas las llaves del acontecimiento en el json
   } else if(!in_array($acontecimiento['acontecimiento'], $acontecimiento)){
      echo '{"error":-21, "message": "Contenido JSON con errores, falta la apertura acontecimiento"}';
   } else {

         // Creamos la variable booleana
         $bol = true;

         // Creamos la variable para mostrar los errores
         $campo_error = '{"error": -22, "message": "Campo con error: ';

         // Comprobamos que exista en el array el tag id
         if(isset($acontecimiento['acontecimiento']['id'])) {
            $campo_error .= 'la id no se puede modificar, ';
            $bol = false;
         } 

         /** Variables de los acontecimientos **/
         $nombre = $acontecimiento['acontecimiento']['nombre'];
         $organizador = $acontecimiento['acontecimiento']['organizador'];
         $descripcion = $acontecimiento['acontecimiento']['descripcion'];
         $tipo = $acontecimiento['acontecimiento']['tipo'];
         $direcccion = $acontecimiento['acontecimiento']['direccion'];
         $localidad = $acontecimiento['acontecimiento']['localidad'];
         $cod_postal = $acontecimiento['acontecimiento']['cod_postal'];
         $provincia = $acontecimiento['acontecimiento']['provincia'];
         $latitud = $acontecimiento['acontecimiento']['latitud'];
         $longitud = $acontecimiento['acontecimiento']['longitud'];
         $telefono = $acontecimiento['acontecimiento']['telefono'];
         $email = $acontecimiento['acontecimiento']['email'];
         $web = $acontecimiento['acontecimiento']['web'];
         $facebook = $acontecimiento['acontecimiento']['facebook'];
         $twitter = $acontecimiento['acontecimiento']['twitter'];
         $instagram = $acontecimiento['acontecimiento']['instagram'];

         if(isset($nombre)) {
            if (!empty($nombre)){
               if (strlen($nombre) > 256) {
                  $campo_error .= 'nombre tiene más longitud de lo permitido, ';
                  $bol = false;
               }
            } else{
               $campo_error .= 'nombre, ';
               $bol = false;
            }
         }

         if(isset($organizador)) {
            if (strlen($organizador) > 256) {
               $campo_error .= 'organizador tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($descripcion)) {
            if (!empty($descripcion)){
               if (strlen($descripcion) > 2014) {
                  $campo_error .= 'descripcion tiene más longitud de lo permitido, ';
                  $bol = false;
               }
            } else{
               $campo_error .= 'descripcion, ';
               $bol = false;
            }
         }

         if(isset($tipo)) {
            if (!empty($tipo)){
               if (strlen($tipo) > 11) {
                  $campo_error .= 'tipo tiene más longitud de lo permitido, ';
                  $bol = false;
               }
            } else {
               $campo_error .= 'tipo, ';
               $bol = false;
            }
         }

         if(isset($acontecimiento['acontecimiento']['inicio'])) {
            if (validateDate($acontecimiento['acontecimiento']['inicio']) == false){
              $campo_error .= 'la fecha de inicio no es válida, ';
              $bol = false;
            } else {
            // Si existe formateamos al fecha para que la recoga bien mysql
            $acontecimiento['acontecimiento']['inicio'] = date("Y-m-d H:i", strtotime($acontecimiento['acontecimiento']['inicio']));
            }
         } 

         if(isset($acontecimiento['acontecimiento']['fin'])) {
            if (validateDate($acontecimiento['acontecimiento']['fin']) == false){
              $campo_error .= 'la fecha de inicio no es válida, ';
              $bol = false;
            } else {
            // Si existe formateamos al fecha para que la recoga bien mysql
            $acontecimiento['acontecimiento']['fin'] = date("Y-m-d H:i", strtotime($acontecimiento['acontecimiento']['fin']));
            }
         } 

         if(isset($direccion)) {
            if (strlen($direccion) > 256) {
               $campo_error .= 'direccion tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($localidad)) {
            if (strlen($localidad) > 256) {
               $campo_error .= 'localidad tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }
         if(isset($cod_postal)) {
            if (strlen($cod_postal) > 5) {
               $campo_error .= 'cod_postal tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($provincia)) {
            if (strlen($provincia) > 256) {
               $campo_error .= 'provincia tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($latitud)) {
            if(validateLatitud($latitud) == false){
               $campo_error .= 'latitud incorrecta, ';
               $bol = false;
            }
         }

         if(isset($longitud)) {
            if(validateLongitud($longitud) == false){
               $campo_error .= 'latitud incorrecta, ';
               $bol = false;
            }
         }

         if(isset($telefono)) {
            if (strlen($telefono) > 9) {
               $campo_error .= 'telefono tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($email)) {
            if (validateEmail($email) == false){
               $campo_error .= 'la página web no es válida, ';
               $bol = false;
            } else if (strlen($email) > 256) {
               $campo_error .= 'email tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }
         

         if(isset($web)) {
            if (validateWeb($web) == false){
               $campo_error .= 'la página web no es válida, ';
               $bol = false;
            } else if (strlen($web) > 256) {
               $campo_error .= 'web tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($facebook)) {
            if (validateFacebook($facebook) == false){
               $campo_error .= 'la página facebook no es válida, ';
               $bol = false;
            } else if (strlen($facebook) > 256) {
               $campo_error .= 'facebook tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($twitter)) {
            if (validateTwitter($twitter) == false){
               $campo_error .= 'la página twitter no es válida, ';
               $bol = false;
            } else if (strlen($twitter) > 256) {
               $campo_error .= 'twitter tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($instagram)) {
            if (strlen($instagram) > 256) {
               $campo_error .= 'instagram tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         // Declaramos un array que contendra los atributos que ha introducido en usuario en el json
         $indicesFromJson = array();

         // Recorremos el array de acontecimiento como indice (atributo) y su valor. Guardamos los indices en el array
         foreach($acontecimiento['acontecimiento'] as $indice=>$valor){
            $indicesFromJson [] = $indice;
         }

         // Creamos un array que serán los posibles atributos que se podrán poner
         $indicesPosibles = array("nombre", "organizador", "descripcion", "tipo", "inicio", "fin", "direccion", "localidad", "cod_postal", "provincia", "latitud", "longitud", "telefono", "email", "web", "facebook", "twitter", "instagram");        

         // Recorremos el array de los indices como valor. Comprobamos que los valores que ha devuelto los indices
         // existan en el array que hemos declarado con lo posibles valores.
         foreach($indicesFromJson as $valor){
            if(array_search($valor, $indicesPosibles) === false){
               $campo_error .= $valor.', ';
               $bol = false; 
            }
         }

         if ($bol == false){
            $campo_error = substr($campo_error, 0, -2);
            echo $campo_error .= '"}';
         }else{

            // sentencia sql que comprobrará que exista el acontecimiento
            $acontecimiento_select = "SELECT id FROM acontecimiento WHERE id=:bind_id";

            // Creamos la consulta SQL de update, pero dejamos el SET para recorrerlo con un foreach
            $acontecimiento_update = "UPDATE acontecimiento SET ";

            // El foreach recorrerá el array de acontecimiento cómo dos valores, indice (ej: nombre) y valor (nombre del acontecimiento).
            foreach($acontecimiento['acontecimiento'] as $indice=>$valor){
               // Agregaremos el indice y el valor a modificar a la sentencia sql.
               $acontecimiento_update .= $indice."='".$valor."', ";
            }

            // Quitamos dos caracteres del string, el cual equivaldrá al espacio y a la coma (,).
            $acontecimiento_update = substr($acontecimiento_update, 0, -2);
            // Añadimos a la sentencia la id que ha de modificar
            $acontecimiento_update .= " WHERE id=:bind_id";

            try {
               // Conecta con la base de datos
               $db = connectionDB();
               
               if ($db != null){

                  // Hacemos un statement preparando la consulta de sql y ejecutandola, para así comprobar si existe el acontecimiento
                  // al que le vamos a insertar el evento
                  $stmt_select = $db->prepare($acontecimiento_select);
                  $stmt_select->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
                  $stmt_select->execute();

                  // Obtiene un array asociativo con un registro
                  $record_acontecimiento = $stmt_select->fetch(PDO::FETCH_ASSOC);

                  // si no existe mostrará un mensaje de error, en caso contrario ejecutará la consulta de actualización
                  if ($record_acontecimiento == false){
                     echo '{"error": -11, "message": "El acontecimiento no existe."}'; 
                  } else{
                     // Prepara la consulta de update
                     $stmt_update = $db->prepare($acontecimiento_update);
                     $stmt_update->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
                     $stmt_update->execute();

                     echo '{"error": 3, "message": "Acontecimiento actualizado correctamente."}';    
                  }

                  $db = null;
               }

            } catch(PDOException $e) {
               echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
            } 
         }
   } 
});


/**
 * Operación PUT para actualización de un evento
 */
$app->put('/evento/:param_id/:param_idAcontecimiento', function ($param_id, $param_idAcontecimiento) {
   // Comprueba el parámetro de entrada
   $param_id = intval($param_id);
   $param_idAcontecimiento = intval($param_idAcontecimiento);
   // Obtiene la petición que ha recibido el servidor REST
   $request = \Slim\Slim::getInstance()->request();

   // Obtiene el body de la petición recibida
   $request_body = $request->getBody();

   // Transforma el contenido JSON del body en un array
   $evento = json_decode($request_body, true, 10);
   
   // Comprueba los errores en el contenido JSON
   if (json_last_error() != JSON_ERROR_NONE) {
      echo '{"error":-21, "message": "Contenido JSON con errores"}';
      // Comprueba que estén abiertas las llaves del evento en el json
   } else if(!in_array($evento['evento'], $evento)){
      echo '{"error":-21, "message": "Contenido JSON con errores, falta la apertura evento"}';
   } else {

         // Creamos la variable booleana
         $bol = true;

         // Creamos la variable para mostrar los errores
         $campo_error = '{"error": -22, "message": "Campo con error: ';

         // Comprobamos que exista en el array el tag id
         if(isset($evento['evento']['id'])) {
            $campo_error .= 'la id no se puede modificar, ';
            $bol = false;
         } 

         if(isset($evento['evento']['id_acontecimiento'])) {
            if(empty($evento['evento']['id_acontecimiento'])){
               $campo_error .= 'la id de acontecimiento no se puede dejar vacia, ';
               $bol = false;
            }
         } 

         if(isset($evento['evento']['nombre'])) {
            if(!empty($evento['evento']['nombre'])){
               if (strlen($evento['evento']['nombre']) > 256) {
                  $campo_error .= 'nombre tiene más longitud de lo permitido, ';
                  $bol = false;
               }
            } else{
               $campo_error .= 'nombre, ';
               $bol = false;
            }
         }

         if(isset($evento['evento']['descripcion'])) {
            if(!empty($evento['evento']['descripcion'])){
               if (strlen($evento['evento']['descripcion']) > 2014) {
                  $campo_error .= 'descripcion tiene más longitud de lo permitido, ';
                  $bol = false;
               }
            } else{
               $campo_error .= 'descripcion, ';
               $bol = false;
            }
         }

         if(isset($evento['evento']['inicio'])) {
            if(!empty($evento['evento']['inicio'])){
               // Comprobamos que sea válida la fecha
               if (validateDate($evento['evento']['inicio']) == false){
                  $campo_error .= 'la fecha de inicio no es válida, ';
                  $bol = false;
               } 
               // Si existe formateamos al fecha para que la recoga bien mysql
               $evento['evento']['inicio'] = date("Y-m-d H:i", strtotime($evento['evento']['inicio']));
             } else{
               $campo_error .= 'inicio, ';
               $bol = false;
            }
         } 

         if(isset($evento['evento']['fin'])) {
            if(!empty($evento['evento']['fin'])){
               if (validateDate($evento['evento']['fin']) == false){
                  $campo_error .= 'la fecha de fin no es válida, ';
                  $bol = false;
               }
               $evento['evento']['fin'] = date("Y-m-d H:i", strtotime($evento['evento']['fin']));
            } else{
               $campo_error .= 'fin, ';
               $bol = false;
            }
         } 

         if(isset($evento['evento']['direccion'])) {
            if (strlen($evento['evento']['direccion']) > 256) {
               $campo_error .= 'direccion tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($evento['evento']['localidad'])) {
            if (strlen($evento['evento']['localidad']) > 256) {
               $campo_error .= 'localidad tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }
         if(isset($evento['evento']['cod_postal'])) {
            if (strlen($evento['evento']['cod_postal']) > 5) {
               $campo_error .= 'cod_postal tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($evento['evento']['provincia'])) {
            if (strlen($evento['evento']['provincia']) > 256) {
               $campo_error .= 'provincia tiene más longitud de lo permitido, ';
               $bol = false;
            }
         }

         if(isset($evento['evento']['latitud'])) {
            if(validateLatitud($evento['evento']['latitud']) == false){
               $campo_error .= 'latitud incorrecta, ';
               $bol = false;
            }
         }

         if(isset($evento['evento']['longitud'])) {
            if(validateLatitud($evento['evento']['longitud']) == false){
               $campo_error .= 'longitud incorrecta, ';
               $bol = false;
            }
         }

          // Declaramos un array que contendra los atributos que ha introducido en usuario en el json
         $indicesFromJson = array();

         // Recorremos el array de evento como indice (atributo) y su valor. Guardamos los indices en el array
         foreach($evento['evento'] as $indice=>$valor){
            $indicesFromJson [] = $indice;
         }

         // Creamos un array que serán los posibles atributos que se podrán poner
         $indicesPosibles = array("id_acontecimiento", "nombre", "descripcion", "inicio", "fin", "direccion", "localidad", "cod_postal", "provincia", "latitud", "longitud");        

         // Recorremos el array de los indices como valor. Comprobamos que los valores que ha devuelto los indices
         // existan en el array que hemos declarado con lo posibles valores.
         foreach($indicesFromJson as $valor){
            if(array_search($valor, $indicesPosibles) === false){
               $campo_error .= $valor.', ';
               $bol = false; 
            }
         }

         if ($bol == false){
            $campo_error = substr($campo_error, 0, -2);
            echo $campo_error .= '"}';
         }else{

            // sentencia sql que comprobrará que exista el acontecimiento
            $acontecimiento_select = "SELECT id FROM acontecimiento WHERE id=:bind_idAcontecimiento";

            // sentencia sql que comprobrará que exista el evento
            $evento_select = "SELECT id, id_acontecimiento FROM evento WHERE id=:bind_id AND id_acontecimiento=:bind_idAcontecimiento";

            // Creamos la consulta SQL de update, pero dejamos el SET para recorrerlo con un foreach
            $evento_update = "UPDATE evento SET ";

            // El foreach recorrerá el array de evento cómo dos valores, indice (ej: nombre) y valor (nombre del evento).
            foreach($evento['evento'] as $indice=>$valor){
               // Agregaremos el indice y el valor a modificar a la sentencia sql.
               $evento_update .= $indice."='".$valor."', ";
            }

            // Quitamos dos caracteres del string, el cual equivaldrá al espacio y a la coma (,).
            $evento_update = substr($evento_update, 0, -2);
            // Añadimos a la sentencia la id que ha de modificar
            $evento_update .= " WHERE id=:bind_id";

            try {
               // Conecta con la base de datos
               $db = connectionDB();
               
               if ($db != null){
                  // Prepara y ejecuta de la sentencia del select del acontecimiento. 
                  $stmt_compruebaA = $db->prepare($acontecimiento_select);
                  $stmt_compruebaA->bindParam(":bind_idAcontecimiento", $param_idAcontecimiento, PDO::PARAM_INT);
                  $stmt_compruebaA->execute();

                  // Obtiene un array asociativo con un registro
                  $record_acontecimiento = $stmt_compruebaA->fetch(PDO::FETCH_ASSOC);

                  if ($record_acontecimiento == false){
                     echo '{"error": -11, "message": "El acontecimiento no existe."}';             
                  }else{

                     // Prepara y ejecuta de la sentencia del select del evento. 
                     $stmt_compruebaE = $db->prepare($evento_select);
                     $stmt_compruebaE->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
                     $stmt_compruebaE->bindParam(":bind_idAcontecimiento", $param_idAcontecimiento, PDO::PARAM_INT);
                     $stmt_compruebaE->execute();

                     // Obtiene un array asociativo con un registro
                     $record_evento = $stmt_compruebaE->fetch(PDO::FETCH_ASSOC);
                     // si no existe mostrará un mensaje de error, en caso contrario ejecutará la consulta de actualización
                     if ($record_evento == false){
                        echo '{"error": -11, "message": "El evento no existe."}'; 
                     } else{
                        // Prepara la consulta de update
                        $stmt_update = $db->prepare($evento_update);
                        $stmt_update->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
                        $stmt_update->execute();

                        echo '{"error": 4, "message": "Evento actualizado correctamente."}';    
                     }

                     $db = null;
                  }
               }
            } catch(PDOException $e) {
               echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
            } 
         }
   } 
});

/**
 * Operación DELETE para el borrado de un acontecimiento mediante su identificador
 */
$app->delete('/acontecimiento/:param_id', function ($param_id) {
   // Comprueba el parámetro de entrada
   $param_id = intval($param_id);
   
   // Sentencias SQL
   $select_acontecimiento = "SELECT id FROM acontecimiento WHERE id=:bind_id";

   $delete_acontecimiento = "DELETE FROM acontecimiento WHERE id=:bind_id";

      try{
         // Conecta con la base de datos
         $db = connectionDB();
      
         if ($db != null){
            // Prepara y ejecuta de la sentencia del select del acontecimiento. 
            $stmt_compruebaA = $db->prepare($select_acontecimiento);
            $stmt_compruebaA->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
            $stmt_compruebaA->execute();

            // Obtiene un array asociativo con un registro
            $record_acontecimiento = $stmt_compruebaA->fetch(PDO::FETCH_ASSOC);

            if ($record_acontecimiento == false){
               echo '{"error": -11, "message": "El acontecimiento no existe."}';
            } else{
               // Prepara y ejecuta la sentencia
               $stmt_acontecimiento = $db->prepare($delete_acontecimiento);
               $stmt_acontecimiento->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
               $stmt_acontecimiento->execute();

               echo '{"error": 5, "message": "El Acontecimiento con id '.$param_id.', ha sido eliminado correctamente."}';
            }
            
         }

      } catch (PDOException $e) {
         echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
      }
});

/**
 * Operación DELETE para el borrado de un evento mediante su identificador, y el de acontecimiento
 */
$app->delete('/evento/:param_id/:param_idAcontecimiento', function ($param_id, $param_idAcontecimiento) {
   // Comprueba los parámetro de entrada
   $param_id = intval($param_id);
   $param_idAcontecimiento = intval($param_idAcontecimiento);
   
   // Sentencias SQL
   $sql_acontecimiento = "SELECT id FROM acontecimiento WHERE id=:bind_idAcontecimiento";
   $sql_evento = "SELECT id, id_acontecimiento FROM evento WHERE id=:bind_id AND id_acontecimiento=:bind_idAcontecimiento";

   $sql_borrar = "DELETE FROM evento WHERE id=:bind_id AND id_acontecimiento=:bind_idAcontecimiento";

      try{
         // Conecta con la base de datos
         $db = connectionDB();
      
         if ($db != null){

            // Prepara y ejecuta de la sentencia del select del acontecimiento. 
            $stmt_compruebaA = $db->prepare($sql_acontecimiento);
            $stmt_compruebaA->bindParam(":bind_idAcontecimiento", $param_idAcontecimiento, PDO::PARAM_INT);
            $stmt_compruebaA->execute();

            // Obtiene un array asociativo con un registro
            $record_acontecimiento = $stmt_compruebaA->fetch(PDO::FETCH_ASSOC);

             // Prepara y ejecuta de la sentencia del select del evento. 
            $stmt_compruebaE = $db->prepare($sql_evento);
            $stmt_compruebaE->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
            $stmt_compruebaE->bindParam(":bind_idAcontecimiento", $param_idAcontecimiento, PDO::PARAM_INT);
            $stmt_compruebaE->execute();

            // Obtiene un array asociativo con un registro
            $record_evento = $stmt_compruebaE->fetch(PDO::FETCH_ASSOC);

            if ($record_acontecimiento == false){
               echo '{"error": -11, "message": "El acontecimiento no existe."}'; 
            }else if($record_evento == false) {
               echo '{"error": -12, "message": "El evento no existe."}';             
            }else{

               // Prepara y ejecuta la sentencia
               $stmt_borrar = $db->prepare($sql_borrar);
               $stmt_borrar->bindParam(":bind_id", $param_id, PDO::PARAM_INT);
               $stmt_borrar->bindParam(":bind_idAcontecimiento", $param_idAcontecimiento, PDO::PARAM_INT);
               $stmt_borrar->execute();

               echo '{"error": 6, "message": "El Evento con id '.$param_id.', ha sido eliminado correctamente."}';    
            } 
         }

      } catch (PDOException $e) {
         echo '{"error": -9, "message": "Excepción en la base de datos: '.$e->getMessage().'"}';
      }
});

// Inicia la aplicación con el servidor REST 
$app->run();

?>