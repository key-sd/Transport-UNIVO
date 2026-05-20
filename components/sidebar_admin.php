<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
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
            <span>Dashboard</span>
        </a>
        <p class="sidebar-seccion">Usuarios</p>
        <a href="gestionar_conductores.php" class="sidebar-item <?php echo $pagina_actual == 'gestionar_conductores.php' ? 'activo' : ''; ?>">
            <i class="ri-steering-2-line"></i>
            <span>Conductores</span>
        </a>
        <p class="sidebar-seccion">Operaciones</p>
        <a href="admin-unidades.php" class="sidebar-item <?php echo $pagina_actual == 'admin-unidades.php' ? 'activo' : ''; ?>">
            <i class="ri-bus-line"></i>
            <span>Unidades</span>
        </a>
        <a href="admin-horarios.php" class="sidebar-item <?php echo $pagina_actual == 'admin-horarios.php' ? 'activo' : ''; ?>">
            <i class="ri-time-line"></i>
            <span>Horarios</span>
        </a>
        <a href="admin-rutas.php" class="sidebar-item <?php echo $pagina_actual == 'admin-rutas.php' ? 'activo' : ''; ?>">
            <i class="ri-map-2-line"></i>
            <span>Rutas</span>
        </a>
        <a href="admin-reportes.php" class="sidebar-item <?php echo $pagina_actual == 'admin-reportes.php' ? 'activo' : ''; ?>">
            <i class="ri-bar-chart-2-line"></i>
            <span>Reportes</span>
        </a>
    </nav>
</aside>