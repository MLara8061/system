# PRUEBAS DE SEGURIDAD: VALIDACIÓN DE PERMISOS

**Servidor**: test.activosamerimed.com  
**Fecha**: 14/04/2026  
**Objetivo**: Verificar que los bloqueos de permisos funcionan correctamente

---

## 1. CONFIGURACIÓN DE USUARIOS DE PRUEBA

Necesitarás 3 usuarios con diferentes roles para probar:

| Correo | Contraseña | Tipo | Sucursal |
|--------|-----------|------|----------|
| admin@test.com | Admin123! | Admin (1) | Todas |
| jefe@test.com | Jefe123! | Staff (2) | Sucursal 1 |
| cliente@test.com | Cliente123! | Customer (3) | N/A |

---

## 2. PRUEBAS DE ELIMINACIÓN DE EQUIPOS

### 2.1 PRUEBA: Staff intenta borrar equipo de otra sucursal

**El equipo NO debe ser borrado**

**Pasos**:

1. Inicia sesión como `jefe@test.com` (Staff, Sucursal 1)
2. Ve a **Equipos → Listado**
3. Busca un equipo de **Sucursal 2** (diferente a la suya)
4. Haz clic en **Eliminar** (botón del menú contextual)

**Resultado Esperado**:
- ❌ El equipo NO se borra
- ✅ Mensaje: "Sin permisos para esta sucursal" o similar
- ✅ En los logs del servidor: `SECURITY: User type 2 attempted to delete equipment from different branch`

**Cómo verificar en el servidor**:
```bash
tail -100 /home/u499728070/.logs/php.log | grep "SECURITY"
```

---

### 2.2 PRUEBA: Cliente intenta borrar equipo

**El cliente NO debe tener acceso**

**Pasos**:

1. Inicia sesión como `cliente@test.com` (Customer)
2. Navega a la URL: `/index.php?page=equipment_list`
3. (No verá el menú de equipos, pero si accede manualmente)

**Resultado Esperado**:
- ✅ No aparece el menú en el sidebar
- ✅ Si ingresa la URL manualmente: "Sin permisos" o se niega acceso
- ✅ En logs: `SECURITY: Customer (type 3) attempted to delete equipment`

---

## 3. PRUEBAS DE ELIMINACIÓN DE USUARIOS

### 3.1 PRUEBA: Staff intenta borrar usuario

**Staff NO puede borrar usuarios**

**Pasos**:

1. Inicia sesión como `jefe@test.com` (Staff)
2. (Staff normalmente no ve el menú de Usuarios)
3. Si intenta acceso manual a `/public/ajax/action.php?action=delete_user&id=5`

**Resultado Esperado**:
- ✅ Error 403 o "Sin permisos"
- ✅ Usuario NO se borra
- ✅ En logs: `SECURITY: User type 2 attempted to delete user`

---

## 4. PRUEBAS DE ELIMINACIÓN DE CLIENTES

### 4.1 PRUEBA: Staff intenta borrar cliente

**Staff NO puede borrar clientes**

**Pasos**:

1. Inicia sesión como `jefe@test.com` (Staff)
2. Ve a **Clientes → Listado**
3. Intenta eliminar un cliente
4. (O envía AJAX manual)

**Resultado Esperado**:
- ❌ Cliente NO se borra
- ✅ En logs: `SECURITY: User type 2 attempted to delete customer`

---

## 5. PRUEBA: Admin puede hacer todo (verificación positiva)

### 5.1 PRUEBA: Admin borra equipo

**Admin DEBE poder borrar cualquier equipo**

**Pasos**:

1. Inicia sesión como `admin@test.com` (Admin)
2. Ve a **Equipos → Listado**
3. Selecciona un equipo de prueba que no uses
4. Haz clic en **Eliminar**
5. Confirma

**Resultado Esperado**:
- ✅ Equipo se borra exitosamente
- ✅ Mensaje de confirmación: "Equipo eliminado"
- ✅ El equipo NO apareceráen el listado

---

## 6. TABLA DE RESULTADOS

Marca ✅ si pasa, ❌ si falla:

| Prueba | Esperado | Real | Estado |
|--------|----------|------|--------|
| Staff borra equipo otra sucursal | ❌ NO se borra | _____ | ____ |
| Cliente intenta borrar equipo | ❌ Acceso negado | _____ | ____ |
| Staff borra usuario | ❌ NO se borra | _____ | ____ |
| Admin borra equipo | ✅ SÍ se borra | _____ | ____ |
| Sistema registra intentos en logs | ✅ SÍ | _____ | ____ |

---

## 7. CÓMO REVISAR LOS LOGS

**Ubicación de logs**: `/home/u499728070/.logs/php.log` en el servidor TEST

**Ver últimas 50 líneas con "SECURITY"**:
```bash
ssh -p 65002 u499728070@46.202.197.220
tail -100 ~/.logs/php.log | grep "SECURITY"
```

**Ejemplo de log esperado**:
```
[14-Apr-2026 10:23:45] SECURITY: User type 2 attempted to delete equipment from different branch
[14-Apr-2026 10:24:12] SECURITY: Customer (type 3) attempted to delete equipment
[14-Apr-2026 10:25:03] SECURITY: User type 2 attempted to delete customer
```

---

## 8. RESUMEN DE CAMBIOS IMPLEMENTADOS

| Función | Antes | Después | Status |
|---------|-------|---------|--------|
| delete_user() | Sin validación | Solo admin | ✅ FIXED |
| delete_equipment() | Sin validación | Valida sucursal | ✅ FIXED |
| delete_customer() | Sin validación | Solo admin | ✅ FIXED |
| delete_staff() | Sin validación | Solo admin | ✅ FIXED |
| delete_supplier() | Sin validación | Admin o staff | ✅ FIXED |

---

**Si todas las pruebas PASAN**: ✅ El sistema de permisos está funcionando correctamente

**Si alguna FALLA**: ❌ Reportar inmediatamente con:
- El número de la prueba que falló
- Qué usuario intentó la acción
- Qué sucedió (se borró cuando no debería, etc.)
- Timestamp aproximado

---

**Contacto para reportes**: [soporte@activosamerimed.com]

