/*************************************************** 
Librerías adicionales:
  ----> http://www.adafruit.com/
 ****************************************************/
 
/*Cargamos las librerías necesarias; unas estándar de Arduino
y otras propias del fabricante de los shields usados (Adafruit).*/
#include <Adafruit_CC3000.h>
#include <ccspi.h>
#include <SPI.h>
#include <string.h>
#include "utility/debug.h"

#include <Wire.h>
#include <Adafruit_MCP23017.h>
#include <Adafruit_RGBLCDShield.h>

#include <Adafruit_NFCShield_I2C.h>

/* Definimos pines para interrupción y control
del shield WiFi + tarjeta SD */
#define ADAFRUIT_CC3000_IRQ   3  // Debe ser un pin habilitado para IRQ (2 ó 3 en Arduino UNO)
// These can be any two pins
#define ADAFRUIT_CC3000_VBAT  5
#define ADAFRUIT_CC3000_CS    10 // Pin para SD card select

// Definimos pines para interrupción y control del shield RFID/NFC
#define IRQ   (2)
#define RESET (3)  // No conectado por defecto en el shield NFC

// Creamos el objeto que representa a nuestro lector/grabador RFID
Adafruit_NFCShield_I2C nfc(IRQ, RESET);

// Usamos SPI para los pines restantes
// En Arduino UNO, SCK = 13, MISO = 12, y MOSI = 11
Adafruit_CC3000 cc3000 = Adafruit_CC3000(ADAFRUIT_CC3000_CS, ADAFRUIT_CC3000_IRQ, ADAFRUIT_CC3000_VBAT,
                                         SPI_CLOCK_DIVIDER); // se puede cambiar la velocidad de reloj

#define WLAN_SSID       "Heidelberg"            // SSID para el acceso WiFi. No puede tener más de 32 caracteres
#define WLAN_PASS       "h31d3lb3rg"	    // Password para el acceso WiFi
// El tipo de encriptación puede ser definida como WLAN_SEC_UNSEC, WLAN_SEC_WEP, WLAN_SEC_WPA or WLAN_SEC_WPA2
#define WLAN_SECURITY   WLAN_SEC_WPA2

#define IDLE_TIMEOUT_MS  4000      // Tiempo a esperar (en milisegundos) antes de cerrar la conexión,
                                   // si no se reciben datos. Se puede aumentar o disminuir según lo rápido
                                   // que sea el servidor en responder.

// Definimos el servidor y la página a la que queremos acceder
#define WEBSITE      "www.heidelbergschule.com"
#define WEBPAGE      "/Proyectos/CreaTablaPasajeros.php"


uint32_t ip;  // Aquí se almacena la dirección IP del servidor una vez que se establece la conexión.
Adafruit_RGBLCDShield lcd = Adafruit_RGBLCDShield();

// Con la definición de estas constantes facilitamos la selección del color para el display
#define RED 0x1
#define YELLOW 0x3
#define GREEN 0x2
#define TEAL 0x6
#define BLUE 0x4
#define VIOLET 0x5
#define WHITE 0x7

#define NUM_LINEAS 6	// Limitamos el número de lineas a controlar a 5 y el número máximo de pasajeros a 50,
#define TOTAL_PASAJEROS 50	// para no desbordar la memoria de la Arduino UNO.
							// En un montaje para uso en entorno real, podríamos usar una placa Arduino con más prestaciones, como la DUE. 
    String recibido="";	// lo usamos como buffer para almacenar los fragmentos de información recibidos de la página Web (un script PHP que
						// crea y comunica los listados diarios de alumnos y les asigna el transporte correspondiente.
    
	// Estructura que almacena la información que necesitamos de cada pasajero
	struct InfoPasajero{
      int id;
      long rfid;	// Cada alumno posee una tarjeta RFID pasiva MiFare Classic de 1K
      byte linea;
      byte parada;
      boolean marca;	// Usada para "marcar" un pasajero para poder realizar diversas operaciones con él sin tener que almacenar
						// arrays con IDs, que usarían demasiada de la escasa memoria disponible en la UNO.
    }Pasajeros[TOTAL_PASAJEROS];	// Creamos un array de este tipo de estructura con tantos elementos como número máximo de pasajeros hayamos definido previamente.
    
    byte pasajerosPorLinea[NUM_LINEAS];
    
    int pasajerosContados=0;
    byte lineaElegida=2;	// Cada aparato en una situación de uso real tendrá asignado una línea de guagua por defecto, aunque se puede cambiar al iniciar el dispositivo.
    int tarjetasLeidas=0;
    boolean lineaCompletaOK=false;

