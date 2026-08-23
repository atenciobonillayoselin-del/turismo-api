# Solucion: El formulario vuelve a aparecer al entrar con Google

## Por que pasa esto

El login con Google siempre envia `perfil_completo = false` al PHP. Si el PHP no preserva el valor existente en MySQL, cada vez que cierras y abres la app parece que es la primera vez.

## Cambios realizados

### 1. Flutter: `lib/services/auth_service.dart`

Ahora `signInWithGoogle()` lee la respuesta del PHP y:
- Si el usuario ya tenia perfil completo en MySQL, lo respeta
- No sobreescribe el nombre personalizado
- Actualiza Firestore con el estado REAL del perfil

### 2. PHP: `api/registro_google.php`

Ahora para usuarios existentes:
- **Preserva el nombre** si ya no es "Usuario"
- **Preserva `perfil_completo = 1`** si ya completo el perfil
- Retorna `perfil_completo` en la respuesta JSON

## Pasos para aplicar

### 1. Copiar archivos PHP corregidos

Copia TODO desde:
```
c:\Users\Atencio\Documents\3 ano\proyecto\prototipo5\turismo_api_corregido\
```

a:
```
C:\laragon\www\turismo_api\
```

**No olvides reemplazar `config/ca.pem` con tu certificado de Aiven.**

### 2. Verificar que `database.php` NO tenga tu password

Debe verse asi:
```php
$host = getenv('PDO_HOST') ?: 'localhost';
$port = getenv('PDO_PORT') ?: '3306';
$dbname = getenv('PDO_DATABASE') ?: 'app_turistica_la_paz';
$username = getenv('PDO_USERNAME') ?: 'root';
$password = getenv('PDO_PASSWORD') ?: '';
```

Si aun tiene tus credenciales de Aiven, reemplazalas por los valores de arriba.

### 3. Subir a GitHub

```bash
cd C:\laragon\www\turismo_api
git add .
git commit -m "Fix: preservar perfil_completo en login Google + SSL Aiven"
git push origin main
```

### 4. Verificar en Render

Espera el deploy y abre:
```
https://turismo-api-y4gk.onrender.com/api/test_connection.php
```

Debe mostrar:
```json
{
  "success": true,
  "checks": [
    "✅ Extension pdo_mysql disponible",
    "✅ Conexion a MySQL exitosa",
    "✅ Base de datos app_turistica_la_paz existe",
    "✅ Tabla usuario existe"
  ]
}
```

### 5. Probar desde la app

1. Desinstala la app de tu celular
2. Vuelve a instalar
3. Inicia sesion con Google
4. Completa el formulario
5. Entra al Home
6. Cierra la app completamente (tambien de tareas recientes)
7. Vuelve a abrir

Ahora debe ir directo al Home, NO al formulario.

## Si sigue fallando

Conecta tu celular a Android Studio o VS Code y mira los logs. Busca estas lineas:

```
📦 Datos retornados por registro_google: {...}
✅ Usuario existente con perfil completo en MySQL
```

o:

```
⚠️ Usuario existente sin perfil completo en MySQL
```

Y tambien:

```
📦 perfil_completo en MySQL: 1
✅ Usuario con perfil completo en MySQL, redirigiendo a /home
```

Si ves `perfil_completo en MySQL: null` o `0`, significa que los archivos PHP en Render aun no estan actualizados.

## Verificar directamente en MySQL

Conectate a Aiven con MySQL Workbench y ejecuta:
```sql
SELECT id_usuario, email, nombre, perfil_completo
FROM app_turistica_la_paz.usuario;
```

Despues de completar el formulario, tu usuario debe tener:
- `perfil_completo = 1`
- `nombre` = el nombre que elegiste (NO "Usuario")
