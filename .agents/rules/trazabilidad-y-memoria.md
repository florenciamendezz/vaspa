---
trigger: always_on
description: Reglas obligatorias de trazabilidad (CHANGELOG.md, DECISIONES.md y MEMORIA_TRABAJO.md) e idioma español para VASPA.
---

# Regla de Trazabilidad Continua y Memoria de Trabajo

1. **Idioma**: Responder y documentar siempre en español.
2. **Registro de Cambios (`CHANGELOG.md`)**: Todo cambio en archivos de código o configuración del proyecto debe registrarse en `c:\xampp\htdocs\vaspa\CHANGELOG.md` detallando fecha, ID del hallazgo (`SEC-XXX`), archivos tocados, resumen del cambio y resultado de las pruebas.
3. **Registro de Decisiones (`DECISIONES.md`)**: Toda decisión de arquitectura, diseño o seguridad debe documentarse como un nuevo ADR en `c:\xampp\htdocs\vaspa\DECISIONES.md`.
4. **Memoria de Trabajo (`MEMORIA_TRABAJO.md`)**: Actualizar el checklist y estado de avance en `c:\xampp\htdocs\vaspa\MEMORIA_TRABAJO.md` al completar cada tarea o subfase.
5. **Enmascaramiento de Secretos**: Nunca incluir contraseñas ni tokens reales en ninguno de los archivos de documentación o código.
