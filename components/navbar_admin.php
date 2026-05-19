<header class="navbar-admin">
    <p class="navbar-fecha" id="fecha-hoy"></p>
    <div class="navbar-acciones">
        <div class="navbar-icono-btn">
            <i class="ri-notification-3-line"></i>
        </div>
        <div class="navbar-divisor"></div>
        <div class="navbar-usuario">
            <div class="navbar-avatar">
                <?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?>
            </div>
            <span class="navbar-nombre"><?php echo ucfirst($_SESSION['usuario']); ?></span>
        </div>
        <div class="navbar-divisor"></div>
        <a href="../includes/logout.php" class="navbar-logout" title="Cerrar sesión">
            <i class="ri-logout-box-r-line"></i>
        </a>
    </div>
</header>