<?php
/*
 ┌──────────────────────────────────────────────────────────────┐
 │  conductores/gestionar_conductores.php                       │
 │  Vista principal: tabla de conductores + modal de creación   │
 └──────────────────────────────────────────────────────────────┘
*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/Transport-UNIVO/includes/sesion.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Transport-UNIVO/includes/conexion.php';
solo_admin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gestión de Conductores — Transport UNIVO</title>

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: sans-serif; background: #f4f6f9; color: #333; }

    .page-wrapper { max-width: 1100px; margin: 40px auto; padding: 0 20px; }

    /* Encabezado */
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .section-header h1 { font-size: 1.6rem; }

    /* Botones */
    .btn-primary {
      background: #2563eb; color: #fff; border: none;
      padding: 10px 22px; border-radius: 6px; font-size: 0.95rem; cursor: pointer;
    }
    .btn-primary:hover { background: #1d4ed8; }
    .btn-secondary {
      background: #e5e7eb; color: #374151; border: none;
      padding: 8px 18px; border-radius: 6px; font-size: 0.9rem; cursor: pointer;
    }
    .btn-secondary:hover { background: #d1d5db; }

    /* Tarjeta / Tabla */
    .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.08); overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead { background: #1e40af; color: #fff; }
    thead th { padding: 14px 16px; text-align: left; font-size: 0.88rem; text-transform: uppercase; letter-spacing: .05em; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 12px 16px; font-size: 0.92rem; border-bottom: 1px solid #e5e7eb; }
    tbody tr:hover { background: #eff6ff; }
    .empty-state { text-align: center; padding: 40px 20px; color: #9ca3af; }

    /* Alertas */
    .alert { padding: 10px 14px; border-radius: 6px; font-size: 0.88rem; margin-bottom: 16px; display: none; }
    .alert-success { background: #dcfce7; color: #15803d; display: block; }
    .alert-error   { background: #fee2e2; color: #b91c1c; display: block; }

    /* Modal */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.5); z-index: 900;
      justify-content: center; align-items: center;
    }
    .modal-overlay.active { display: flex; }
    .modal {
      background: #fff; border-radius: 12px; width: 100%; max-width: 560px;
      max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.2);
    }
    .modal-header {
      display: flex; justify-content: space-between; align-items: center;
      padding: 20px 24px; border-bottom: 1px solid #e5e7eb;
    }
    .modal-header h2 { font-size: 1.15rem; }
    .modal-close { background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #6b7280; }
    .modal-body  { padding: 24px; }
    .modal-footer {
      display: flex; justify-content: flex-end; gap: 10px;
      padding: 16px 24px; border-top: 1px solid #e5e7eb;
    }

    /* Formulario */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: #374151; }
    .form-group input {
      padding: 9px 12px; border: 1px solid #d1d5db;
      border-radius: 6px; font-size: 0.92rem; outline: none; transition: border-color .15s;
    }
    .form-group input:focus { border-color: #2563eb; }
    .form-separator { grid-column: 1 / -1; border: none; border-top: 1px dashed #e5e7eb; margin: 4px 0; }
    .form-section-label {
      grid-column: 1 / -1; font-size: 0.78rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-top: 4px;
    }
    .hint { font-size: 0.75rem; color: #9ca3af; margin-top: 2px; }

    @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

<div class="page-wrapper">

  <div class="section-header">
    <h1>🚌 Gestión de Conductores</h1>
    <button class="btn-primary" id="btnAbrirModal">+ Agregar Conductor</button>
  </div>

  <div id="alertaGlobal" class="alert" role="alert"></div>

  <div class="card">
    <table id="tablaConductores">
      <thead>
        <tr>
          <th>#</th>
          <th>Nombre completo</th>
          <th>Teléfono</th>
          <th>Código universitario</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="cuerpoTabla">
        <tr><td colspan="5" class="empty-state">Cargando conductores…</td></tr>
      </tbody>
    </table>
  </div>

</div>


<!-- ══ MODAL ══ -->
<div class="modal-overlay" id="modalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">

    <div class="modal-header">
      <h2 id="modalTitle">Nuevo Conductor</h2>
      <button class="modal-close" id="btnCerrarModal" aria-label="Cerrar">&times;</button>
    </div>

    <div class="modal-body">
      <div id="alertaModal" class="alert" role="alert"></div>

      <form id="formConductor" novalidate>
        <div class="form-grid">

          <!-- DATOS PERSONALES -->
          <span class="form-section-label">Datos personales</span>

          <div class="form-group">
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan" required />
          </div>

          <div class="form-group">
            <label for="apellido">Apellido *</label>
            <input type="text" id="apellido" name="apellido" placeholder="Ej: Pérez" required />
          </div>

          <div class="form-group full">
            <label for="telefono">Teléfono *</label>
            <input type="tel" id="telefono" name="telefono" placeholder="Ej: 7000-1234" required />
          </div>

          <hr class="form-separator" />

          <!-- CREDENCIALES -->
          <span class="form-section-label">Acceso al sistema</span>

          <div class="form-group full">
            <label for="codigo_universitario">Código universitario *</label>
            <input type="text" id="codigo_universitario" name="codigo_universitario"
                   placeholder="Ej: u2026010" required />
            <span class="hint">Este código será el nombre de usuario para iniciar sesión.</span>
          </div>

          <div class="form-group">
            <label for="password">Contraseña temporal *</label>
            <input type="password" id="password" name="password"
                   placeholder="Mínimo 8 caracteres" required autocomplete="new-password"/>
          </div>

          <div class="form-group">
            <label for="confirmar_password">Confirmar contraseña *</label>
            <input type="password" id="confirmar_password" name="confirmar_password"
                   placeholder="Repetir contraseña" required />
          </div>

        </div>
      </form>
    </div>

    <div class="modal-footer">
      <button class="btn-secondary" id="btnCancelar">Cancelar</button>
      <button class="btn-primary"   id="btnGuardar">Guardar conductor</button>
    </div>

  </div>
</div>


<script>
const overlay      = document.getElementById('modalOverlay');
const btnAbrir     = document.getElementById('btnAbrirModal');
const btnCerrar    = document.getElementById('btnCerrarModal');
const btnCancelar  = document.getElementById('btnCancelar');
const btnGuardar   = document.getElementById('btnGuardar');
const form         = document.getElementById('formConductor');
const alertaModal  = document.getElementById('alertaModal');
const alertaGlobal = document.getElementById('alertaGlobal');
const cuerpoTabla  = document.getElementById('cuerpoTabla');

/* ── Modal ── */
const abrirModal  = () => overlay.classList.add('active');
const cerrarModal = () => {
  overlay.classList.remove('active');
  form.reset();
  ocultarAlerta(alertaModal);
};

btnAbrir.addEventListener('click', abrirModal);
btnCerrar.addEventListener('click', cerrarModal);
btnCancelar.addEventListener('click', cerrarModal);
overlay.addEventListener('click', (e) => { if (e.target === overlay) cerrarModal(); });

/* ── Alertas ── */
function mostrarAlerta(el, msg, tipo) { el.textContent = msg; el.className = `alert alert-${tipo}`; }
function ocultarAlerta(el)            { el.textContent = ''; el.className = 'alert'; }

/* ── Cargar tabla ── */
function cargarConductores() {
  fetch('listar_conductores.php')
    .then(r => r.json())
    .then(data => {
      if (!data.length) {
        cuerpoTabla.innerHTML = '<tr><td colspan="5" class="empty-state">No hay conductores registrados.</td></tr>';
        return;
      }
      cuerpoTabla.innerHTML = data.map((c, i) => `
        <tr>
          <td>${i + 1}</td>
          <td>${c.nombre} ${c.apellido}</td>
          <td>${c.telefono}</td>
          <td><code>${c.codigo_universitario}</code></td>
          <td>
            <button class="btn-secondary" style="padding:5px 10px;font-size:.8rem">Ver</button>
          </td>
        </tr>
      `).join('');
    })
    .catch(() => {
      cuerpoTabla.innerHTML = '<tr><td colspan="5" class="empty-state">Error al cargar los datos.</td></tr>';
    });
}
cargarConductores();

/* ── Guardar ── */
btnGuardar.addEventListener('click', () => {
  ocultarAlerta(alertaModal);

  const pwd  = document.getElementById('password').value;
  const pwd2 = document.getElementById('confirmar_password').value;

  if (!form.checkValidity()) { form.reportValidity(); return; }

  if (pwd !== pwd2) {
    mostrarAlerta(alertaModal, 'Las contraseñas no coinciden.', 'error'); return;
  }
  if (pwd.length < 8) {
    mostrarAlerta(alertaModal, 'La contraseña debe tener al menos 8 caracteres.', 'error'); return;
  }

  btnGuardar.disabled    = true;
  btnGuardar.textContent = 'Guardando…';

  fetch('crear_conductor.php', { method: 'POST', body: new FormData(form) })
    .then(r => r.json())
    .then(resp => {
      if (resp.success) {
        cerrarModal();
        mostrarAlerta(alertaGlobal, '✅ ' + resp.message, 'success');
        cargarConductores();
      } else {
        mostrarAlerta(alertaModal, '❌ ' + resp.message, 'error');
      }
    })
    .catch(() => mostrarAlerta(alertaModal, 'Error de conexión. Intente de nuevo.', 'error'))
    .finally(() => {
      btnGuardar.disabled    = false;
      btnGuardar.textContent = 'Guardar conductor';
    });
});
</script>

</body>
</html>