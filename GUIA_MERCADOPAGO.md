# 🔐 Guía de Configuración de MercadoPago

## 📋 Pasos para Configurar MercadoPago Checkout Pro

### 1️⃣ Crear Cuenta en MercadoPago

Si aún no tienes cuenta:
1. Ve a: https://www.mercadopago.com.ar/
2. Clic en "Registrarte" o "Crear cuenta"
3. Completa el formulario con tus datos
4. Verifica tu email y teléfono

---

### 2️⃣ Acceder al Panel de Desarrolladores

1. Inicia sesión en MercadoPago
2. Ve a: https://www.mercadopago.com.ar/developers/panel
3. O desde el menú: **"Desarrolladores"** → **"Tus integraciones"**

---

### 3️⃣ Crear una Aplicación (App)

1. En el panel de desarrolladores, clic en **"Crear aplicación"**
2. Completa los datos:
   - **Nombre**: LUME - Velas Artesanales (o el que prefieras)
   - **Producto**: Selecciona **"Checkout Pro"**
   - **Plataforma**: Web
3. Clic en **"Crear"**

---

### 4️⃣ Obtener Credenciales de PRUEBA (Test)

**⚠️ IMPORTANTE: Empieza siempre con credenciales de PRUEBA**

1. En tu aplicación, busca la sección **"Credenciales de prueba"**
2. Verás dos credenciales:
   - **Public Key (Clave pública)**: No la necesitamos para Checkout Pro
   - **Access Token (Token de acceso)**: **ESTA ES LA QUE NECESITAS** ✅

3. Clic en **"Ver credenciales"** o **"Mostrar"** para ver tu Access Token
4. Copia el Access Token de prueba (empieza con "TEST-...")

---

### 5️⃣ Configurar en tu Proyecto

1. Abre el archivo `config.php`
2. Busca la línea:
   ```php
   define('MERCADOPAGO_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_AQUI');
   ```
3. Reemplaza `'TU_ACCESS_TOKEN_AQUI'` con tu Access Token de prueba:
   ```php
   define('MERCADOPAGO_ACCESS_TOKEN', 'TEST-tu-token-de-prueba-aqui');
   define('MERCADOPAGO_TEST_MODE', true);
   ```

---

### 6️⃣ Probar el Checkout

1. Agrega productos al carrito en tu sitio
2. Completa el formulario con tus datos
3. Haz clic en "Pagar con MercadoPago"
4. Serás redirigido al checkout de MercadoPago

**💡 Tarjetas de Prueba:**

Para probar pagos, usa estas tarjetas de prueba:

| Tarjeta | Número | CVV | Fecha | Nombre | Resultado |
|---------|--------|-----|-------|--------|-----------|
| Visa | 4509 9535 6623 3704 | 123 | Cualquier fecha futura | Cualquier nombre | ✅ Aprobado |
| Mastercard | 5031 7557 3453 0604 | 123 | Cualquier fecha futura | Cualquier nombre | ✅ Aprobado |
| Visa | 4013 5406 8274 6260 | 123 | Cualquier fecha futura | Cualquier nombre | ❌ Rechazado |
| Mastercard | 5250 6421 6478 2468 | 123 | Cualquier fecha futura | Cualquier nombre | ⏳ Pendiente |

**Más tarjetas de prueba:** https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/testing

---

### 7️⃣ Configurar Webhook (Notificaciones)

MercadoPago necesita saber dónde enviar notificaciones cuando cambia el estado de un pago.

1. En tu aplicación de MercadoPago, ve a **"Webhooks"** o **"Notificaciones"**
2. Ingresa la URL de tu webhook:
   ```
   https://tu-dominio.com/api/mercadopago/webhook.php
   ```
   Por ejemplo:
   ```
   https://hechoencorrientes.com/lumetest/api/mercadopago/webhook.php
   ```
