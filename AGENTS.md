# Reglas de Trabajo y Trazabilidad del Proyecto (VASPA)

Estas reglas son obligatorias para todos los agentes y subagentes que trabajen en el repositorio `c:\xampp\htdocs\vaspa`.

## 1. Idioma Obligatorio
- **Todas las respuestas, mensajes, comentarios de código y archivos `.md` deben redactarse íntegramente en español.**

## 2. Registro Obligatorio de Cambios (`CHANGELOG.md`)
- **Cada vez que se realice una modificación, creación o eliminación de archivos en el proyecto**, se debe registrar inmediatamente una entrada en el archivo [`CHANGELOG.md`](./CHANGELOG.md) (ubicado en la raíz del proyecto).
- Cada entrada en `CHANGELOG.md` debe incluir:
  - **Fecha y Fase**: (ej. `2026-11-02 — Fase 1: Remediación Crítica`).
  - **ID de Hallazgo / Tarea**: (ej. `SEC-009`, `SEC-001`, etc.).
  - **Archivos modificados/creados/eliminados**.
  - **Descripción clara del cambio**: Qué se hizo y qué problema resuelve.
  - **Verificación**: Cómo se comprobó que el cambio funciona y no rompe funcionalidad existente (ej. ejecución de tests E2E de Playwright).

## 3. Registro Obligatorio de Decisiones (`DECISIONES.md`)
- **Cada decisión arquitectónica, de seguridad, de diseño o de entorno** debe documentarse en el archivo [`DECISIONES.md`](./DECISIONES.md) (Architecture & Security Decision Records — ADR).
- Cada entrada en `DECISIONES.md` debe incluir:
  - **ID y Título de la Decisión**: (ej. `ADR-001: Gestión de credenciales mediante archivo .env`).
  - **Fecha y Estado**: (`Propuesta` / `Aceptada` / `Implementada`).
  - **Contexto**: Por qué fue necesario tomar la decisión.
  - **Decisión**: Qué camino técnico se eligió.
  - **Consecuencias / Impacto**: Qué cambia en el proyecto o en el despliegue local/producción.

## 4. Memoria de Trabajo y Avance (`MEMORIA_TRABAJO.md`)
- Antes de iniciar una tarea o al retomar el trabajo, los agentes deben consultar y mantener actualizado el archivo [`MEMORIA_TRABAJO.md`](./MEMORIA_TRABAJO.md).
- Este archivo actúa como **tablero de estado y memoria persistente** entre sesiones, detallando:
  - Estado general de cada fase del Plan de Remediación (`Pendiente`, `En Progreso`, `Completado`).
  - Checklist detallado de los 16 hallazgos de seguridad (`SEC-001` a `SEC-016`).
  - Próximos pasos inmediatos y notas técnicas relevantes del entorno.

## 5. Seguridad y No Regresión
- **Cero secretos en texto plano**: Nunca escribir contraseñas reales, tokens o claves en archivos versionados ni en los archivos `.md` de seguimiento (enmascarar siempre).
- **Verificación continua**: Tras completar un bloque de cambios de código, verificar que la aplicación y la suite en `tests/e2e/` sigan funcionando correctamente sin regresiones.
