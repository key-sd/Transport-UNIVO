<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<!-- sidebar desktop — se oculta en pantallas pequeñas -->
<aside class="sidebar d-none d-lg-flex flex-column">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icono">
            <i class="ri-bus-2-fill"></i>
        </div>
        <div>
            <p class="sidebar-nombre">TransporteU</p>
            <p class="sidebar-rol">Panel Admin</p>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="admin.php" class="sidebar-item <?php echo $pagina_actual == 'admin.php' ? 'activo' : ''; ?>">
            <i class="ri-dashboard-line"></i>
            <span>Inicio</span>
        </a>
        <p class="sidebar-seccion">Usuarios</p>
        <a href="section_conductor.php" class="sidebar-item <?php echo $pagina_actual == 'section_conductor.php' ? 'activo' : ''; ?>">
            <i class="ri-steering-2-line"></i>
            <span>Conductores</span>
        </a>
        <p class="sidebar-seccion">Operaciones</p>
        <a href="section_unidades.php" class="sidebar-item <?php echo $pagina_actual == 'section_unidades.php' ? 'activo' : ''; ?>">
            <i class="ri-bus-line"></i>
            <span>Unidades</span>
        </a>
        <a href="section_horario.php" class="sidebar-item <?php echo $pagina_actual == 'section_horario.php' ? 'activo' : ''; ?>">
            <i class="ri-time-line"></i>
            <span>Horarios</span>
        </a>
        <a href="section_cronograma.php" class="sidebar-item <?php echo $pagina_actual == 'section_cronograma.php' ? 'activo' : ''; ?>">
            <i class="ri-calendar-todo-line"></i>
            <span>Cronograma</span>
        </a>
        <a href="section_rutas.php" class="sidebar-item <?php echo $pagina_actual == 'section_rutas.php' ? 'activo' : ''; ?>">
            <i class="ri-map-2-line"></i>
            <span>Rutas</span>
        </a>
    </nav>
</aside>

<!-- offcanvas para móvil -->
<div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="sidebarMobile">
    <div class="offcanvas-header" style="border-bottom:1px solid #ffffff18; padding:1.25rem;">
        <div class="d-flex align-items-center gap-2">
            <div class="sidebar-logo-icono">
                <i class="ri-bus-2-fill"></i>
            </div>
            <div>
                <p class="sidebar-nombre mb-0">TransporteU</p>
                <p class="sidebar-rol mb-0">Panel Admin</p>
            </div>
        </div>
        <button class="btn-cerrar-menu" data-bs-dismiss="offcanvas">
            <i class="ri-close-line"></i>
        </button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0">
        <nav class="sidebar-nav flex-grow-1">
            <a href="admin.php" class="sidebar-item <?php echo $pagina_actual == 'admin.php' ? 'activo' : ''; ?>">
                <i class="ri-dashboard-line"></i>
                <span>Inicio</span>
            </a>
            <p class="sidebar-seccion">Usuarios</p>
            <a href="section_conductor.php" class="sidebar-item <?php echo $pagina_actual == 'section_conductor.php' ? 'activo' : ''; ?>">
                <i class="ri-steering-2-line"></i>
                <span>Conductores</span>
            </a>
            <p class="sidebar-seccion">Operaciones</p>
            <a href="section_unidades.php" class="sidebar-item <?php echo $pagina_actual == 'section_unidades.php' ? 'activo' : ''; ?>">
                <i class="ri-bus-line"></i>
                <span>Unidades</span>
            </a>
            <a href="section_horario.php" class="sidebar-item <?php echo $pagina_actual == 'section_horario.php' ? 'activo' : ''; ?>">
                <i class="ri-time-line"></i>
                <span>Horarios</span>
            </a>
            <a href="section_cronograma.php" class="sidebar-item <?php echo $pagina_actual == 'section_cronograma.php' ? 'activo' : ''; ?>">
                <i class="ri-calendar-todo-line"></i>
                <span>Cronograma</span>
            </a>
            <a href="section_ruta.php" class="sidebar-item <?php echo $pagina_actual == 'section_ruta.php' ? 'activo' : ''; ?>">
                <i class="ri-map-2-line"></i>
                <span>Rutas</span>
            </a>
        </nav>

        <div style="padding:1rem 0.75rem; border-top:1px solid #ffffff18;">
            <a href="../includes/logout.php" class="sidebar-item sidebar-logout">
                <i class="ri-logout-box-r-line"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </div>
</div>