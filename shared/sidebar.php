<!-- START MENU SIDEBAR WRAPPER -->
<?php
require_once __DIR__ . '/../config/database.php';
// ── Cargar módulos del perfil activo ──────────────────────────────────────
$permisos = $_SESSION['permisos_acceso'];
$uid      = (int)$_SESSION['id_user'];

$modulos_usuario = [];
$stmt = $mysqli->prepare(
    "SELECT pm_modulo FROM perfil_modulo pm
     JOIN usuario u ON u.per_id = pm.per_id
     WHERE u.id_user = ?"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $modulos_usuario[] = $row['pm_modulo'];
}

$has = function(string $m) use ($modulos_usuario): bool {
    return in_array($m, $modulos_usuario);
};

$cur       = $_GET['module'] ?? '';
$home_link = $has('dashboard') ? '?module=dashboard' : '?module=' . ($modulos_usuario[0] ?? 'contrasena');
?>
<aside class="sidebar sidebar-left">
    <div class="sidebar-content">
        <div class="aside-toolbar">
            <ul class="site-logo">
                <li>
                    <a href="<?php echo $home_link; ?>">
                        <div class="logo">
                            <img src="images/icon.png" alt="" width="25" height="25">
                        </div>
                        <span class="brand-text">SGC ARGOS</span>
                    </a>
                </li>
            </ul>
            <ul class="header-controls">
                <li class="nav-item">
                    <button type="button" class="btn btn-link btn-menu" data-toggle-state="mini-sidebar">
                        <i class="la la-dot-circle-o"></i>
                    </button>
                </li>
            </ul>
        </div>

        <nav class="main-menu">
            <ul class="nav metismenu">

                <?php if ($has('dashboard') || $has('gestiones') || $has('reportes') || $has('pos') || $has('convenios') || $has('giftcard') || $has('venta_diferida') || $has('estado_cuenta') || $has('portal_empresa')): ?>
                <li class="sidebar-header"><span>NAVEGACIÓN</span></li>
                <?php endif; ?>

                <?php if ($has('dashboard')): ?>
                <li class="nav-dropdown <?php if ($cur === 'dashboard') echo 'active'; ?>">
                    <a href="?module=dashboard" aria-expanded="false">
                        <i class="icon dripicons-meter"></i><span>Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('gestiones')): ?>
                <li class="nav-dropdown <?php if (in_array($cur, ['gestiones','nueva_gestion'])) echo 'active open'; ?>">
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="icon dripicons-document-edit"></i><span>Gestiones</span>
                    </a>
                    <ul class="collapse nav-sub <?php if (in_array($cur, ['gestiones','nueva_gestion'])) echo 'show'; ?>" aria-expanded="false">
                        <li><a href="?module=gestiones&cartera=30"><span>Cartera 30</span></a></li>
                        <li><a href="?module=gestiones&cartera=60"><span>Cartera 60</span></a></li>
                        <li><a href="?module=gestiones&cartera=90"><span>Cartera 90</span></a></li>
                        <li><a href="?module=gestiones&cartera=91"><span>Cartera +90</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($has('reportes')): ?>
                <li class="nav-dropdown <?php if ($cur === 'reportes') echo 'active open'; ?>">
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="icon dripicons-to-do"></i><span>Reportes</span>
                    </a>
                    <ul class="collapse nav-sub <?php if ($cur === 'reportes') echo 'show'; ?>" aria-expanded="false">
                        <li><a href="?module=reportes&tipo=ventas por locales"><span>Ventas por locales</span></a></li>
                        <li><a href="?module=reportes&tipo=cobranzas anteriores"><span>Reporte Cobranzas Anteriores</span></a></li>
                        <li><a href="?module=reportes&tipo=total cobranza"><span>Total Cobranza</span></a></li>
                        <li><a href="?module=reportes&tipo=detalle cobranza"><span>Detalle Cobranza</span></a></li>
                        <li><a href="?module=reportes&tipo=dinero por edades de cartera"><span>Dinero por edades de cartera</span></a></li>
                        <li><a href="?module=reportes&tipo=cartera recuperada"><span>Cartera recuperada</span></a></li>
                        <li><a href="?module=reportes&tipo=cliente consumos"><span>Cliente + Consumos</span></a></li>
                        <li><a href="?module=reportes&tipo=cliente - consumos"><span>Cliente - Consumos</span></a></li>
                        <li><a href="?module=reportes&tipo=cobranza por gestor"><span>Detalle de cobranza por gestores</span></a></li>
                        <li><a href="?module=reportes&tipo=consumos del mes"><span>Consumos del mes</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($has('pos')): ?>
                <li class="nav-dropdown <?php if (in_array($cur, ['pos','pos_historial'])) echo 'active open'; ?>">
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="icon dripicons-shopping-bag"></i><span>Punto de Venta</span>
                    </a>
                    <ul class="collapse nav-sub <?php if (in_array($cur, ['pos','pos_historial'])) echo 'show'; ?>" aria-expanded="false">
                        <li><a href="?module=pos"><span>Registrar Venta</span></a></li>
                        <li><a href="?module=pos_historial"><span>Historial de Ventas</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($has('convenios')): ?>
                <li class="nav-dropdown <?php if ($cur === 'convenios') echo 'active'; ?>">
                    <a href="?module=convenios">
                        <i class="icon dripicons-document"></i><span>Convenios</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('giftcard')): ?>
                <li class="nav-dropdown <?php if ($cur === 'giftcard') echo 'active'; ?>">
                    <a href="?module=giftcard">
                        <i class="icon dripicons-card"></i><span>Gift Cards</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('venta_diferida')): ?>
                <li class="nav-dropdown <?php if ($cur === 'venta_diferida') echo 'active'; ?>">
                    <a href="?module=venta_diferida">
                        <i class="icon dripicons-clock"></i><span>Ventas Diferidas</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('estado_cuenta')): ?>
                <li class="nav-dropdown <?php if ($cur === 'estado_cuenta') echo 'active'; ?>">
                    <a href="?module=estado_cuenta">
                        <i class="icon dripicons-graph-bar"></i><span>Estados de Cuenta</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('portal_empresa')): ?>
                <li class="nav-dropdown <?php if ($cur === 'portal_empresa') echo 'active'; ?>">
                    <a href="?module=portal_empresa">
                        <i class="icon dripicons-briefcase"></i><span>Portal Empresa / Nómina</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('clientes') || $has('usuarios') || $has('perfiles') || $has('configuracion') || $has('locales')): ?>
                <li class="sidebar-header"><span>ADMINISTRACIÓN</span></li>
                <?php endif; ?>

                <?php if ($has('clientes')): ?>
                <li class="nav-dropdown <?php if ($cur === 'clientes') echo 'active'; ?>">
                    <a href="?module=clientes">
                        <i class="icon dripicons-user"></i><span>Clientes</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('usuarios')): ?>
                <li class="nav-dropdown <?php if (in_array($cur, ['usuarios','formulario'])) echo 'active open'; ?>">
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="icon dripicons-user-group"></i><span>Usuarios</span>
                    </a>
                    <ul class="collapse nav-sub <?php if (in_array($cur, ['usuarios','formulario'])) echo 'show'; ?>" aria-expanded="false">
                        <li><a href="?module=usuarios"><span>Gestión Usuarios</span></a></li>
                        <li><a href="?module=contrasena"><span>Cambiar Contraseña</span></a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-dropdown <?php if ($cur === 'contrasena') echo 'active'; ?>">
                    <a href="?module=contrasena">
                        <i class="icon dripicons-lock"></i><span>Cambiar Contraseña</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('perfiles')): ?>
                <li class="nav-dropdown <?php if ($cur === 'perfiles') echo 'active'; ?>">
                    <a href="?module=perfiles">
                        <i class="icon dripicons-user-id"></i><span>Perfiles y Permisos</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('locales')): ?>
                <li class="nav-dropdown <?php if ($cur === 'locales') echo 'active'; ?>">
                    <a href="?module=locales">
                        <i class="icon dripicons-store"></i><span>Locales Comerciales</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($has('configuracion')): ?>
                <li class="nav-dropdown <?php if ($cur === 'configuracion') echo 'active'; ?>">
                    <a href="?module=configuracion">
                        <i class="icon dripicons-gear"></i><span>Configuración</span>
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </nav>
    </div>
</aside>
<!-- END MENU SIDEBAR WRAPPER -->