/**********************   SETUP    **********************/

void setup(void)
{
// Inicializamos los dispositivos conectados, tal y como indica el fabricante.
  Serial.begin(115200);
  nfc.begin();
  nfc.SAMConfig();

  if (!cc3000.begin())
  {
    while(1); // Para, si no puede inicializar el shield WiFi
  }
  lcd.begin(16, 2);
  lcd.setBacklight(WHITE);

  //Serial.print(F("\nAttempting to connect to "));
  Serial.println(WLAN_SSID);	// Para control. Imprime la SSID del punto de acceso WiFi en el monitor serial.
  lcd.print(F("Conectando con")); // Usamos la función F() en cadenas que no van a variar para ocupar la menor cantidad posible de memoria SRAM.
  lcd.setCursor(0,1);
  lcd.print(WLAN_SSID);	//Imprime en el display la SSID del acceso WiFi al que se ha conectado.
  if (!cc3000.connectToAP(WLAN_SSID, WLAN_PASS, WLAN_SECURITY)) {
    Serial.println(F("¡Conexión fallida!"));
    while(1);	// Para, si no puede conectar con la red WiFi.
  }
   
  Serial.println(F("¡Conectado!"));
  lcd.setCursor(0,0);
  lcd.setBacklight(RED);
  lcd.clear();
  lcd.print(F("Conectado"));  

  while (!cc3000.checkDHCP())
  {
    delay(100); // Espera a que se complete el DHCP. Deberíamos insertar algún tipo de timeout
  }  

  ip = 0;
   // Intentamos obtener la dirección IP del sitio Web al que queremos conectar.
  Serial.print(WEBSITE); Serial.print(F(" -> "));
  while (ip == 0) {
    if (! cc3000.getHostByName(WEBSITE, &ip)) {
      Serial.println(F("IP no resuelta"));
    }
    delay(500);
  }

  cc3000.printIPdotsRev(ip);
  
   /* 
     Intentamos conectar al sitio web.
     Nota: usamos el protocolo HTTP/1.1 según recomienda el fabricante para evitar desconexiones antes de que finalice la transferencia de datos.
  */
  //Serial.print(F("Free RAM: ")); Serial.println(getFreeRam(), DEC);          

  Adafruit_CC3000_Client www = cc3000.connectTCP(ip, 80);
  if (www.connected()) {
    lcd.setBacklight(RED);
    lcd.clear();
    lcd.setCursor(0,0);
    lcd.print(F("Recibiendo datos"));

    www.fastrprint(F("GET "));
    www.fastrprint(WEBPAGE);
    www.fastrprint(F(" HTTP/1.1\r\n"));
    www.fastrprint(F("Host: ")); www.fastrprint(WEBSITE); www.fastrprint(F("\r\n"));
    www.fastrprint(F("\r\n"));
    www.println();
  } else {
    Serial.println(F("Error de conexión Web"));
    lcd.setBacklight(RED);    
    return;
  }
//Serial.print(F("Free RAM: ")); Serial.println(getFreeRam(), DEC);          
 
  lcd.setCursor(0, 0);
  
// Lee datos hasta que se cierra la conexión o se supera el tiempo sin comunicación definido en IDLE_TIMEOUT_MS
  unsigned long lastRead = millis();
  while (www.connected() && (millis() - lastRead < IDLE_TIMEOUT_MS)) {
      
    boolean siguenDatos=false;	// En algunas conexiones se envían caracteres "impredecibles" que preceden al envío real
								// de los datos que necesitamos. Este semáforo se activa al detectar la marca que hemos
                               // puesto para identificar el inicio de la cadena de datos que queremos.
	byte pos=0;	// Puntero que nos permite identificar la subcadena que vamos a asignar a cada variables miembro de cada Pasajero.
    int indx=0;	// Nos permitirá seleccionar consecutivamente cada Pasajero para asignarle valor a sus variables miembro.
    while (www.available()) {
      char c = www.read();	// Lee cada carácter del stream de entrada y lo va concatenando -hasta 50 caracteres porque era más cómodo para localizar errores-
      //Serial.write(c);
      recibido.concat(c);
      if (recibido.length()>50){
        //Serial.print(recibido.substring(0,40));
        recibido=recibido.substring(40,recibido.length());	// Luego eliminamos caracteres que sabemos que no contienen información útil para mantener recibido "manejable"
      }
		// El script PHP devuelve una cadena de datos separados por ";". Si ya hemos detectado la marca de inicio de datos (siguenDatos), se rellena el array Pasajeros.
      if (c==';'){
        recibido=recibido.substring(0,recibido.length()-1);
          if (siguenDatos){
            switch (pos){
              case 0:
                Pasajeros[indx].id=recibido.toInt();
                Pasajeros[indx].marca=false;
                pos++;
                break;
              case 1:
                Pasajeros[indx].rfid=recibido.toInt();
                pos++;
                break;
              case 2:
                Pasajeros[indx].linea=recibido.toInt();
                pos++;
                break;
              case 3:
                Pasajeros[indx].parada=recibido.toInt();
                pos=0;
                break;
             } // fin switch pos        
           if (pos==0)indx++;
          } // fin if siguenDatos
        if (recibido.substring(recibido.length()-5,recibido.length()).toInt()==-2222){// La marca de inicio de datos es "-2222" -...sí, podríamos usar una mejor
          siguenDatos=true;
        }
        recibido="";
      } //fin if c==';'
      //Serial.print(c);
      lastRead = millis();
      
        } //fin while available         

    }
//    for (int h=0;h<50;h++){
//      Serial.print(Pasajeros[h].id);Serial.print("-");Serial.print(Pasajeros[h].rfid);Serial.print("-");Serial.println(Pasajeros[h].linea);
//    }
    if (!cuentaPasajerosPorLinea()){// Llamada a una función que devuelve el número de pasajeros cuya tarjeta espera leer el dispositivo.
      lcd.setBacklight(RED);
      lcd.clear();
      lcd.setCursor(0,0);
      lcd.print(F("Recargando datos"));
      Serial.println(F("Recargando datos"));
      softReset();
    }
  
  www.close();	/* Cierra el stream de datos proveniente de la página web. Es importante, porque hemos comprobado que si no se hace
				y se vuelve a intentar establecer una conexión web, el shield WiFi hace cosas impredecibles.*/

  //Serial.println(F("\n\nDesconectando"));
    lcd.setBacklight(GREEN);
    lcd.clear();
    lcd.setCursor(0,0);
    lcd.print(F("Desconectado"));
    cc3000.disconnect();
    delay(500);
    /*
    for (int n=0;n<50;n++){
      Serial.print(n);Serial.print(" - ");
      Serial.print(Pasajeros[n].rfid);
      Serial.print(" - ");
      Serial.print(Pasajeros[n].linea);
      Serial.print(" - ");
      Serial.println(Pasajeros[n].parada);
    Serial.print(F("Free RAM: ")); Serial.println(getFreeRam(), DEC);     
    }
    */
  seleccionaLinea();	/* Permite seleccionar la línea de guaguas que controlará el dispositivo. Tenemos que definir una tarjeta "maestra"
						que permita el cambio de línea en cualquier momento */
}

