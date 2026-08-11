# SGC ARGOS — Notas para Claude Code

## Regla obligatoria: incrementar VERSION en cada push a producción

El archivo `VERSION` en la raíz contiene un número (`1.1`, `1.2`, ...) que se
muestra como una marca de agua pequeña en la esquina inferior izquierda de
`index.php` (login) y `main.php` (app), para que el usuario pueda confirmar
a simple vista que un despliegue llegó a producción sin tener que probar
ninguna funcionalidad.

**Antes de cada `git push` a una rama de despliegue** (`claude/session-*`,
`nuevas-funcionalidades-v2`, `feature/nuevas-funcionalidades`), incrementa
el número menor en `VERSION` (`1.1` → `1.2` → `1.3` ...) e inclúyelo en el
mismo commit que el resto de los cambios. No se salta nunca, incluso para
cambios triviales — es la única forma de que la marca de versión sirva como
señal confiable de que el deploy ocurrió.
