// PARA EL LOGIN
// carrusel de fondo
const slides = document.querySelectorAll('.fondo-slide');
const dots   = document.querySelectorAll('.dot');
let actual   = 0;

function cambiarSlide(nuevo) {
    if (!slides[actual] || !dots[actual]) return;
    slides[actual].classList.remove('activo');
    dots[actual].classList.remove('activo');
    actual = nuevo;
    slides[actual].classList.add('activo');
    dots[actual].classList.add('activo');
}

// avanza solo cada 4 segundos
if (slides.length > 0) {
    setInterval(() => cambiarSlide((actual + 1) % slides.length), 4000);

    // clic en los puntitos
    dots.forEach(dot => {
        dot.addEventListener('click', function () {
            cambiarSlide(parseInt(this.dataset.index));
        });
    });
}

// ojito para ver/ocultar contraseña
const btnVerPass = document.getElementById('btnVerPass');
const inputPass  = document.getElementById('password');
const iconoOjo   = document.getElementById('iconoOjo');

if (btnVerPass) {
    btnVerPass.addEventListener('click', function () {
        const esPassword = inputPass.type === 'password';
        inputPass.type   = esPassword ? 'text' : 'password';
        iconoOjo.classList.toggle('bi-eye',       !esPassword);
        iconoOjo.classList.toggle('bi-eye-slash',  esPassword);
    });
}

// limpiar campos al recargar la página del login
window.onload = function () {
    const txtUsuario  = document.getElementById('usuario');
    const txtPassword = document.getElementById('password');
    if (txtUsuario)  txtUsuario.value  = '';
    if (txtPassword) txtPassword.value = '';
};

// UTILIDADES DE ALERTAS PARA TODO EL SISTEMA
const alertaGlobalElement = document.getElementById('alertaGlobal');

// muestra una alerta dentro de un modal
function mostrarAlerta(el, tipo, mensaje) {
    if (!el) return;
    el.className = `alerta-modal animate__animated animate__shakeX ${tipo === 'error' ? 'alerta-error' : 'alerta-exito'}`;
    el.innerHTML = `<i class="ri-${tipo === 'error' ? 'error-warning' : 'checkbox-circle'}-line me-2"></i>${mensaje}`;
    el.classList.remove('d-none');
}

// muestra una alerta flotante en la página y desaparece a los 4 segundos
function mostrarAlertaGlobal(tipo, mensaje) {
    if (!alertaGlobalElement) return;
    alertaGlobalElement.className = `alerta-global animate__animated animate__fadeInDown ${tipo === 'exito' ? 'alerta-exito' : 'alerta-error'}`;
    alertaGlobalElement.innerHTML = `<i class="ri-${tipo === 'exito' ? 'checkbox-circle' : 'error-warning'}-line me-2"></i>${mensaje}`;
    alertaGlobalElement.classList.remove('d-none');
    setTimeout(() => alertaGlobalElement.classList.add('d-none'), 4000);
}

// limpia y oculta cualquier alerta
function ocultarAlerta(el) {
    if (!el) return;
    el.className = 'd-none';
    el.innerHTML = '';
}

// cambia el estado de activo/inactivo con mensajito bonito, reutilizable para cualquier tabla
function cambiarEstado(id, estadoActual, archivo, entidad, callback, textos) {
    const nuevoEstado  = parseInt(estadoActual) === 1 ? 0 : 1;
    const accionTexto  = nuevoEstado === 0 ? 'desactivar' : 'activar';
    const esDesactivar = nuevoEstado === 0;

    const textoDesactivar = textos?.desactivar || `Este ${entidad} no podrá acceder al sistema.`;
    const textoActivar    = textos?.activar    || `Este ${entidad} podrá acceder al sistema nuevamente.`;

    Swal.fire({
        title: `¿${accionTexto.charAt(0).toUpperCase() + accionTexto.slice(1)} ${entidad}?`,
        text: esDesactivar ? textoDesactivar : textoActivar,
        icon: esDesactivar ? 'warning' : 'question',
        iconColor: esDesactivar ? '#ef4444' : '#16a34a',
        showCancelButton: true,
        confirmButtonText: `Sí, ${accionTexto}`,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: esDesactivar ? '#ef4444' : '#16a34a',
        cancelButtonColor: '#64748b',
        width: '380px',
        padding: '1.5rem',
        customClass: {
            popup:         'rounded-4',
            title:         'fs-5 fw-semibold',
            htmlContainer: 'fs-6 text-muted',
            confirmButton: 'btn btn-sm px-4 rounded-3',
            cancelButton:  'btn btn-sm px-4 rounded-3',
            icon:          'swal-icono-chico'
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        const datos = new FormData();
        datos.append('id', id);
        datos.append('estado', nuevoEstado);

        fetch(archivo, { method: 'POST', body: datos })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    mostrarAlertaGlobal('exito', resp.message);
                    if (callback) callback();
                } else {
                    mostrarAlertaGlobal('error', resp.message);
                }
            })
            .catch(() => mostrarAlertaGlobal('error', 'Error al procesar la solicitud.'));
    });
}

// fecha del día con Moment.js para el navbar
const txtFechaHoy = document.getElementById('fecha-hoy');
if (txtFechaHoy && typeof moment !== 'undefined') {
    const diasES  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const mesesES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    const hoy     = moment();
    txtFechaHoy.textContent = diasES[hoy.day()] + ', ' + hoy.date() + ' de ' + mesesES[hoy.month()] + ' de ' + hoy.year();
}

// ══════════════════════════════════════════════════════════════
// PARA LA SECCIÓN DE CONDUCTORES
// ══════════════════════════════════════════════════════════════
const elModalConductor   = document.getElementById('modalConductor');
const modalBS            = elModalConductor ? new bootstrap.Modal(elModalConductor) : null;
const btnAbrir           = document.getElementById('btnAbrirModal');
const btnGuardar         = document.getElementById('btnGuardar');
const form               = document.getElementById('formConductor');
const alertaModal        = document.getElementById('alertaModal');
const cuerpoTabla        = document.getElementById('cuerpoTabla');
const buscador           = document.getElementById('buscador');
const contenedorTarjetas = document.getElementById('contenedorTarjetas');
const buscadorMobile     = document.getElementById('buscadorMobile');

let conductores     = [];
let modoModal       = 'crear';
let conductorEditId = null;

// helpers para manejar el required de contraseñas según el modo
function activarRequiredPassword() {
    document.getElementById('password')?.setAttribute('required', 'required');
    document.getElementById('confirmar_password')?.setAttribute('required', 'required');
}
function desactivarRequiredPassword() {
    document.getElementById('password')?.removeAttribute('required');
    document.getElementById('confirmar_password')?.removeAttribute('required');
}
function setHintPassword(texto) {
    const hint = document.getElementById('hintPassword');
    if (hint) hint.textContent = texto;
}

