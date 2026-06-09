
<header class="navbar-admin">
    <div class="navbar-izquierda">
        <!-- botón hamburguesa — solo en móvil -->
        <button class="hamburguesa d-lg-none"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebarMobile">
            <i class="ri-menu-line"></i>
        </button>
        <p class="navbar-fecha mb-0" id="fecha-hoy"></p>
    </div>
    <div class="navbar-acciones">
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

<script>
// blur en el contenido cuando el offcanvas se abre
const _offcanvas = document.getElementById('sidebarMobile');
if (_offcanvas) {
    const _contenido = document.querySelector('.admin-contenido');
    _offcanvas.addEventListener('show.bs.offcanvas',   () => { if (_contenido) _contenido.style.filter = 'blur(3px)'; });
    _offcanvas.addEventListener('hidden.bs.offcanvas', () => { if (_contenido) _contenido.style.filter = ''; });
}
</script>