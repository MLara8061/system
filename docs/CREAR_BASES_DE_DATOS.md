# Instrucciones para Crear Bases de Datos en Hostinger

## ⚠️ IMPORTANTE: Contraseñas Generadas

Guarda estas contraseñas en un lugar seguro:

```
Sistema 1 - Biomédica CUN
  Base de Datos: u499728070_biomedicacun
  Usuario: u499728070_biomedica
  Contraseña: Q;|\p5&u%<r#3}7,

Sistema 2 - Sistemas CUN
  Base de Datos: u499728070_sistemascun
  Usuario: u499728070_sistemas
  Contraseña: =_ju7{21BnHa})hX

Sistema 3 - Mantenimiento CUN
  Base de Datos: u499728070_manttocun
  Usuario: u499728070_mantto
  Contraseña: gIWXujkCO9/{%$37
```

---

## Opción 1: Crear desde hPanel (Recomendado)

### Paso 1: Acceder al Panel

1. Ve a: https://hpanel.hostinger.com
2. Inicia sesión con tus credenciales
3. Selecciona tu cuenta o dominio
4. Ve a **Bases de Datos MySQL**

### Paso 2: Crear Primera Base de Datos

**Para Biomédica CUN:**

1. Click en "Crear Base de Datos"
2. Nombre de la BD: `u499728070_biomedicacun`
3. Usuario de BD: `u499728070_biomedica`
4. Contraseña: `Q;|\p5&u%<r#3}7,`
5. Click en "Crear"

**Repite el proceso para:**

- Base 2: `u499728070_sistemascun` | Usuario: `u499728070_sistemas` | Pass: `=_ju7{21BnHa})hX`
- Base 3: `u499728070_manttocun` | Usuario: `u499728070_mantto` | Pass: `gIWXujkCO9/{%$37`

### Paso 3: Importar Esquema SQL

Una vez creadas las 3 bases de datos:

1. Click en la BD `u499728070_biomedicacun`
2. Click en **phpMyAdmin**
3. Pestaña **Importar**
4. Selecciona archivo: `database/migrations/schema.sql`
5. Click en **Ejecutar**

**Repite para las otras 2 bases de datos**

---

## Opción 2: Crear desde phpMyAdmin

### Paso 1: Acceder a phpMyAdmin

1. En hPanel → Bases de Datos → Click en "Administrar"
2. O accede directamente: https://phpmyadmin.hostinger.com

### Paso 2: Crear Base de Datos

1. Pestaña **Bases de Datos**
2. Nombre: `u499728070_biomedicacun`
3. Collation: `utf8mb4_unicode_ci`
4. Click en **Crear**

### Paso 3: Crear Usuario

1. Pestaña **Cuentas**
2. Click en **Agregar cuenta de usuario**
3. Nombre: `u499728070_biomedica`
4. Host: `localhost`
5. Contraseña: `Q;|\p5&u%<r#3}7,`
6. Confirmar contraseña
7. Click en **Crear**

### Paso 4: Asignar Privilegios

1. En **Cuentas**, busca: `u499728070_biomedica`
2. Click en **Editar privilegios**
3. Pestaña **Base de datos específica**
4. Selecciona: `u499728070_biomedicacun`
5. Marca **TODOS los privilegios**
6. Click en **Ir**

---

## Verificación en Servidor

Después de crear las bases de datos, verifica por SSH:

```bash
ssh -p 65002 u499728070@46.202.197.220

# Listar bases de datos creadas
mysql -u u499728070_biomedica -p u499728070_biomedicacun -e "SHOW TABLES;" 2>&1
```

Si ves "ERROR 2000" o similar, significa que la BD existe pero está vacía (normal antes de importar schema).

---

## Importar Esquema SQL (Alternativa por SSH)

Si prefieres hacerlo por SSH después de crear las BDs:

```bash
ssh -p 65002 u499728070@46.202.197.220

# Importar en biomedicacun
mysql -u u499728070_biomedica -p"Q;|\p5&u%<r#3}7," u499728070_biomedicacun < /home/u499728070/domains/activosamerimed.com/public_html/biomedicacun/database/migrations/schema.sql

# Importar en sistemascun
mysql -u u499728070_sistemas -p"=_ju7{21BnHa})hX" u499728070_sistemascun < /home/u499728070/domains/activosamerimed.com/public_html/sistemascun/database/migrations/schema.sql

# Importar en manttocun
mysql -u u499728070_mantto -p"gIWXujkCO9/{%$37" u499728070_manttocun < /home/u499728070/domains/activosamerimed.com/public_html/manttocun/database/migrations/schema.sql
```

---

## Próximos Pasos

Una vez creadas las bases de datos:

1. ✅ Bases de datos creadas
2. ✅ Usuarios de BD creados
3. ⏭️ **Actualizar contraseñas en archivos .env** (próximo paso)
4. ⏭️ Importar esquema SQL
5. ⏭️ Verificar subdominios

---

**¿Necesitas que cree un script automatizado para completar esto?**