3. Eventos a recibir: Selecciona **"Pagos"**
4. Guarda la configuración

**Nota:** Para pruebas locales, puedes usar ngrok o similar para exponer tu servidor local.

---

### 8️⃣ Pasar a PRODUCCIÓN (Cuando estés listo)

Una vez que hayas probado todo y estés listo para recibir pagos reales:

1. En el panel de MercadoPago, ve a **"Credenciales de producción"**
2. Copia tu **Access Token de producción** (empieza con "APP_USR-...")
3. Actualiza `config.php`:
   ```php
   define('MERCADOPAGO_ACCESS_TOKEN', 'APP_USR-tu-token-de-produccion-aqui');
   define('MERCADOPAGO_TEST_MODE', false);
   ```
4. Actualiza la URL del webhook con tu dominio de producción
5. ⚠️ **IMPORTANTE**: Verifica que tu cuenta de MercadoPago esté completamente verificada

---

## 🔍 Verificar que Funciona

### En Pruebas:
- Los pagos aparecerán en: https://www.mercadopago.com.ar/developers/panel/app/[tu-app-id]/testing
- No se procesan pagos reales
- Puedes usar tarjetas de prueba

### En Producción:
- Los pagos aparecerán en: https://www.mercadopago.com.ar/activities/list
- Se procesan pagos reales
- Solo puedes usar tarjetas reales

---

## 💳 Configurar Cuotas Sin Interés (3 cuotas)

Para que aparezcan las **3 cuotas sin interés** cuando el cliente paga con tarjeta:

1. Inicia sesión en tu cuenta de MercadoPago
2. Ve a **"Tu negocio"** → **"Costos y cuotas"**
3. En la sección **"Por ofrecer cuotas"**, busca **"Cuotas sin interés"**
4. Haz clic en **"Ofrecer"** y activa la opción
5. Elige **"3 cuotas"** como el máximo de cuotas sin interés

**Importante:**
- Cuando ofreces cuotas sin interés, MercadoPago aplica una comisión adicional (aproximadamente 12.55% para 3 cuotas)
- Esta comisión la pagas tú como vendedor, no el cliente
- El código ya está configurado para mostrar hasta 3 cuotas cuando se selecciona "Tarjeta"

## 💰 Diferenciación de Precios (Transferencia vs Tarjeta)

El sistema diferencia automáticamente los precios según el método de pago:

- **Transferencia/Efectivo**: Precio original (sin recargo)
- **Tarjeta de crédito**: Precio + 25% (hasta 3 cuotas sin interés)

El recargo del 25% se aplica automáticamente tanto a los productos como al costo de envío cuando se selecciona "Tarjeta" en el carrito.

## ❓ Troubleshooting

### Error: "MercadoPago no está configurado"
- Verifica que hayas configurado `MERCADOPAGO_ACCESS_TOKEN` en `config.php`
- Asegúrate de que no tenga espacios extra al copiar el token

### Error: "Invalid access token"
- Verifica que el token esté completo y correcto
- Asegúrate de estar usando el token correcto (test vs producción)

### Webhook no recibe notificaciones
- Verifica que la URL del webhook sea accesible públicamente
- Revisa los logs en: `logs/mercadopago-webhook.log`
- Verifica que el servidor pueda recibir solicitudes POST

### No aparecen las 3 cuotas sin interés
- Verifica que hayas activado "Cuotas sin interés" en el panel de MercadoPago (ver sección arriba)
- Asegúrate de que el método de pago seleccionado sea "Tarjeta" y no "Transferencia"
- Las cuotas sin interés solo aparecen cuando el método de pago es tarjeta de crédito

---

## 📞 Soporte

- **Documentación oficial**: https://www.mercadopago.com.ar/developers/es/docs/checkout-pro
- **Soporte de MercadoPago**: Desde el panel de desarrolladores

---

¡Listo! Con esto ya puedes comenzar a recibir pagos. 🎉