/**********************   LOOP    **********************/

void loop(void)
{
  if (pasajerosContados < pasajerosPorLinea[lineaElegida] || lineaCompletaOK){// Intenta leer tantas tarjetas como Pasajeros se esperan en la línea seleccionada
    lcd.clear();
    lcd.setBacklight(BLUE);
    lcd.setCursor(0,0);
    lcd.print(F("Espero Tarjeta"));
    lcd.setCursor(0,1);
    lcd.print(pasajerosContados); lcd.print(F("/")); lcd.print(pasajerosPorLinea[lineaElegida]);
    lcd.setCursor(11,1); lcd.print(F("L:")); lcd.print(lineaElegida);   
    //lcd.print(getFreeRam(),DEC);

  uint8_t detectado;
  uint8_t uid[] = { 0, 0, 0, 0 };  // Buffer para almacenar el UID devuelto
  uint8_t uidLength;               // Longitud del UID (4 or 7 bytes)
    
  /* Espera hasta detectar una tarjeta ISO14443A (Mifare, etc.). Una vez detectada
   el array 'uid' contendrá los bytes del UID de la tarjeta y uidLength indicará si la uid is de 4 bytes (Mifare Classic)
   ó 7 bytes (Mifare Ultralight). Nosotros usamos tarjetas Mifare Classic con uid de 4 bytes.*/
  detectado = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength);

lcd.clear();
//lcd.print(detectado);
  if (detectado) { // Si se detecta una tarjeta válida...
    unsigned long cardUID=0;
    cardUID=uid[0]+uid[1]*256L+uid[2]*65536L+uid[3]*16777216L; // Recrea el UID (long) de la tarjeta a partir de los 4 bytes
    lcd.print(cardUID);
    if(tarjetaMaestra(cardUID)){ //Si la tarjeta detectada es la tarjeta maestra...
      //Serial.println(F("Tarjeta maestra detectada"));
      lcd.clear();
      lcd.setCursor(0,0);
      lcd.print(F("Tarjeta maestra"));
      lcd.setCursor(0,1);
      lcd.print(F("detectada"));
      tone(8,550,250);
      delay(500);
      seleccionaLinea(); //...vuelve a seleccionar línea y reinicia algunas variables
      tarjetasLeidas=0;
      pasajerosContados=0;
      lineaCompletaOK=false;
    }
    boolean reconocida=false;
    for (int n=0;n<50;n++){
      if (Pasajeros[n].rfid==cardUID){	// Si se reconoce la tarjeta...
        reconocida=true;
        if (Pasajeros[n].linea==lineaElegida){ //Si el pasajero titular de la tarjeta tiene asignada la misma linea que el "Cacharrito"...
          lcd.clear();
          lcd.setBacklight(GREEN);
          lcd.setCursor(0,0);
          lcd.print(cardUID);
          //Serial.print(cardUID);Serial.print(F(" ----> "));Serial.print(Pasajeros[n].linea);
          //if (Pasajeros[n].marca) Serial.println(" ya leida."); else Serial.print("\n");
          lcd.setCursor(0,1);
          lcd.print(F("Linea "));lcd.print(Pasajeros[n].linea);lcd.print(F("  P: "));lcd.print(Pasajeros[n].parada);
          if (Pasajeros[n].marca){ // Si ya ha sido leída previamente...
            lcd.setCursor(11,0);lcd.print(F("leida")); // Lo indica en el display y no incrementa la cuenta de tarjetas leídas.
          }else{
          tarjetasLeidas++; // Si no, incrementa la cuenta de tarjetas leídas
          }
          if (!Pasajeros[n].marca) pasajerosContados++;
          Pasajeros[n].marca=true; // Marca al pasajero para no volver a contarlo
          tone(8,550,500);
          ////Sonido de confirmación
        } else { // Si la tarjeta es reconocida, pero su titular no pertenece a esta línea...
          lcd.clear();
          lcd.setBacklight(RED);
          lcd.setCursor(0,0);
          lcd.print(cardUID);
          //Serial.print(cardUID);Serial.print(F(" ----> "));Serial.print(Pasajeros[n].linea);
          //if (Pasajeros[n].marca) Serial.println(" ya leida."); else Serial.print("\n");
          lcd.setCursor(0,1);
          if (Pasajeros[n].linea>0){
            lcd.print(F("Ir a L:"));lcd.print(Pasajeros[n].linea);lcd.print(F(" P:"));lcd.print(Pasajeros[n].parada);
          }else{
            lcd.print(F("Hoy a NO MICRO"));
          }
          ////Alarma acústica
          while (!lcd.readButtons()){ // Espera confirmación de lectura del aviso.
            tone(8,900,250);delay(500);
          }
        }
      }
    }
    if (!reconocida && !tarjetaMaestra(cardUID)){ // Si la tarjeta es detectada, pero no es reconocida
      lcd.clear();
          lcd.setBacklight(RED);
          lcd.setCursor(0,0);
          lcd.print(cardUID);
          lcd.setCursor(0,1);
          lcd.print(F("NO RECONOCIDA"));
          while (!lcd.readButtons()){
            tone(8,1200,150);delay(200);
          }
    }
  }
  
  delay(500);
  lcd.clear();
  } else {
    // código para cuando todas las tarjetas estén leídas
    
    lcd.setBacklight(YELLOW);
    lcd.setCursor(0,0);
    lcd.print(F("L:"));lcd.print(lineaElegida);lcd.print(F(" completa."));
    while (!leePulsadores(lcd));
    lcd.clear();
    lcd.setBacklight(WHITE);
    lcd.setCursor(0,0);
    lineaCompletaOK=true;
  }
}

