<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'pasajero') {
    header("Location: /login.php");
    exit();
}
require_once '../includes/conexion.php';

// Obtener nombre del pasajero (o código de usuario si no hay nombre completo)
$nombre_pasajero = $_SESSION['usuario'] ?? 'Pasajero';

// Cargar sedes para los selectores de filtro
$sedes_query = mysqli_query($conexion, "SELECT id, nombre FROM sedes WHERE estado = 1");
$sedes = [];
while ($s = mysqli_fetch_assoc($sedes_query)) {
    $sedes[] = $s;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransportU — Pasajero</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../css/pasajero.css">
    <link rel="icon" href="../img/logo-app.png">
</head>
<body>

    <div class="cabecera-sticky">
        <header class="pasajero-header">
            <div class="header-izq">
                <img src="../img/logo-app.png" alt="Logo" class="header-logo">
                <div>
                    <h1 class="header-saludo">Bienvenido </h1>
                    <p class="header-nombre">¡Hola, <?php echo htmlspecialchars($nombre_pasajero); ?>!</p>
                </div>
            </div>
            <div class="header-der">
                <div class="header-fecha-hora">
                    <p class="header-hora" id="reloj">--:--</p>
                    <p class="header-fecha" id="fecha-hoy"></p>
                </div>
                <a href="../includes/logout.php" class="btn-logout" title="Cerrar sesión">
                    <i class="ri-logout-box-r-line"></i>
                </a>
            </div>
        </header>
        <nav class="pasajero-nav">
            <a href="#seccion-rutas" class="pasajero-nav-link">
                <i class="ri-route-line"></i><span>Rutas</span>
            </a>
            <a href="#seccion-eta" class="pasajero-nav-link">
                <i class="ri-timer-flash-line"></i><span>Llegada</span>
            </a>
            <a href="#seccion-mapa" class="pasajero-nav-link">
                <i class="ri-map-pin-range-line"></i><span>Mapa</span>
            </a>
            <a href="#seccion-horarios" class="pasajero-nav-link">
                <i class="ri-calendar-todo-line"></i><span>Horarios</span>
            </a>
        </nav>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="pasajero-main">

        <!-- FILTRADO DE RUTAS -->
        <section id="seccion-rutas" class="card-pasajero animate__animated animate__fadeInDown">
            <div class="card-titulo">
                <i class="ri-search-eye-line"></i>
                <span>Filtrar Rutas de Transporte</span>
            </div>
            <div class="filtro-rutas">
                <div class="filtro-campo">
                    <label for="filtro-origen" class="form-label mb-1 fw-medium" style="font-size:12px; color:#475569;">Sede de Origen</label>
                    <select id="filtro-origen" class="form-select-premium">
                        <option value="">— Seleccionar origen —</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-campo">
                    <label for="filtro-destino" class="form-label mb-1 fw-medium" style="font-size:12px; color:#475569;">Sede de Destino</label>
                    <select id="filtro-destino" class="form-select-premium">
                        <option value="">— Seleccionar destino —</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button id="btn-buscar" class="btn-buscar-rutas">
                    <i class="ri-filter-fill"></i> Filtrar Horarios
                </button>
            </div>
        </section>

        <!--TIEMPO ESTIMADO DE LLEGADA-->
        <section id="seccion-eta" class="card-pasajero eta-card animate__animated animate__fadeInLeft">
            <div class="card-titulo">
                <i class="ri-time-flash-line"></i>
                <span>Tiempo Estimado de Llegada del Transporte</span>
            </div>
            <div class="eta-info">
                <p class="eta-tiempo" id="eta-tiempo">-- min</p>
                <p class="eta-desc" id="eta-desc">Selecciona una ruta para monitorear el transporte activo.</p>
                <div class="eta-ruta" id="eta-ruta" style="display: none;">
                    <i class="ri-route-line"></i> —
                </div>
            </div>
        </section>

        <!-- TARJETA 3: MAPA DE UBICACIÓN EN TIEMPO REAL -->
        <section id="seccion-mapa" class="card-pasajero card-mapa animate__animated animate__fadeInUp">
            <div class="card-titulo">
                <i class="ri-map-pin-range-line"></i>
                <span>Monitoreo en Tiempo Real</span>
            </div>
            <!-- Contenedor del Mapa Activo -->
            <div id="mapa-pasajero"></div>
        </section>

        <!-- TARJETA 4: CRONOGRAMA DE HORARIOS -->
        <section id="seccion-horarios" class="card-pasajero card-horarios animate__animated animate__fadeInRight">
            <div class="card-titulo">
                <i class="ri-calendar-todo-line"></i>
                <span>Horarios Programados (Hoy)</span>
            </div>
            <div id="contenedor-horarios">
                <div class="cargando">
                    <i class="ri-loader-4-line spin" style="font-size: 24px;"></i>
                    <p class="mt-2">Cargando cronograma...</p>
                </div>
            </div>
        </section>

    </main>

    <!-- CONTENEDOR DE ALERTAS TOAST -->
    <div class="alert-toast-container" id="toast-container"></div>

    <!-- Scripts Externos -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Script Propio -->
    <script src="../js/pasajero.js?v=20260615-2"></script>
</body>
</html>
