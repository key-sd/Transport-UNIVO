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
  <title>Admin — Asignaciones</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../css/admin.css">
  <link rel="icon" href="../img/logo-app.png">
</head>

<body>
  <div class="admin-wrapper">
    <?php include("../components/sidebar_admin.php"); ?>
    <div class="admin-contenido">
      <?php include("../components/navbar_admin.php"); ?>
      <main class="admin-main">

        <!-- Encabezado -->
        <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeInDown">
          <div>
            <h4 class="fw-semibold mb-0" style="color:#0d2346;">
              <i class="ri-steering-line m-2" style="color:#f5c518;"></i>Gestión de Asignaciones
            </h4>
            <p class="text-muted small mb-0">Asigna conductores y unidades a los bloques horarios de cada ruta</p>
          </div>
          <button class="btn-agregar" id="btnNuevaAsignacion">
            <i class="ri-add-line me-2"></i>Nueva asignación
          </button>
        </div>

        <div id="alertaGlobal" class="alerta-global d-none animate__animated"></div>

        <!-- Tabla desktop -->
        <div class="tabla-card animate__animated animate__fadeInUp d-none d-md-block">
          <div class="tabla-card-header">
            <div class="d-flex align-items-center gap-2">
              <i class="ri-steering-line" style="color:#f5c518; font-size:18px;"></i>
              <span class="fw-medium" style="color:#0d2346;">Conductores asignados</span>
            </div>
            <div class="tabla-buscador">
              <i class="ri-search-line"></i>
              <input type="text" id="buscadorAsig" placeholder="Buscar conductor...">
            </div>
          </div>
          <div class="table-responsive">
            <table class="tabla-asignaciones">
              <thead>
                <tr>
                  <th>Conductor</th>
                  <th>Rutas que cubre</th>
                  <th>Días</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaAsig">
                <tr>
                  <td colspan="5" class="tabla-empty">
                    <i class="ri-loader-4-line ri-spin"></i> Cargando asignaciones...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tarjetas móvil -->
        <div class="d-md-none animate__animated animate__fadeInUp">
          <div class="tabla-buscador mb-3">
            <i class="ri-search-line"></i>
            <input type="text" id="buscadorMobileAsig" placeholder="Buscar conductor..."
              style="flex:1;border:none;outline:none;background:transparent;font-family:'Outfit',sans-serif;font-size:13px;color:#0f172a;">
          </div>
          <div id="contenedorTarjetasAsig" class="d-flex flex-column gap-3">
            <div class="text-center p-4 text-muted">
              <i class="ri-loader-4-line ri-spin me-1"></i> Cargando...
            </div>
          </div>
        </div>
        <!-- MODAL: NUEVA ASIGNACIÓN -->
        <div class="modal fade" id="modalNuevaAsig" tabindex="-1" aria-labelledby="modalNuevaAsigLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header" style="background:#0d2346; color:#fff; padding:1rem 1.5rem;">
                <h5 class="modal-title" id="modalNuevaAsigLabel" style="font-size:15px; font-weight:600;">
                  <i class="ri-calendar-check-line me-2"></i>Nueva asignación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body" style="padding:1.5rem;">

                <div id="alertaModalAsig" class="alerta-modal d-none mb-3"></div>

                <!-- Ruta -->
                <div class="asig-seccion-label"><i class="ri-map-pin-2-line"></i>Ruta</div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Sede origen</label>
                    <select id="asig_origen" class="form-select form-select-sm">
                      <option value="">— Origen —</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Sede destino</label>
                    <select id="asig_destino" class="form-select form-select-sm">
                      <option value="">— Destino —</option>
                    </select>
                  </div>
                </div>

                <!-- Horarios -->
                <div class="asig-seccion-label"><i class="ri-time-line"></i>Horarios disponibles</div>
                <div class="horarios-grilla-wrap mb-3">
                  <div class="horarios-quick-actions">
                    <span id="contadorSeleccionados" class="horarios-counter">0 horarios seleccionados</span>
                    <div class="d-flex gap-2">
                      <button class="btn-quick-sel" id="btnSelTodosHorarios">Seleccionar todos</button>
                      <button class="btn-quick-sel" id="btnDeselTodosHorarios">Limpiar</button>
                    </div>
                  </div>
                  <div id="gridHorarios"></div>
                  <div id="avisoSinHorarios" class="aviso-sin-horarios d-none">
                    <i class="ri-calendar-close-line"></i>
                    No hay horarios disponibles para esta ruta.
                  </div>
                  <div id="avisoElegirRuta" class="aviso-sin-horarios">
                    <i class="ri-arrow-up-line"></i>
                    Selecciona una ruta para ver los horarios disponibles.
                  </div>
                </div>

                <!-- Conductor y unidad -->
                <div class="asig-seccion-label"><i class="ri-steering-2-line"></i>Conductor y unidad</div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Conductor</label>
                    <select id="asig_conductor" class="form-select form-select-sm">
                      <option value="">— Seleccionar conductor —</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Unidad (vehículo)</label>
                    <select id="asig_unidad" class="form-select form-select-sm">
                      <option value="">— Seleccionar —</option>
                    </select>
                  </div>
                </div>

                <!-- Vigencia -->
                <div class="asig-seccion-label"><i class="ri-calendar-line"></i>Vigencia</div>
                <div class="asig-vigencia-grid">
                  <div>
                    <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Desde <span style="color:#dc2626;">*</span></label>
                    <input type="date" id="asig_desde" class="form-control form-control-sm">
                  </div>
                  <div>
                    <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Hasta <span style="color:#94a3b8;">(opcional)</span></label>
                    <input type="date" id="asig_hasta" class="form-control form-control-sm">
                  </div>
                </div>

              </div>

              <div class="modal-footer" style="padding:1rem 1.5rem; border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarAsig" class="btn-agregar">
                  <i class="ri-save-line me-1"></i>Guardar asignaciones
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- MODAL: DETALLE / EDITAR CONDUCTOR -->
        <div class="modal fade" id="modalDetalleConductor" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

              <div class="modal-header" style="background:#0d2346; padding:1rem 1.5rem; border-bottom:none;">
                <div class="d-flex align-items-center gap-3 w-100">
                  <div class="conductor-avatar-lg" id="detalleAvatar">??</div>
                  <div class="flex-grow-1">
                    <h5 id="detalleNombre" class="mb-0" style="color:#fff; font-size:16px; font-weight:600;"></h5>
                    <div class="d-flex align-items-center gap-2 mt-1">
                      <span style="color:#94a3b8; font-size:12px;">Conductor de transporte</span>
                      <span id="detalleEstadoBadge" class="badge-estado"></span>
                    </div>
                  </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body" style="padding:1.25rem 1.5rem;">
                <div id="alertaDetalle" class="alerta-modal d-none mb-3"></div>

                <!-- Filtros de vista -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <p class="mb-0" style="font-size:13px; font-weight:600; color:#0d2346;">
                    <i class="ri-calendar-check-line me-1"></i>Asignaciones activas
                  </p>
                  <div class="d-flex gap-2">
                    <button class="btn-quick-sel btn-vista-detalle activo-vista" data-vista="tabla" id="btnVistaTabla">
                      <i class="ri-table-line me-1"></i>Tabla
                    </button>
                    <button class="btn-quick-sel btn-vista-detalle" data-vista="ruta" id="btnVistaRuta">
                      <i class="ri-route-line me-1"></i>Por ruta/día
                    </button>
                  </div>
                </div>

                <!-- Vista tabla -->
                <div id="vistaTablaDetalle">
                  <div class="table-responsive">
                    <table class="tabla-asig-detalle">
                      <thead>
                        <tr>
                          <th>Ruta</th>
                          <th>Día</th>
                          <th>Hora</th>
                          <th>Turno</th>
                          <th>Unidad</th>
                          <th>Acción</th>
                        </tr>
                      </thead>
                      <tbody id="cuerpoTablaAsignacionesDetalle">
                        <tr><td colspan="6" class="tabla-empty"><i class="ri-loader-4-line ri-spin"></i></td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <!-- Vista agrupada por ruta/día -->
                <div id="vistaRutaDetalle" class="d-none">
                  <div id="gruposRutaDia"></div>
                </div>
              </div>
              <div class="modal-footer" style="padding:0.75rem 1.5rem; border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cerrar</button>
              </div>
            </div>
          </div>
        </div>
        <!-- MODAL: REASIGNAR (EDITAR) CONDUCTOR / UNIDAD -->
        <div class="modal fade" id="modalReasignar" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
            <div class="modal-content">

              <div class="modal-header" style="background:#0d2346; color:#fff; padding:0.9rem 1.25rem;">
                <h5 class="modal-title" style="font-size:14px; font-weight:600;">
                  <i class="ri-user-follow-line me-2"></i>Editar asignación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body" style="padding:1.25rem;">
                <!-- Info del horario seleccionado -->
                <div id="reasignarInfoHorario" class="asig-horario-info-bloque mb-3 d-none"></div>

                <div class="reasignar-info-bloque mb-3">
                  <i class="ri-information-line"></i>
                  <span>Se cerrará la asignación actual y se creará una nueva con los datos que elijas.</span>
                </div>

                <div class="mb-3">
                  <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Conductor</label>
                  <select id="reasig_conductor" class="form-select form-select-sm">
                    <option value="">— Seleccionar conductor —</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Unidad (vehículo)</label>
                  <select id="reasig_unidad" class="form-select form-select-sm">
                    <option value="">— Seleccionar unidad —</option>
                  </select>
                </div>

                <div class="asig-vigencia-grid">
                  <div>
                    <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Desde <span style="color:#dc2626;">*</span></label>
                    <input type="date" id="reasig_desde" class="form-control form-control-sm">
                  </div>
                  <div>
                    <label class="form-label" style="font-size:12px; color:#64748b; margin-bottom:4px;">Hasta <span style="color:#94a3b8;">(opcional)</span></label>
                    <input type="date" id="reasig_hasta" class="form-control form-control-sm">
                  </div>
                </div>
              </div>

              <div class="modal-footer" style="padding:0.75rem 1.25rem; border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarReasignacion" class="btn-agregar">
                  <i class="ri-save-line me-1"></i>Guardar cambio
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