// abre el modal limpio para AGREGAR un conductor
if (btnAbrir) {
    btnAbrir.addEventListener('click', () => {
        modoModal       = 'crear';
        conductorEditId = null;

        document.getElementById('modalTitle').textContent = 'Nuevo Conductor';
        btnGuardar.innerHTML = '<i class="ri-save-line me-1"></i>Guardar conductor';

        activarRequiredPassword();
        setHintPassword('');
        form.reset();
        ocultarAlerta(alertaModal);
        if (modalBS) modalBS.show();
    });
}

// limpia el form y resetea el modo cuando se cierra el modal
if (elModalConductor) {
    elModalConductor.addEventListener('hidden.bs.modal', () => {
        modoModal       = 'crear';
        conductorEditId = null;

        document.getElementById('modalTitle').textContent = 'Nuevo Conductor';
        btnGuardar.innerHTML = '<i class="ri-save-line me-1"></i>Guardar conductor';

        activarRequiredPassword();
        setHintPassword('');
        form.reset();
        ocultarAlerta(alertaModal);
    });
}

// carga la tabla al entrar a la página
if (cuerpoTabla) document.addEventListener('DOMContentLoaded', cargarConductores);

function cargarConductores() {
    if (!cuerpoTabla) return;
    cuerpoTabla.innerHTML = `<tr><td colspan="5" class="tabla-empty"><i class="ri-loader-4-line ri-spin"></i> Cargando conductores...</td></tr>`;

    fetch('listar_conductores.php')
        .then(r => r.json())
        .then(data => { conductores = data; renderTabla(conductores); })
        .catch(() => {
            cuerpoTabla.innerHTML = `<tr><td colspan="5" class="tabla-empty"><i class="ri-error-warning-line"></i> Error al cargar los datos.</td></tr>`;
        });
}

// carga la tabla de conductores en desktop
function renderTabla(lista) {
    if (!cuerpoTabla) return;

    const activos   = lista.filter(c => parseInt(c.estado) === 1);
    const inactivos = lista.filter(c => parseInt(c.estado) === 0);
    lista = [...activos, ...inactivos];

    if (!lista.length) {
        cuerpoTabla.innerHTML = `<tr><td colspan="5" class="tabla-empty"><i class="ri-user-search-line"></i> No hay conductores registrados.</td></tr>`;
        renderTarjetas([]);
        return;
    }

    cuerpoTabla.innerHTML = lista.map((c, i) => {
        const ini        = (c.nombre[0] + c.apellido[0]).toUpperCase();
        const esInactivo = parseInt(c.estado) === 0;
        return `
        <tr class="animate__animated animate__fadeIn ${esInactivo ? 'fila-inactiva' : ''}">
            <td>${i + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="conductor-tabla-avatar">${ini}</div>
                    <span>${c.nombre} ${c.apellido}</span>
                </div>
            </td>
            <td><i class="ri-phone-line me-1 text-muted"></i>${c.telefono}</td>
            <td><i class="ri-id-card-line me-1 text-muted"></i><code>${c.codigo}</code></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    ${!esInactivo ? `
                        <button class="btn-accion btn-accion-editar" onclick="editarConductor(${c.id})">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    ` : ''}
                    <button class="btn-accion ${!esInactivo ? 'btn-accion-activo' : 'btn-accion-inactivo'}"
                            onclick="cambiarEstadoConductor(${c.id}, ${c.estado})">
                        ${!esInactivo ? 'Activo' : 'Inactivo'}
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    renderTarjetas(lista);
}

// carga las tarjetas en móvil de los conductores
function renderTarjetas(lista) {
    if (!contenedorTarjetas) return;
    if (!lista.length) {
        contenedorTarjetas.innerHTML = `<div class="text-center p-4 text-muted"><i class="ri-user-search-line d-block mb-2" style="font-size:2rem;"></i>No hay conductores registrados.</div>`;
        return;
    }

    contenedorTarjetas.innerHTML = lista.map(c => {
        const ini        = (c.nombre[0] + c.apellido[0]).toUpperCase();
        const esInactivo = parseInt(c.estado) === 0;
        return `
        <div class="conductor-card-mobile animate__animated animate__fadeIn ${esInactivo ? 'fila-inactiva' : ''}">
            <div class="d-flex align-items-center gap-3">
                <div class="conductor-card-avatar">${ini}</div>
                <div class="flex-grow-1">
                    <p class="fw-semibold mb-0" style="color:#0d2346; font-size:14px;">${c.nombre} ${c.apellido}</p>
                    <p class="text-muted mb-0" style="font-size:12px;"><i class="ri-id-card-line me-1"></i><code>${c.codigo}</code></p>
                    <p class="text-muted mb-0" style="font-size:12px;"><i class="ri-phone-line me-1"></i>${c.telefono}</p>
                </div>
                <div class="d-flex flex-column gap-1">
                    ${!esInactivo ? `
                        <button class="btn-accion btn-accion-editar" onclick="editarConductor(${c.id})">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    ` : ''}
                    <button class="btn-accion ${!esInactivo ? 'btn-accion-activo' : 'btn-accion-inactivo'}"
                            onclick="cambiarEstadoConductor(${c.id}, ${c.estado})">
                        ${!esInactivo ? 'Activo' : 'Inactivo'}
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}

// buscador desktop
if (buscador) {
    buscador.addEventListener('input', () => {
        const q = buscador.value.toLowerCase();
        renderTabla(conductores.filter(c =>
            c.nombre.toLowerCase().includes(q)               ||
            c.apellido.toLowerCase().includes(q)             ||
            c.codigo.toLowerCase().includes(q) ||
            c.telefono.includes(q)
        ));
    });
}

// buscador móvil — sincronizado con el de desktop
if (buscadorMobile) {
    buscadorMobile.addEventListener('input', () => {
        const q = buscadorMobile.value.toLowerCase();
        renderTabla(conductores.filter(c =>
            c.nombre.toLowerCase().includes(q)               ||
            c.apellido.toLowerCase().includes(q)             ||
            c.codigo.toLowerCase().includes(q) ||
            c.telefono.includes(q)
        ));
        if (buscador) buscador.value = buscadorMobile.value;
    });
}

// guarda conductor — detecta si es crear o editar y llama al archivo correcto
if (btnGuardar) {
    btnGuardar.addEventListener('click', () => {
        ocultarAlerta(alertaModal);

        const pwd  = document.getElementById('password')?.value  || '';
        const pwd2 = document.getElementById('confirmar_password')?.value || '';

        if (!form.checkValidity()) { form.reportValidity(); return; }

        if (modoModal === 'crear') {
            if (pwd !== pwd2)   { mostrarAlerta(alertaModal, 'error', 'Las contraseñas no coinciden.'); return; }
            if (pwd.length < 8) { mostrarAlerta(alertaModal, 'error', 'La contraseña debe tener al menos 8 caracteres.'); return; }
        }

        if (modoModal === 'editar' && pwd !== '') {
            if (pwd !== pwd2)   { mostrarAlerta(alertaModal, 'error', 'Las contraseñas no coinciden.'); return; }
            if (pwd.length < 8) { mostrarAlerta(alertaModal, 'error', 'La contraseña debe tener al menos 8 caracteres.'); return; }
        }

        btnGuardar.disabled  = true;
        btnGuardar.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i>Guardando...';

        const datos = new FormData(form);
        let archivo = 'crear_conductor.php';

        if (modoModal === 'editar') {
            datos.append('conductor_id', conductorEditId);
            archivo = 'editar_conductor.php';
        }

        fetch(archivo, { method: 'POST', body: datos })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    if (modalBS) modalBS.hide();
                    mostrarAlertaGlobal('exito', resp.message);
                    cargarConductores();
                } else {
                    mostrarAlerta(alertaModal, 'error', resp.message);
                }
            })
            .catch(() => mostrarAlerta(alertaModal, 'error', 'Error de conexión. Intenta de nuevo.'))
            .finally(() => {
                btnGuardar.disabled  = false;
                btnGuardar.innerHTML = modoModal === 'editar'
                    ? '<i class="ri-save-line me-1"></i>Guardar cambios'
                    : '<i class="ri-save-line me-1"></i>Guardar conductor';
            });
    });
}

