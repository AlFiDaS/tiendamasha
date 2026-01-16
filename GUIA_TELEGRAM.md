# 📱 Guía de Configuración de Notificaciones por Telegram

## 🎯 ¿Qué hace esto?

Cuando alguien hace una compra en tu web, recibirás una notificación instantánea en tu celular por Telegram con todos los detalles del pedido.

## 📋 Pasos para Configurar

### 1️⃣ Crear tu Bot de Telegram

1. Abre Telegram en tu celular o computadora
2. Busca **@BotFather** (es el bot oficial de Telegram para crear bots)
3. Envía el comando: `/newbot`
4. Sigue las instrucciones:
   - Te pedirá un nombre para el bot (ejemplo: "Lume Notificaciones")
   - Te pedirá un username (debe terminar en "bot", ejemplo: "lume_notificaciones_bot")
5. **¡IMPORTANTE!** Guarda el **token** que te da BotFather
   - Se ve así: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`
   - Este es tu **Bot Token**

### 2️⃣ Obtener tu Chat ID

1. Busca **@userinfobot** en Telegram
2. Envía cualquier mensaje (puede ser "hola")
3. Te responderá con tu información, incluyendo tu **Chat ID**
   - Es un número como: `123456789`
   - **¡IMPORTANTE!** Guarda este número

### 3️⃣ Configurar en tu Proyecto

1. Abre el archivo `config.php`
2. Busca la sección **CONFIGURACIÓN DE TELEGRAM** (alrededor de la línea 122)
3. Reemplaza los valores:

```php
define('TELEGRAM_BOT_TOKEN', 'TU_BOT_TOKEN_AQUI');
define('TELEGRAM_CHAT_ID', 'TU_CHAT_ID_AQUI');
```

Por ejemplo:
```php
define('TELEGRAM_BOT_TOKEN', '123456789:ABCdefGHIjklMNOpqrsTUVwxyz');
define('TELEGRAM_CHAT_ID', '123456789');
```

4. Guarda el archivo

### 4️⃣ Probar la Configuración

Para probar que funciona, puedes crear un archivo de prueba `test-telegram.php` en la raíz:

```php
<?php
require_once 'config.php';
require_once 'helpers/telegram.php';

$message = "🧪 <b>Prueba de Notificación</b>\n\nSi recibes este mensaje, ¡la configuración está correcta!";
sendTelegramNotification($message);
echo "Mensaje enviado. Revisa tu Telegram.";
```

Luego ejecútalo desde tu navegador: `http://tu-dominio.com/test-telegram.php`

Si recibes el mensaje en Telegram, ¡está todo listo! 🎉

## 📨 ¿Cuándo recibirás notificaciones?

Recibirás una notificación cuando:

1. ✅ **Se crea una nueva orden** (cualquier método de pago)
2. ✅ **Un pago se aprueba** (cuando MercadoPago confirma el pago)

## 📱 Formato de las Notificaciones

Las notificaciones incluyen:
- 🛒 Número de orden
- ✅ Estado del pago
- 👤 Datos del cliente (nombre, email, teléfono)
- 💳 Método de pago
- 📦 Tipo de envío
- 🛍️ Lista de productos
- 💰 Total a pagar
- 📍 Dirección de envío (si aplica)
- 🔗 Link directo al panel de administración

## ❓ Troubleshooting

### No recibo notificaciones

1. **Verifica que el Bot Token sea correcto**
   - Debe tener el formato: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`
   - No debe tener espacios al inicio o final

2. **Verifica que el Chat ID sea correcto**
   - Debe ser solo números
   - No debe tener comillas ni espacios

3. **Verifica los logs del servidor**
   - Revisa los logs de PHP para ver si hay errores
   - Los errores se guardan en el log de errores de PHP

4. **Prueba con el archivo de prueba**
   - Usa el archivo `test-telegram.php` para verificar la conexión

### Error: "Telegram no está configurado"

- Verifica que hayas agregado las constantes en `config.php`
- Asegúrate de que no haya errores de sintaxis en `config.php`

### El bot no responde

- Verifica que hayas creado el bot correctamente con @BotFather
- Asegúrate de que el token sea el correcto

## 🔒 Seguridad

- **NUNCA** compartas tu Bot Token públicamente
- **NUNCA** subas `config.php` a repositorios públicos
- El Chat ID es personal, solo tú recibirás las notificaciones

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs de errores de PHP
2. Verifica que cURL esté habilitado en tu servidor
3. Asegúrate de que tu servidor pueda hacer conexiones HTTPS a `api.telegram.org`

---

¡Listo! Con esto ya recibirás notificaciones instantáneas en tu celular cada vez que alguien haga una compra. 🎉

