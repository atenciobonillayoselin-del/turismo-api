# Guia: Configurar Aiven MySQL + Render + Flutter

## El Problema Actual

Tu API esta desplegada en Render.com (https://turismo-api-y4gk.onrender.com),
pero **los archivos PHP en GitHub todavia son los viejos** sin las correcciones:

1. `config/database.php` NO tiene soporte SSL para Aiven
2. `api/registro_google.php` NO guarda ni retorna `perfil_completo`
3. `api/actualizar_usuario.php` NO actualiza `perfil_completo`
4. `api/perfil_usuario.php` NO retorna `perfil_completo`

Por eso Firebase si guarda los datos, pero MySQL no se actualiza correctamente.

---

## La Solucion: Aiven + Archivos PHP Corregidos

Aiven ofrece MySQL en la nube con plan gratuito. Asi tanto Render como tu PC local pueden conectarse a la misma base de datos.

---

## Paso 1: Crear cuenta en Aiven (ya lo hiciste)

Ya tienes creado el servicio `mysql-3c89e575-turismo-la-paz` en Aiven.

Tus datos de conexion son:
```
Host:     mysql-3c89e575-turismo-la-paz.d.aivencloud.com
Port:     23909
User:     avnadmin
Password: (el que aparece en Aiven Console)
Database: defaultdb   <- Aiven crea esta por defecto
```

---

## Paso 2: Crear la base de datos app_turistica_la_paz en Aiven

Aiven crea `defaultdb` automaticamente, pero tu app usa `app_turistica_la_paz`.

### Opcion A: Desde Aiven Console
1. En tu servicio MySQL, ve a **Databases** > **Create database**
2. Nombre: `app_turistica_la_paz`
3. Clic en **Add database**

### Opcion B: Desde MySQL Workbench / HeidiSQL
1. Conectate a Aiven con SSL
2. Ejecuta:
```sql
CREATE DATABASE IF NOT EXISTS app_turistica_la_paz
CHARACTER SET utf8mb4
COLLATE utf8mb4_spanish_ci;
```

---

## Paso 3: Importar las tablas en Aiven

1. Conectate a Aiven con MySQL Workbench / HeidiSQL / phpMyAdmin
2. Selecciona la base de datos `app_turistica_la_paz`
3. Ejecuta el SQL del archivo `app_turistica_la_paz (9).sql`

O usa la linea de comandos (si tienes mysql client instalado):
```bash
mysql -h mysql-3c89e575-turismo-la-paz.d.aivencloud.com \
      -P 23909 \
      -u avnadmin \
      -p \
      --ssl-mode=REQUIRED \
      app_turistica_la_paz < app_turistica_la_paz.sql
```

---

## Paso 4: Descargar el certificado CA de Aiven

1. En Aiven Console, ve a tu servicio MySQL
2. En **Connection information**, busca **CA certificate**
3. Haz clic en **Download** o **Show**
4. Copia TODO el contenido del certificado

El certificado debe verse asi:
```
-----BEGIN CERTIFICATE-----
MIIDXTCCAkWgAwIBAgIJAJC1HiIAZAiUMA0GCSqGSIb3DfBAYTAkVTMRMwEQYD
... (muchas lineas)
-----END CERTIFICATE-----
```

---

## Paso 5: Copiar los archivos corregidos a tu proyecto

Copia TODO el contenido de `turismo_api_corregido/` a `C:\laragon\www\turismo_api\`:

```
turismo_api_corregido/
  config/
    database.php          -> C:\laragon\www\turismo_api\config\database.php
    ca.pem                -> C:\laragon\www\turismo_api\config\ca.pem
  api/
    registro.php          -> C:\laragon\www\turismo_api\api\registro.php
    registro_google.php   -> C:\laragon\www\turismo_api\api\registro_google.php
    actualizar_usuario.php -> C:\laragon\www\turismo_api\api\actualizar_usuario.php
    perfil_usuario.php   -> C:\laragon\www\turismo_api\api\perfil_usuario.php
    login.php             -> C:\laragon\www\turismo_api\api\login.php
    test_connection.php   -> C:\laragon\www\turismo_api\api\test_connection.php
  render.yaml             -> C:\laragon\www\turismo_api\render.yaml
```

### IMPORTANTE: Pegar el certificado CA

Abre `C:\laragon\www\turismo_api\config\ca.pem` y **reemplaza todo el contenido** con el certificado que descargaste de Aiven.

NO dejes el texto de instrucciones que viene por defecto.

---

## Paso 6: Configurar variables en Render.com

1. Ve a https://dashboard.render.com
2. Selecciona tu servicio `turismo-api`
3. Ve a **Environment** (menu izquierdo)
4. Agrega/Actualiza estas variables:

```
PDO_HOST      = mysql-3c89e575-turismo-la-paz.d.aivencloud.com
PDO_PORT      = 23909
PDO_DATABASE  = app_turistica_la_paz
PDO_USERNAME  = avnadmin
PDO_PASSWORD  = (tu password de Aiven)
PDO_SSL_CA    = config/ca.pem
```

5. Clic en **Save Changes**

---

## Paso 7: Subir cambios a GitHub

Abre PowerShell o CMD en `C:\laragon\www\turismo_api`:

```bash
cd C:\laragon\www\turismo_api
git status
git add .
git commit -m "Fix: correcciones perfil_completo + SSL Aiven + test conexion"
git push origin main
```

Render detectara el push y hara deploy automaticamente.

---

## Paso 8: Verificar que todo funciona

Despues del deploy, abre en tu navegador:

```
https://turismo-api-y4gk.onrender.com/api/test_connection.php
```

Deberias ver algo como:
```json
{
  "success": true,
  "checks": [
    "✅ Extension pdo_mysql disponible",
    "✅ Conexion a MySQL exitosa (version: 8.4.8)",
    "✅ Base de datos app_turistica_la_paz existe",
    "✅ Tabla usuario existe",
    "📊 Total de usuarios en MySQL: 0"
  ]
}
```

Si ves errores, copia el mensaje exacto.

---

## Paso 9: Probar desde la app Flutter

1. Desinstala la app de tu celular (para borrar datos locales)
2. Vuelve a instalar
3. Inicia sesion con Google
4. Debe ir al formulario de completar perfil
5. Llena el formulario y guarda
6. Debe ir a la pantalla Home

Despues, verifica en MySQL Workbench conectado a Aiven:
```sql
SELECT id_usuario, email, nombre, telefono, carnet, perfil_completo
FROM app_turistica_la_paz.usuario;
```

Deberias ver el usuario nuevo con `perfil_completo = 1`.

---

## Errores comunes

### "SQLSTATE[HY000] [1049] Unknown database 'app_turistica_la_paz'"
Solucion: Crear la base de datos en Aiven (Paso 2).

### "SQLSTATE[HY000] [2002] Connection refused" o timeout
Solucion: Verificar que PDO_HOST y PDO_PORT esten correctos en Render.

### "SSL connection is required"
Solucion: El certificado CA no esta configurado. Verifica que `config/ca.pem` este en el repo y que `PDO_SSL_CA = config/ca.pem` este en Render.

### La tabla usuario no existe
Solucion: Importar el SQL `app_turistica_la_paz.sql` en Aiven (Paso 3).

---

## ¿Que cambio en los archivos PHP?

### El problema era:

1. **`registro_google.php`**: NO guardaba `perfil_completo` en MySQL
2. **`actualizar_usuario.php`**: NO actualizaba `perfil_completo` en MySQL
3. **`perfil_usuario.php`**: NO retornaba `perfil_completo` en la respuesta
4. **`login.php`**: NO retornaba `perfil_completo` en la respuesta
5. **`registro.php`**: NO guardaba `perfil_completo` en MySQL
6. **`database.php`**: NO soportaba SSL para Aiven

### Ahora todos los archivos PHP:

- Guardan `perfil_completo` (0 o 1) en la base de datos
- Lo retornan en la respuesta JSON
- Se conectan a Aiven con SSL obligatorio
- `test_connection.php` verifica que la conexion y tablas funcionen