// llama a cambiarEstado con los parámetros del conductor
function cambiarEstadoConductor(id, estadoActual) {
    cambiarEstado(id, estadoActual, 'cambiar_estado_conductor.php', 'conductor', cargarConductores);
}

// abre el modal en modo EDITAR con los datos del conductor precargados
function editarConductor(id) {
    const c = conductores.find(c => parseInt(c.id) === parseInt(id));
    if (!c) {
        mostrarAlertaGlobal('error', 'No se encontraron los datos del conductor.');
        return;
    }

    modoModal       = 'editar';
    conductorEditId = id;

    document.getElementById('modalTitle').textContent = 'Editar Conductor';
    btnGuardar.innerHTML = '<i class="ri-save-line me-1"></i>Guardar cambios';

    document.getElementById('nombre').value               = c.nombre;
    document.getElementById('apellido').value             = c.apellido;
    document.getElementById('telefono').value             = c.telefono;
    document.getElementById('codigo').value = c.codigo;

    document.getElementById('password').value             = '';
    document.getElementById('confirmar_password').value   = '';
    desactivarRequiredPassword();
    setHintPassword('Déjala en blanco para no cambiarla.');

    ocultarAlerta(alertaModal);
    if (modalBS) modalBS.show();
}

// ══════════════════════════════════════════════════════════════
// SECCIÓN: GESTIÓN DE CRONOGRAMA DE HORARIOS
// ══════════════════════════════════════════════════════════════

const elModalCronograma     = document.getElementById('modalCronograma');
const modalCronogramaBS     = elModalCronograma ? new bootstrap.Modal(elModalCronograma) : null;
const btnAbrirCronograma    = document.getElementById('btnAbrirModalCronograma');
const btnGuardarCronograma  = document.getElementById('btnGuardarCronograma');
const alertaModalCronograma = document.getElementById('alertaModalCronograma');
const cuerpoTablaCronograma = document.getElementById('cuerpoTablaCronograma');
const buscadorCronograma    = document.getElementById('buscadorCronograma');
const contenedorTarjetasCronograma = document.getElementById('contenedorTarjetasCronograma');
const buscadorMobileCronograma     = document.getElementById('buscadorMobileCronograma');

let cronogramas         = [];
let modoModalCronograma = 'crear'; // 'crear' | 'ver' | 'editar'
let rutaEditando        = null;

const ORDEN_DIAS = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