/************** FUNCIONES ********************/

char leePulsadores(Adafruit_RGBLCDShield disp)
{
  uint8_t pulsador = disp.readButtons();
  if (pulsador) {
    if (pulsador & BUTTON_UP) {
      //lineaElegida++;
      //Serial.println("UP");
      return 'U';
    }
    if (pulsador & BUTTON_DOWN) {
      //disp.print("DOWN ");
      disp.setBacklight(YELLOW);
      return 'D';
    }
    if (pulsador & BUTTON_LEFT) {
      //disp.print("LEFT ");
      disp.setBacklight(GREEN);
      return 'L';
    }
    if (pulsador & BUTTON_RIGHT) {
      //disp.print("RIGHT ");
      disp.setBacklight(TEAL);
      return 'R';
    }
    if (pulsador & BUTTON_SELECT) {
      //disp.print("SELECT ");
      disp.setBacklight(VIOLET);
      return 'S';
    }
  }
  
}


void seleccionaLinea(){
  boolean salir=false;
  lcd.clear();
  lcd.setBacklight(VIOLET);
  while (!salir){ 
    uint8_t pulsador=lcd.readButtons();   
    lcd.setCursor(0,0);
    lcd.print("Selecc. linea");
    lcd.setCursor(0,1);
    lcd.print(lineaElegida);lcd.print("        ");
    if(pulsador&BUTTON_DOWN){
      lineaElegida--;
      if (lineaElegida<1)lineaElegida=NUM_LINEAS-1;
      delay(100);}
    if(pulsador&BUTTON_UP) {
      lineaElegida++;
      if (lineaElegida>NUM_LINEAS-1)lineaElegida=1;
      delay(100);}
    if(pulsador&BUTTON_SELECT) salir=true;
  }
}

boolean cuentaPasajerosPorLinea()
{
  byte lineasVacias=0;
  
  for (int m=0;m<NUM_LINEAS;m++) pasajerosPorLinea[m]=0;
  for (int n=0;n<TOTAL_PASAJEROS;n++){
    if (Pasajeros[n].linea!=0){
      pasajerosPorLinea[Pasajeros[n].linea]=pasajerosPorLinea[Pasajeros[n].linea]++;
    }else{
      if (Pasajeros[n].rfid !=0){
        pasajerosPorLinea[Pasajeros[n].linea]=pasajerosPorLinea[Pasajeros[n].linea]++;
      }
     if (pasajerosPorLinea[n]==0) lineasVacias++; 
    }
  }
  for (int m=0;m<NUM_LINEAS;m++){
    Serial.print("\n");Serial.print(m);Serial.print(" --> ");Serial.print(pasajerosPorLinea[m]);
  }
  Serial.print("\n");
  if (lineasVacias==NUM_LINEAS){
    return false;
  } else {
    return true;
  }
}

boolean tarjetaMaestra(unsigned long IDtarjeta)
{
  return (IDtarjeta==19032192);
}

void softReset()
{
  asm volatile(" jmp 0");
}  
