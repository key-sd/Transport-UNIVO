<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /Transport-UNIVO/login.php");
    exit();
}

include("../includes/conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Cronograma</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>

    <link rel="stylesheet" href="../css/admin.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body>

<div class="admin-wrapper">

    <?php include("../components/sidebar_admin.php"); ?>

    <div class="admin-contenido">

        <?php include("../components/navbar_admin.php"); ?>

        <main class="admin-main">

            <!-- encabezado de sección -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeInDown">
                <div>
                    <h4 class="fw-semibold mb-0" style="color:#0d2346;">
                        <i class="ri-calendar-todo-line m-2" style="color:#f5c518;"></i>Gestión de Cronograma
                    </h4>
                    <p class="text-muted small mb-0">Administra los cronogramas registrados en el sistema</p>
                </div>
                <button class="btn-agregar" id="btnAbrirModalCronograma">
                    <i class="ri-user-add-line me-2"></i>Agregar cronograma
                </button>
            </div>

            <!-- alerta global -->
            <div id="alertaGlobal" class="alerta-global d-none animate__animated"></div>

            <!-- tabla — visible solo en desktop -->
            <div class="tabla-card animate__animated animate__fadeInUp d-none d-md-block">
                <div class="tabla-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-list-check-2" style="color:#f5c518; font-size:18px;"></i>
                        <span class="fw-medium" style="color:#0d2346;">Lista de cronograma de horarios</span>
                    </div>
                    <div class="tabla-buscador">
                        <i class="ri-search-line"></i>
                        <input type="text" id="buscadorCronograma" placeholder="Buscar horario...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="tabla-cronograma">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ruta</th>
                                <th>Horario</th>
                                <th>Día</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaCronograma">
                            <tr>
                                <td colspan="5" class="tabla-empty">
                                    <i class="ri-loader-4-line ri-spin"></i> Cargando cronograma de horarios...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- tarjetas — visible solo en móvil -->
            <div class="d-md-none animate__animated animate__fadeInUp">
                <div class="tabla-buscador mb-3">
                    <i class="ri-search-line"></i>
                    <input type="text" id="buscadorMobileCronograma" placeholder="Buscar horario..." style="flex:1; border:none; outline:none; background:transparent; font-family:'Outfit',sans-serif; font-size:13px; color:#0f172a;">
                </div>
                <div id="contenedorTarjetasCronograma" class="d-flex flex-column gap-3">
                    <div class="text-center p-4 text-muted">
                        <i class="ri-loader-4-line ri-spin me-1"></i> Cargando cronograma de horarios...
                    </div>
                </div>
            </div>

            <!-- modal agregar/editar ruta -->
            <div class="modal fade" id="modalCronograma" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content modal-custom">

                        <div class="modal-custom-header">
                            <div class="d-flex align-items-center gap-2">
                                <div class="modal-icono">
                                    <i class="ri-steering-2-line"></i>
                                </div>
                                <h5 class="mb-0 fw-semibold text-white" id="modalTitle">Nuevo Horario</h5>
                            </div>
                            <button type="button" class="modal-btn-cerrar" data-bs-dismiss="modal" aria-label="Cerrar">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="modal-body p-4">
                            <div id="alertaModalCronograma" class="alerta-modal d-none mb-3 animate__animated"></div>
                            <form id="formCronograma" novalidate>
                                <p class="form-seccion-label">
                                    <i class="ri-map-pin-line me-1"></i>Datos del horario del transporte
                                </p>
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-medium">Ruta *</label>
                                        <div class="input-group campo-input-group">
                                            <span class="input-group-text border-end-0">
                                                <i class="ri-map-pin-2-line text-secondary"></i>
                                            </span>
                                            <select id="ruta" name="ruta" class="form-control border-start-0" required>
                                                <option value="">Seleccionar ruta</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-medium">Horario *</label>
                                        <div class="input-group campo-input-group"><span class="input-group-text border-end-0"> <i class="ri-map-pin-5-line text-secondary"></i></span>
                                        <select id="horario" name="horario" class="form-control border-start-0" required>
                                            <option value="">Seleccionar horario</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-medium">Día *</label>
                                        <div class="input-group campo-input-group"><span class="input-group-text border-end-0"> <i class="ri-map-pin-5-line text-secondary"></i></span>
                                        <select id="dia" name="dia" class="form-control border-start-0" required>
                                            <option value="">Seleccionar Día</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="modal-custom-footer">
                            <button type="button" class="btn-cancelar" data-bs-dismiss="modal">
                                <i class="ri-close-line me-1"></i>Cancelar
                            </button>
                            <button type="button" class="btn-guardar" id="btnGuardarCronograma">
                                <i class="ri-save-line me-1"></i>Guardar horario
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/main.js"></script>
</body>
</html>