// ── sanitiza el nombre del día para usarlo como ID en el DOM ──
// "Miércoles" → "Miercoles", "Sábado" → "Sabado"
function diaId(dia) {
    return dia.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

//  helpers de turno y formato de hora para mostrar en el acordeón
function calcularTurno(horaStr) {
    const [h, m] = horaStr.split(':').map(Number);
    const mins = h * 60 + m;
    if (mins < 6 * 60 || mins > 18 * 60) return null;
    return mins < 12 * 60 ? 'Matutino' : 'Vespertino';
}

function formatHora(horaStr) {
    const [h, m] = horaStr.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12  = h % 12 || 12;
    return `${String(h12).padStart(2,'0')}:${String(m).padStart(2,'0')} ${ampm}`;
}

// TABLA PRINCIPAL — carga el cronograma al entrar a la página
if (cuerpoTablaCronograma) {
    document.addEventListener('DOMContentLoaded', cargarCronogramas);
}

function cargarCronogramas() {
    if (!cuerpoTablaCronograma) return;
    cuerpoTablaCronograma.innerHTML = `
        <tr><td colspan="4" class="tabla-empty">
            <i class="ri-loader-4-line ri-spin"></i> Cargando cronograma de horarios...
        </td></tr>`;

    fetch('listar_cronogramas.php')
        .then(r => r.json())
        .then(data => { cronogramas = data; renderTablaCronograma(cronogramas); })
        .catch(() => {
            cuerpoTablaCronograma.innerHTML = `
                <tr><td colspan="4" class="tabla-empty">
                    <i class="ri-error-warning-line"></i> Error al cargar los datos.
                </td></tr>`;
        });
}

function renderTablaCronograma(lista) {
    if (!cuerpoTablaCronograma) return;

    if (!lista.length) {
        cuerpoTablaCronograma.innerHTML = `
            <tr><td colspan="4" class="tabla-empty">
                <i class="ri-calendar-close-line"></i> No hay cronogramas registrados.
            </td></tr>`;
        renderTarjetasCronograma([]);
        return;
    }

    cuerpoTablaCronograma.innerHTML = lista.map((r, i) => {
        const diasBadges = r.dias.map(d =>
            `<span class="badge-dia">${d.substring(0,3)}</span>`
        ).join('');

        return `
        <tr class="animate__animated animate__fadeIn">
            <td>${i + 1}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-map-pin-2-line text-muted"></i>
                    <span class="fw-medium">${r.origen}</span>
                    <i class="ri-arrow-right-line text-muted"></i>
                    <span class="fw-medium">${r.destino}</span>
                </div>
            </td>
            <td><div class="d-flex flex-wrap gap-1">${diasBadges}</div></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn-accion btn-accion-ver"
                            onclick="verCronograma('${r.id_sede_origen}','${r.id_sede_destino}','${r.origen}','${r.destino}')">
                        <i class="ri-eye-line me-1"></i>Ver
                    </button>
                    <button class="btn-accion btn-accion-editar"
                            onclick="editarCronograma('${r.id_sede_origen}','${r.id_sede_destino}','${r.origen}','${r.destino}')">
                        <i class="ri-edit-line me-1"></i>Editar
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    renderTarjetasCronograma(lista);
}

function renderTarjetasCronograma(lista) {
    if (!contenedorTarjetasCronograma) return;

    if (!lista.length) {
        contenedorTarjetasCronograma.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="ri-calendar-close-line d-block mb-2" style="font-size:2rem;"></i>
                No hay cronogramas registrados.
            </div>`;
        return;
    }

    contenedorTarjetasCronograma.innerHTML = lista.map(r => {
        const diasBadges = r.dias.map(d =>
            `<span class="badge-dia">${d.substring(0,3)}</span>`
        ).join('');

        return `
        <div class="conductor-card-mobile animate__animated animate__fadeIn">
            <div class="d-flex align-items-start gap-3">
                <div class="conductor-card-avatar">
                    <i class="ri-route-line" style="font-size:18px;"></i>
                </div>
                <div class="flex-grow-1">
                    <p class="fw-semibold mb-1" style="color:#0d2346; font-size:14px;">
                        ${r.origen} <i class="ri-arrow-right-line mx-1"></i> ${r.destino}
                    </p>
                    <div class="d-flex flex-wrap gap-1 mb-2">${diasBadges}</div>
                    <div class="d-flex gap-2">
                        <button class="btn-accion btn-accion-ver flex-fill"
                                onclick="verCronograma('${r.id_sede_origen}','${r.id_sede_destino}','${r.origen}','${r.destino}')">
                            <i class="ri-eye-line me-1"></i>Ver
                        </button>
                        <button class="btn-accion btn-accion-editar flex-fill"
                                onclick="editarCronograma('${r.id_sede_origen}','${r.id_sede_destino}','${r.origen}','${r.destino}')">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

// buscadores
if (buscadorCronograma) {
    buscadorCronograma.addEventListener('input', () => {
        const q = buscadorCronograma.value.toLowerCase();
        renderTablaCronograma(cronogramas.filter(r =>
            r.origen.toLowerCase().includes(q) ||
            r.destino.toLowerCase().includes(q) ||
            r.dias.some(d => d.toLowerCase().includes(q))
        ));
    });
}

if (buscadorMobileCronograma) {
    buscadorMobileCronograma.addEventListener('input', () => {
        const q = buscadorMobileCronograma.value.toLowerCase();
        renderTablaCronograma(cronogramas.filter(r =>
            r.origen.toLowerCase().includes(q) ||
            r.destino.toLowerCase().includes(q) ||
            r.dias.some(d => d.toLowerCase().includes(q))
        ));
        if (buscadorCronograma) buscadorCronograma.value = buscadorMobileCronograma.value;
    });
}

// MODAL — helpers compartidos para mostrar/ocultar secciones según el modo (crear, ver, editar)
function toggleSeccionesModal(modo) {
    const seccionCrear  = document.getElementById('seccionCrearCronograma');
    const seccionEditar = document.getElementById('seccionEditarCronograma');
    if (seccionCrear)  seccionCrear.style.display  = modo === 'crear'  ? '' : 'none';
    if (seccionEditar) seccionEditar.style.display = (modo === 'ver' || modo === 'editar') ? '' : 'none';
}

function resetearContenedorHoras() {
    const contenedor = document.getElementById('contenedorHorarios');
    if (!contenedor) return;
    contenedor.innerHTML = `
        <div class="input-group campo-input-group hora-item">
            <span class="input-group-text border-end-0">
                <i class="ri-time-line text-secondary"></i>
            </span>
            <input type="time" name="horas[]" class="form-control border-start-0"
                   min="06:00" max="18:00" required>
        </div>`;
}

function cargarSedes() {
    fetch('listar_sedes.php')
        .then(r => r.json())
        .then(sedes => {
            const opciones = sedes.map(s => `<option value="${s.id}">${s.nombre}</option>`).join('');
            ['id_sede_origen','id_sede_destino'].forEach(id => {
                const sel = document.getElementById(id);
                if (!sel) return;
                const placeholder = id === 'id_sede_origen'
                    ? '<option value="">Seleccionar origen</option>'
                    : '<option value="">Seleccionar destino</option>';
                sel.innerHTML = placeholder + opciones;
            });
        })
        .catch(() => {});
}

// MODAL CREAR
if (btnAbrirCronograma) {
    btnAbrirCronograma.addEventListener('click', () => {
        modoModalCronograma = 'crear';
        rutaEditando = null;

        document.getElementById('modalTitleCronograma').textContent = 'Nuevo Cronograma';
        btnGuardarCronograma.style.display = 'inline-flex';
        btnGuardarCronograma.innerHTML = '<i class="ri-save-line me-1"></i>Guardar cronograma';

        toggleSeccionesModal('crear');
        document.getElementById('formCronograma')?.reset();
        resetearContenedorHoras();
        cargarSedes();
        ocultarAlerta(alertaModalCronograma);
        if (modalCronogramaBS) modalCronogramaBS.show();
    });
}

if (elModalCronograma) {
    elModalCronograma.addEventListener('hidden.bs.modal', () => {
        modoModalCronograma = 'crear';
        rutaEditando = null;
        ocultarAlerta(alertaModalCronograma);
        toggleSeccionesModal('crear');
        resetearContenedorHoras();
    });
}

// agregar campo de hora extra en el form crear
const btnAgregarHora = document.getElementById('btnAgregarHora');
if (btnAgregarHora) {
    btnAgregarHora.addEventListener('click', () => {
        const contenedor = document.getElementById('contenedorHorarios');
        if (!contenedor) return;
        const div = document.createElement('div');
        div.className = 'input-group campo-input-group hora-item d-flex align-items-center gap-2';
        div.innerHTML = `
            <span class="input-group-text border-end-0">
                <i class="ri-time-line text-secondary"></i>
            </span>
            <input type="time" name="horas[]" class="form-control border-start-0"
                   min="06:00" max="18:00" required>
            <button type="button" class="btn-quitar-hora" onclick="this.closest('.hora-item').remove()">
                <i class="ri-close-line"></i>
            </button>`;
        contenedor.appendChild(div);
    });
}

// guardar cronograma (crear)
if (btnGuardarCronograma) {
    btnGuardarCronograma.addEventListener('click', () => {
        if (modoModalCronograma !== 'crear') return;

        ocultarAlerta(alertaModalCronograma);

        const origen  = document.getElementById('id_sede_origen')?.value;
        const destino = document.getElementById('id_sede_destino')?.value;
        const dias    = [...document.querySelectorAll('input[name="dias[]"]:checked')].map(c => c.value);
        const horas   = [...document.querySelectorAll('input[name="horas[]"]')]
                            .map(i => i.value.trim()).filter(Boolean);

        if (!origen)           { mostrarAlerta(alertaModalCronograma, 'error', 'Selecciona la sede de origen.'); return; }
        if (!destino)          { mostrarAlerta(alertaModalCronograma, 'error', 'Selecciona la sede de destino.'); return; }
        if (origen === destino){ mostrarAlerta(alertaModalCronograma, 'error', 'El origen y destino no pueden ser iguales.'); return; }
        if (!dias.length)      { mostrarAlerta(alertaModalCronograma, 'error', 'Selecciona al menos un día.'); return; }
        if (!horas.length)     { mostrarAlerta(alertaModalCronograma, 'error', 'Agrega al menos un horario de salida.'); return; }

        for (const h of horas) {
            if (calcularTurno(h) === null) {
                mostrarAlerta(alertaModalCronograma, 'error', `La hora ${h} está fuera del rango permitido (06:00 AM – 18:00 PM) en formato 24h.`);
                return;
            }
        }

        btnGuardarCronograma.disabled  = true;
        btnGuardarCronograma.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i>Guardando...';

        // Una sola petición con todos los días y horas — crear_cronograma.php los itera
        const fd = new FormData();
        fd.append('id_sede_origen',  origen);
        fd.append('id_sede_destino', destino);
        dias.forEach(d  => fd.append('dias[]',  d));
        horas.forEach(h => fd.append('horas[]', h));

        fetch('crear_cronograma.php', { method: 'POST', body: fd })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.text();
            })
            .then(texto => {
                try { return JSON.parse(texto); }
                catch(e) { console.error('Respuesta no-JSON:', texto); throw new Error('Respuesta inválida del servidor.'); }
            })
            .then(resp => {
                if (!resp.success) {
                    mostrarAlerta(alertaModalCronograma, 'error', resp.message);
                    return;
                }
                if (modalCronogramaBS) modalCronogramaBS.hide();
                mostrarAlertaGlobal('exito', resp.message);
                cargarCronogramas();
            })
            .catch(err => mostrarAlerta(alertaModalCronograma, 'error', err.message || 'Error de conexión.'))
            .finally(() => {
                btnGuardarCronograma.disabled  = false;
                btnGuardarCronograma.innerHTML = '<i class="ri-save-line me-1"></i>Guardar cronograma';
            });
    });
}

// MODAL VER 
function verCronograma(origen, destino, nombreOrigen, nombreDestino) {
    modoModalCronograma = 'ver';
    rutaEditando = { id_sede_origen: origen, id_sede_destino: destino };

    document.getElementById('modalTitleCronograma').textContent = `${nombreOrigen} → ${nombreDestino}`;
    btnGuardarCronograma.style.display = 'none';

    toggleSeccionesModal('ver');
    ocultarAlerta(alertaModalCronograma);

    const acordeon = document.getElementById('acordeonDias');
    if (acordeon) {
        acordeon.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="ri-loader-4-line ri-spin me-1"></i> Cargando horarios...
            </div>`;
    }

    if (modalCronogramaBS) modalCronogramaBS.show();

    fetch(`obtener_horarios_ruta.php?origen=${origen}&destino=${destino}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                mostrarAlerta(alertaModalCronograma, 'error', data.message || 'Error al cargar horarios.');
                return;
            }
            renderAcordeonDias(data.dias, origen, destino, false);
        })
        .catch(() => mostrarAlerta(alertaModalCronograma, 'error', 'Error de conexión.'));
}

// MODAL EDITAR — con controles para agregar/eliminar horas
function editarCronograma(origen, destino, nombreOrigen, nombreDestino) {
    modoModalCronograma = 'editar';
    rutaEditando = { id_sede_origen: origen, id_sede_destino: destino };

    document.getElementById('modalTitleCronograma').textContent = `Editar: ${nombreOrigen} → ${nombreDestino}`;
    btnGuardarCronograma.style.display = 'none';

    toggleSeccionesModal('editar');
    ocultarAlerta(alertaModalCronograma);

    const acordeon = document.getElementById('acordeonDias');
    if (acordeon) {
        acordeon.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="ri-loader-4-line ri-spin me-1"></i> Cargando horarios...
            </div>`;
    }

    if (modalCronogramaBS) modalCronogramaBS.show();

    fetch(`obtener_horarios_ruta.php?origen=${origen}&destino=${destino}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                mostrarAlerta(alertaModalCronograma, 'error', data.message || 'Error al cargar horarios.');
                return;
            }
            renderAcordeonDias(data.dias, origen, destino, true); 
        })
        .catch(() => mostrarAlerta(alertaModalCronograma, 'error', 'Error de conexión.'));
}

// RENDER DEL ACORDEÓN — recibe flag `editable`
function renderAcordeonDias(diasData, origen, destino, editable) {
    const acordeon = document.getElementById('acordeonDias');
    if (!acordeon) return;

    const diasPresentes = ORDEN_DIAS.filter(d => diasData[d] && diasData[d].length > 0);

    if (!diasPresentes.length) {
        acordeon.innerHTML = `<p class="text-muted text-center p-3">No hay horarios registrados para esta ruta.</p>`;
        return;
    }
    acordeon.innerHTML = diasPresentes.map((dia) => {
        const horarios    = diasData[dia] || [];
        const matutinos   = horarios.filter(h => h.turno === 'Matutino');
        const vespertinos = horarios.filter(h => h.turno === 'Vespertino');
        const sid = diaId(dia);

        const itemsMatutino = matutinos.map(h => itemHora(h, editable)).join('') ||
            `<span class="text-muted" style="font-size:12px;">Sin horarios matutinos</span>`;
        const itemsVespertino = vespertinos.map(h => itemHora(h, editable)).join('') ||
            `<span class="text-muted" style="font-size:12px;">Sin horarios vespertinos</span>`;

        const controlesAgregar = editable ? `
            <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top">
                <div class="input-group campo-input-group" style="max-width:160px;">
                    <span class="input-group-text border-end-0">
                        <i class="ri-time-line text-secondary"></i>
                    </span>
                    <input type="time" class="form-control border-start-0"
                           id="nuevaHora-${sid}" min="06:00" max="18:00">
                </div>
                <button class="btn-agregar-hora-dia"
                        onclick="agregarHoraADia('${dia}','${sid}','${origen}','${destino}')">
                    <i class="ri-add-line me-1"></i>Agregar hora
                </button>
            </div>` : '';

        return `
        <div class="accordion-item border-0 mb-2">
            <h2 class="accordion-header" id="head-${sid}">
                <button class="acordeon-dia-btn acordeon-dia-btn--collapsed" type="button"
                        data-sid="${sid}">
                    <i class="ri-calendar-event-line me-2" style="color:#f5c518;"></i>
                    <span class="fw-semibold">${dia}</span>
                    <span class="ms-2 badge-dia-count">${horarios.length} horario${horarios.length !== 1 ? 's' : ''}</span>
                    <i class="ri-arrow-down-s-line ms-auto acordeon-flecha"></i>
                </button>
            </h2>
            <div id="body-${sid}" class="acordeon-cuerpo" style="display:none;">
                <div class="accordion-body pt-2 pb-3">
                    <div class="row g-3">
                        <div class="col-6">
                            <p class="turno-label turno-matutino mb-2">
                                <i class="ri-haze-line me-1"></i>Mañana
                            </p>
                            <div class="horas-lista" id="lista-matutino-${sid}">
                                ${itemsMatutino}
                            </div>
                        </div>
                        <div class="col-6">
                            <p class="turno-label turno-vespertino mb-2">
                                <i class="ri-sun-line me-1"></i>Tarde
                            </p>
                            <div class="horas-lista" id="lista-vespertino-${sid}">
                                ${itemsVespertino}
                            </div>
                        </div>
                    </div>
                    ${controlesAgregar}
                </div>
            </div>
        </div>`;
    }).join('');

    // Inicializar acordeón manualmente — abrir el primer día con animación suave
    const items = acordeon.querySelectorAll('.acordeon-dia-btn');
    items.forEach((btn, idx) => {
        const sid    = btn.dataset.sid;
        const cuerpo = document.getElementById(`body-${sid}`);
        if (!cuerpo) return;

        // Preparar el cuerpo para animación con max-height
        cuerpo.style.maxHeight  = '0';
        cuerpo.style.display    = '';   // siempre visible en el DOM
        cuerpo.style.overflow   = 'hidden';
        cuerpo.style.transition = 'max-height 0.3s ease, opacity 0.25s ease';
        cuerpo.style.opacity    = '0';

        if (idx === 0) {
            // primer día: abierto sin animación
            cuerpo.style.maxHeight = cuerpo.scrollHeight + 'px';
            cuerpo.style.opacity   = '1';
            btn.classList.remove('acordeon-dia-btn--collapsed');
        }

        btn.addEventListener('click', () => {
            const abierto = cuerpo.style.maxHeight !== '0px' && cuerpo.style.maxHeight !== '0';
            if (abierto) {
                cuerpo.style.maxHeight = '0';
                cuerpo.style.opacity   = '0';
                btn.classList.add('acordeon-dia-btn--collapsed');
            } else {
                cuerpo.style.maxHeight = cuerpo.scrollHeight + 'px';
                cuerpo.style.opacity   = '1';
                btn.classList.remove('acordeon-dia-btn--collapsed');
            }
        });
    });

    // sección para añadir un día nuevo — solo en modo editar
    if (editable) {
        const diasFaltantes = ORDEN_DIAS.filter(d => !diasPresentes.includes(d));
        if (diasFaltantes.length) {
            acordeon.insertAdjacentHTML('beforeend', seccionNuevoDia(diasFaltantes, origen, destino));
        }
    }
}

// chip de hora — con o sin botón eliminar según `editable`
function itemHora(h, editable) {
    const btnEliminar = editable
        ? `<button class="btn-quitar-hora-chip" onclick="eliminarHoraDia(${h.id})" title="Eliminar">
               <i class="ri-close-line"></i>
           </button>`
        : '';
    return `
    <div class="hora-chip" id="chip-${h.id}">
        <i class="ri-time-line me-1 text-muted" style="font-size:11px;"></i>
        <span>${formatHora(h.hora_salida)}</span>
        ${btnEliminar}
    </div>`;
}

// sección para agregar un día completamente nuevo a la ruta
function seccionNuevoDia(diasFaltantes, origen, destino) {
    const opciones = diasFaltantes.map(d => `<option value="${d}">${d}</option>`).join('');
    return `
    <div class="nuevo-dia-seccion mt-3 p-3">
        <p class="fw-medium mb-2" style="font-size:13px; color:#0d2346;">
            <i class="ri-calendar-add-line me-1" style="color:#f5c518;"></i>Agregar nuevo día a esta ruta
        </p>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <select id="selectNuevoDia" class="form-select" style="max-width:150px; font-size:13px;">
                <option value="">Seleccionar día</option>
                ${opciones}
            </select>
            <div class="input-group campo-input-group" style="max-width:160px;">
                <span class="input-group-text border-end-0">
                    <i class="ri-time-line text-secondary"></i>
                </span>
                <input type="time" id="horaSelectNuevoDia" class="form-control border-start-0"
                       min="06:00" max="18:00">
            </div>
            <button class="btn-agregar-hora-dia" onclick="agregarNuevoDia('${origen}','${destino}')">
                <i class="ri-add-line me-1"></i>Agregar día
            </button>
        </div>
    </div>`;
}
// ACCIONES DE EDICIÓN
// agregar hora a un día ya existente
// `sid` = diaId(dia) para getElementById, `dia` = nombre real para el POST
function agregarHoraADia(dia, sid, origen, destino) {
    const input = document.getElementById(`nuevaHora-${sid}`);
    if (!input) return;
    const hora = input.value.trim();

    if (!hora) { mostrarAlerta(alertaModalCronograma, 'error', 'Ingresa una hora antes de agregar.'); return; }
    if (calcularTurno(hora) === null) {
        mostrarAlerta(alertaModalCronograma, 'error', `La hora ${hora} está fuera del rango permitido (06:00 AM – 18:00 PM) en formato 24h.`);
        return;
    }

    const fd = new FormData();
    fd.append('accion',          'agregar');
    fd.append('id_sede_origen',  origen);
    fd.append('id_sede_destino', destino);
    fd.append('dia_semana',      dia);
    fd.append('hora_salida',     hora);

    fetch('editar_cronograma.php', { method: 'POST', body: fd })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.text(); // texto primero para detectar respuestas HTML
        })
        .then(texto => {
            try { return JSON.parse(texto); }
            catch(e) { console.error('Respuesta no-JSON del servidor:', texto); throw new Error('Respuesta inválida del servidor. Revisa la consola.'); }
        })
        .then(resp => {
            if (!resp.success) { mostrarAlerta(alertaModalCronograma, 'error', resp.message); return; }

            const turno   = resp.turno;
            const listaId = turno === 'Matutino'
                ? `lista-matutino-${sid}`
                : `lista-vespertino-${sid}`;
            const lista = document.getElementById(listaId);
            if (lista) {
                const vacio = lista.querySelector('span.text-muted');
                if (vacio) vacio.remove();
                lista.insertAdjacentHTML('beforeend', itemHora({
                    id: resp.horario_id,
                    hora_salida: resp.hora_salida,
                    turno: resp.turno
                }, true));
                ordenarChips(lista);
            }

            actualizarContadorDia(sid);
            input.value = '';
            ocultarAlerta(alertaModalCronograma);
            mostrarAlertaGlobal('exito', resp.message);
            cargarCronogramas();
        })
        .catch(() => mostrarAlerta(alertaModalCronograma, 'error', 'Error de conexión.'));
}

// agregar un día nuevo a la ruta
function agregarNuevoDia(origen, destino) {
    const diaSelect = document.getElementById('selectNuevoDia');
    const horaInput = document.getElementById('horaSelectNuevoDia');
    if (!diaSelect || !horaInput) return;

    const dia  = diaSelect.value;
    const hora = horaInput.value.trim();

    if (!dia)  { mostrarAlerta(alertaModalCronograma, 'error', 'Selecciona el día.'); return; }
    if (!hora) { mostrarAlerta(alertaModalCronograma, 'error', 'Ingresa la hora de salida.'); return; }
    if (calcularTurno(hora) === null) {
        mostrarAlerta(alertaModalCronograma, 'error', `La hora ${hora} está fuera del rango permitido (06:00 AM – 18:00 PM) en formato 24h.`);
        return;
    }

    const fd = new FormData();
    fd.append('accion',          'agregar');
    fd.append('id_sede_origen',  origen);
    fd.append('id_sede_destino', destino);
    fd.append('dia_semana',      dia);
    fd.append('hora_salida',     hora);

    fetch('editar_cronograma.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (!resp.success) { mostrarAlerta(alertaModalCronograma, 'error', resp.message); return; }
            mostrarAlertaGlobal('exito', resp.message);
            cargarCronogramas();
            // recargar acordeón para mostrar el nuevo día
            fetch(`obtener_horarios_ruta.php?origen=${origen}&destino=${destino}`)
                .then(r => r.json())
                .then(data => { if (data.success) renderAcordeonDias(data.dias, origen, destino, true); });
        })
        .catch(() => mostrarAlerta(alertaModalCronograma, 'error', 'Error de conexión.'));
}

// eliminar una hora por su ID
function eliminarHoraDia(horarioId) {
    Swal.fire({
        title: '¿Eliminar esta hora?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        heightAuto: false,
        customClass: { container: 'swal-sobre-modal' },
    }).then(result => {
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append('accion',     'eliminar');
        fd.append('horario_id', horarioId);

        fetch('editar_cronograma.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(resp => {
                if (!resp.success) { mostrarAlertaGlobal('error', resp.message); return; }

                const chip = document.getElementById(`chip-${horarioId}`);
                if (chip) {
                    const lista = chip.closest('.horas-lista');
                    chip.remove();
                    if (lista && !lista.querySelector('.hora-chip')) {
                        const turno = lista.id.includes('matutino') ? 'matutinos' : 'vespertinos';
                        lista.innerHTML = `<span class="text-muted" style="font-size:12px;">Sin horarios ${turno}</span>`;
                    }
                }

                // actualizar contadores de todos los días visibles
                document.querySelectorAll('.acordeon-dia-btn .fw-semibold').forEach(el => {
                    actualizarContadorDia(diaId(el.textContent.trim()));
                });

                mostrarAlertaGlobal('exito', resp.message);
                cargarCronogramas();
            })
            .catch(() => mostrarAlertaGlobal('error', 'Error de conexión.'));
    });
}

// ── utilidades del acordeón ───────────────────────────────────
function actualizarContadorDia(sid) {
    const matutinos   = document.querySelectorAll(`#lista-matutino-${sid} .hora-chip`).length;
    const vespertinos = document.querySelectorAll(`#lista-vespertino-${sid} .hora-chip`).length;
    const total = matutinos + vespertinos;
    const contador = document.querySelector(`#head-${sid} .badge-dia-count`);
    if (contador) contador.textContent = `${total} horario${total !== 1 ? 's' : ''}`;
}

function ordenarChips(lista) {
    const chips = [...lista.querySelectorAll('.hora-chip')];
    chips.sort((a, b) => {
        const horaA = a.querySelector('span')?.textContent || '';
        const horaB = b.querySelector('span')?.textContent || '';
        return horaA.localeCompare(horaB);
    });
    chips.forEach(c => lista.appendChild(c));
}

// ══════════════════════════════════════════════════════════════
// PARA LA SECCIÓN DE UNIDADES
// ══════════════════════════════════════════════════════════════
const elModalUnidad              = document.getElementById('modalUnidad');
const modalUnidadBS              = elModalUnidad ? new bootstrap.Modal(elModalUnidad) : null;
const btnAbrirUnidad             = document.getElementById('btnAbrirModalUnidad');
const btnGuardarUnidad           = document.getElementById('btnGuardarUnidad');
const formUnidad                 = document.getElementById('formUnidad');
const alertaModalUnidad          = document.getElementById('alertaModalUnidad');
const cuerpoTablaUnidades        = document.getElementById('cuerpoTablaUnidades');
const contenedorTarjetasUnidades = document.getElementById('contenedorTarjetasUnidades');
const buscadorDesktopUnidades    = document.getElementById('buscadorUnidades');
const buscadorMobileUnidades     = document.getElementById('buscadorMobileUnidades');
 
let unidades     = [];
let modoUnidad   = 'crear';
let unidadEditId = null;
 
if (cuerpoTablaUnidades) {
    document.addEventListener('DOMContentLoaded', cargarUnidades);
 
    // abre el modal limpio para agregar una unidad
    if (btnAbrirUnidad) {
        btnAbrirUnidad.addEventListener('click', () => {
            modoUnidad   = 'crear';
            unidadEditId = null;
 
            document.getElementById('modalTitleUnidad').textContent = 'Nueva Unidad';
            btnGuardarUnidad.innerHTML = '<i class="ri-save-line me-1"></i>Guardar unidad';
 
            if (formUnidad) formUnidad.reset();
            ocultarAlerta(alertaModalUnidad);
            if (modalUnidadBS) modalUnidadBS.show();
        });
    }
 
    // limpia el form y resetea el modo cuando se cierra el modal
    if (elModalUnidad) {
        elModalUnidad.addEventListener('hidden.bs.modal', () => {
            modoUnidad   = 'crear';
            unidadEditId = null;
 
            document.getElementById('modalTitleUnidad').textContent = 'Nueva Unidad';
            btnGuardarUnidad.innerHTML = '<i class="ri-save-line me-1"></i>Guardar unidad';
 
            if (formUnidad) formUnidad.reset();
            ocultarAlerta(alertaModalUnidad);
        });
    }
}
 
// carga las unidades al entrar a la página
function cargarUnidades() {
    if (!cuerpoTablaUnidades) return;
    cuerpoTablaUnidades.innerHTML = `<tr><td colspan="5" class="tabla-empty"><i class="ri-loader-4-line ri-spin"></i> Cargando unidades...</td></tr>`;
 
    fetch('listar_unidades.php')
        .then(r => r.json())
        .then(data => { unidades = data; renderTablaUnidades(unidades); })
        .catch(() => {
            cuerpoTablaUnidades.innerHTML = `<tr><td colspan="5" class="tabla-empty"><i class="ri-error-warning-line"></i> Error al cargar los datos.</td></tr>`;
        });
}
 
// carga la tabla de unidades en desktop
function renderTablaUnidades(lista) {
    if (!cuerpoTablaUnidades) return;
 
    const activos   = lista.filter(u => parseInt(u.estado) === 1);
    const inactivos = lista.filter(u => parseInt(u.estado) === 0);
    lista = [...activos, ...inactivos];
 
    if (!lista.length) {
        cuerpoTablaUnidades.innerHTML = `<tr><td colspan="5" class="tabla-empty"><i class="ri-bus-line"></i> No hay unidades registradas.</td></tr>`;
        renderTarjetasUnidades([]);
        return;
    }
 
    cuerpoTablaUnidades.innerHTML = lista.map((u, i) => {
        const esInactivo = parseInt(u.estado) === 0;
        return `
        <tr class="animate__animated animate__fadeIn ${esInactivo ? 'fila-inactiva' : ''}">
            <td>${i + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="conductor-tabla-avatar"><i class="ri-bus-line" style="font-size:13px;"></i></div>
                    <span>${u.nombre}</span>
                </div>
            </td>
            <td><code>${u.placa}</code></td>
            <td><i class="ri-group-line me-1 text-muted"></i>${u.capacidad_maxima} pasajeros</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    ${!esInactivo ? `
                        <button class="btn-accion btn-accion-editar" onclick="editarUnidad(${u.id})">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    ` : ''}
                    <button class="btn-accion ${!esInactivo ? 'btn-accion-activo' : 'btn-accion-inactivo'}"
                            onclick="cambiarEstadoUnidad(${u.id}, ${u.estado})">
                        ${!esInactivo ? 'Activo' : 'Inactivo'}
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
 
    renderTarjetasUnidades(lista);
}
 
// carga las tarjetas de unidades en móvil
function renderTarjetasUnidades(lista) {
    if (!contenedorTarjetasUnidades) return;
    if (!lista.length) {
        contenedorTarjetasUnidades.innerHTML = `<div class="text-center p-4 text-muted"><i class="ri-bus-line d-block mb-2" style="font-size:2rem;"></i>No hay unidades registradas.</div>`;
        return;
    }
 
    contenedorTarjetasUnidades.innerHTML = lista.map(u => {
        const esInactivo = parseInt(u.estado) === 0;
        return `
        <div class="conductor-card-mobile animate__animated animate__fadeIn ${esInactivo ? 'fila-inactiva' : ''}">
            <div class="d-flex align-items-center gap-3">
                <div class="conductor-card-avatar"><i class="ri-bus-line"></i></div>
                <div class="flex-grow-1">
                    <p class="fw-semibold mb-0" style="color:#0d2346; font-size:14px;">${u.nombre}</p>
                    <p class="text-muted mb-0" style="font-size:12px;"><i class="ri-car-line me-1"></i><code>${u.placa}</code></p>
                    <p class="text-muted mb-0" style="font-size:12px;"><i class="ri-group-line me-1"></i>${u.capacidad_maxima} pasajeros</p>
                </div>
                <div class="d-flex flex-column gap-1">
                    ${!esInactivo ? `
                        <button class="btn-accion btn-accion-editar" onclick="editarUnidad(${u.id})">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    ` : ''}
                    <button class="btn-accion ${!esInactivo ? 'btn-accion-activo' : 'btn-accion-inactivo'}"
                            onclick="cambiarEstadoUnidad(${u.id}, ${u.estado})">
                        ${!esInactivo ? 'Activo' : 'Inactivo'}
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}
 
// buscador desktop unidades
if (buscadorDesktopUnidades) {
    buscadorDesktopUnidades.addEventListener('input', () => {
        const q = buscadorDesktopUnidades.value.toLowerCase();
        renderTablaUnidades(unidades.filter(u =>
            u.nombre.toLowerCase().includes(q) ||
            u.placa.toLowerCase().includes(q)  ||
            String(u.capacidad_maxima).includes(q)
        ));
    });
}
 
// buscador móvil unidades — sincronizado con el de desktop
if (buscadorMobileUnidades) {
    buscadorMobileUnidades.addEventListener('input', () => {
        const q = buscadorMobileUnidades.value.toLowerCase();
        renderTablaUnidades(unidades.filter(u =>
            u.nombre.toLowerCase().includes(q) ||
            u.placa.toLowerCase().includes(q)  ||
            String(u.capacidad_maxima).includes(q)
        ));
        if (buscadorDesktopUnidades) buscadorDesktopUnidades.value = buscadorMobileUnidades.value;
    });
}
 
// guarda la unidad — detecta si es crear o editar y llama al archivo correcto
if (btnGuardarUnidad && formUnidad) {
    btnGuardarUnidad.addEventListener('click', () => {
        ocultarAlerta(alertaModalUnidad);
        if (!formUnidad.checkValidity()) { formUnidad.reportValidity(); return; }
 
        btnGuardarUnidad.disabled  = true;
        btnGuardarUnidad.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i>Guardando...';
 
        const datos = new FormData(formUnidad);
        let archivo = 'crear_unidad.php';
 
        if (modoUnidad === 'editar') {
            datos.append('unidad_id', unidadEditId);
            archivo = 'editar_unidad.php';
        }
 
        fetch(archivo, { method: 'POST', body: datos })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    if (modalUnidadBS) modalUnidadBS.hide();
                    mostrarAlertaGlobal('exito', resp.message);
                    cargarUnidades();
                } else {
                    mostrarAlerta(alertaModalUnidad, 'error', resp.message);
                }
            })
            .catch(() => mostrarAlerta(alertaModalUnidad, 'error', 'Error de conexión. Intenta de nuevo.'))
            .finally(() => {
                btnGuardarUnidad.disabled  = false;
                btnGuardarUnidad.innerHTML = modoUnidad === 'editar'
                    ? '<i class="ri-save-line me-1"></i>Guardar cambios'
                    : '<i class="ri-save-line me-1"></i>Guardar unidad';
            });
    });
}
 
// llama a cambiarEstado con los textos específicos para unidades
function cambiarEstadoUnidad(id, estadoActual) {
    cambiarEstado(
        id,
        estadoActual,
        'cambiar_estado_unidad.php',
        'unidad',
        cargarUnidades,
        {
            desactivar: 'Esta unidad no estará disponible para asignaciones.',
            activar:    'Esta unidad volverá a estar disponible para asignaciones.'
        }
    );
}
 
// abre el modal en modo EDITAR con los datos de la unidad precargados
function editarUnidad(id) {
    const u = unidades.find(u => parseInt(u.id) === parseInt(id));
    if (!u) {
        mostrarAlertaGlobal('error', 'No se encontraron los datos de la unidad.');
        return;
    }
 
    modoUnidad   = 'editar';
    unidadEditId = id;
 
    // cambiar título y botón
    document.getElementById('modalTitleUnidad').textContent = 'Editar Unidad';
    btnGuardarUnidad.innerHTML = '<i class="ri-save-line me-1"></i>Guardar cambios';
 
    // pre-llenar los campos con los datos actuales
    document.getElementById('nombreUnidad').value    = u.nombre;
    document.getElementById('placaUnidad').value     = u.placa;
    document.getElementById('capacidadUnidad').value = u.capacidad_maxima;
 
    ocultarAlerta(alertaModalUnidad);
    if (modalUnidadBS) modalUnidadBS.show();
}