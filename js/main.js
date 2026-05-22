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
            <td><i class="ri-id-card-line me-1 text-muted"></i><code>${c.codigo_universitario}</code></td>
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
                    <p class="text-muted mb-0" style="font-size:12px;"><i class="ri-id-card-line me-1"></i><code>${c.codigo_universitario}</code></p>
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
            c.codigo_universitario.toLowerCase().includes(q) ||
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
            c.codigo_universitario.toLowerCase().includes(q) ||
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
    document.getElementById('codigo_universitario').value = c.codigo_universitario;

    document.getElementById('password').value             = '';
    document.getElementById('confirmar_password').value   = '';
    desactivarRequiredPassword();
    setHintPassword('Déjala en blanco para no cambiarla.');

    ocultarAlerta(alertaModal);
    if (modalBS) modalBS.show();
}

// ══════════════════════════════════════════════════════════════
// PARA LA SECCIÓN DE HORARIOS
// ══════════════════════════════════════════════════════════════
const modalHorarioElem           = document.getElementById('modalHorario');
const modalHorarioBS             = modalHorarioElem ? new bootstrap.Modal(modalHorarioElem) : null;
const btnAbrirHor                = document.getElementById('btnAbrirModalHorario');
const btnGuardarHor              = document.getElementById('btnGuardarHorario');
const formHor                    = document.getElementById('formHorario');
const cuerpoTablaHor             = document.getElementById('cuerpoTablaHorarios');
const contenedorTarjetasHorarios = document.getElementById('contenedorTarjetasHorarios');
const buscadorMobileHor          = document.getElementById('buscadorMobileHorarios');
const buscadorDesktopHor         = document.getElementById('buscadorHorarios');

let horarios      = [];
let modoHorario   = 'crear';
let horarioEditId = null;

if (cuerpoTablaHor) {
    document.addEventListener('DOMContentLoaded', cargarHorarios);

    // abre el modal limpio para agregar horario
    if (btnAbrirHor) {
        btnAbrirHor.addEventListener('click', () => {
            modoHorario   = 'crear';
            horarioEditId = null;

            const tituloModal = modalHorarioElem?.querySelector('#modalTitle');
            if (tituloModal) tituloModal.textContent = 'Nuevo Horario';
            if (btnGuardarHor) btnGuardarHor.innerHTML = '<i class="ri-save-line me-1"></i>Guardar horario';

            if (formHor) formHor.reset();
            ocultarAlerta(alertaModal);
            if (modalHorarioBS) modalHorarioBS.show();
        });
    }

    // limpia el form al cerrar y resetea el modo
    if (modalHorarioElem) {
        modalHorarioElem.addEventListener('hidden.bs.modal', () => {
            modoHorario   = 'crear';
            horarioEditId = null;

            const tituloModal = modalHorarioElem?.querySelector('#modalTitle');
            if (tituloModal) tituloModal.textContent = 'Nuevo Horario';
            if (btnGuardarHor) btnGuardarHor.innerHTML = '<i class="ri-save-line me-1"></i>Guardar horario';

            if (formHor) formHor.reset();
            ocultarAlerta(alertaModal);
        });
    }
}

// carga los horarios al entrar
function cargarHorarios() {
    if (!cuerpoTablaHor) return;
    cuerpoTablaHor.innerHTML = `<tr><td colspan="3" class="tabla-empty"><i class="ri-loader-4-line ri-spin"></i> Cargando horarios...</td></tr>`;

    fetch('listar_horarios.php')
        .then(r => r.json())
        .then(data => { horarios = data; renderTablaHorarios(horarios); })
        .catch(() => {
            cuerpoTablaHor.innerHTML = `<tr><td colspan="3" class="tabla-empty"><i class="ri-error-warning-line"></i> Error al cargar los datos.</td></tr>`;
        });
}

// carga la tabla de horarios en desktop
function renderTablaHorarios(lista) {
    if (!cuerpoTablaHor) return;

    const activos   = lista.filter(h => parseInt(h.estado) === 1);
    const inactivos = lista.filter(h => parseInt(h.estado) === 0);
    lista = [...activos, ...inactivos];

    if (!lista.length) {
        cuerpoTablaHor.innerHTML = `<tr><td colspan="3" class="tabla-empty"><i class="ri-time-line"></i> No hay horarios registrados.</td></tr>`;
        renderTarjetasHorarios([]);
        return;
    }

    cuerpoTablaHor.innerHTML = lista.map(h => {
        const esInactivo = parseInt(h.estado) === 0;
        const turnoBadge = h.turno === 'Matutino'
            ? `<span class="badge badge-matutino"><i class="ri-sun-cloudy-line me-1"></i>Matutino</span>`
            : h.turno === 'Vespertino'
            ? `<span class="badge badge-vespertino"><i class="ri-sun-line me-1"></i>Vespertino</span>`
            : `<span class="badge badge-fuera-rango">—</span>`;
        return `
        <tr class="animate__animated animate__fadeIn ${esInactivo ? 'fila-inactiva' : ''}">
            <td>${h.id}</td>
            <td><span class="hora-badge"><i class="ri-time-fill"></i>${h.hora_formateada}</span></td>
            <td>${turnoBadge}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    ${!esInactivo ? `
                        <button class="btn-accion btn-accion-editar" onclick="abrirModalEditarHorario(${h.id}, '${h.hora}')">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    ` : ''}
                    <button class="btn-accion ${!esInactivo ? 'btn-accion-activo' : 'btn-accion-inactivo'}"
                            onclick="cambiarEstadoHorario(${h.id}, ${h.estado})">
                        ${!esInactivo ? 'Activo' : 'Inactivo'}
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    renderTarjetasHorarios(lista);
}

// carga las tarjetas de horarios en móvil
function renderTarjetasHorarios(lista) {
    if (!contenedorTarjetasHorarios) return;
    if (!lista.length) {
        contenedorTarjetasHorarios.innerHTML = `<div class="text-center p-4 text-muted"><i class="ri-time-line d-block mb-2" style="font-size:2rem;"></i>No hay horarios registrados.</div>`;
        return;
    }

    contenedorTarjetasHorarios.innerHTML = lista.map(h => {
        const esInactivo = parseInt(h.estado) === 0;
        return `
        <div class="conductor-card-mobile animate__animated animate__fadeIn ${esInactivo ? 'fila-inactiva' : ''}">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="conductor-card-avatar"><i class="ri-time-fill"></i></div>
                    <div>
                        <p class="fw-semibold mb-0" style="color:#0d2346; font-size:15px;">${h.hora_formateada}</p>
                        <p class="text-muted mb-0" style="font-size:12px;">ID: ${h.id}</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${!esInactivo ? `
                        <button class="btn-accion btn-accion-editar" onclick="abrirModalEditarHorario(${h.id}, '${h.hora}')">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    ` : ''}
                    <button class="btn-accion ${!esInactivo ? 'btn-accion-activo' : 'btn-accion-inactivo'}"
                            onclick="cambiarEstadoHorario(${h.id}, ${h.estado})">
                        ${!esInactivo ? 'Activo' : 'Inactivo'}
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}

// buscador desktop horarios
if (buscadorDesktopHor) {
    buscadorDesktopHor.addEventListener('input', () => {
        const q = buscadorDesktopHor.value.toLowerCase();
        renderTablaHorarios(horarios.filter(h =>
            h.hora_formateada.toLowerCase().includes(q) || String(h.id).includes(q)
        ));
    });
}

// buscador móvil horarios — sincronizado con el de desktop
if (buscadorMobileHor) {
    buscadorMobileHor.addEventListener('input', () => {
        const q = buscadorMobileHor.value.toLowerCase();
        renderTablaHorarios(horarios.filter(h =>
            h.hora_formateada.toLowerCase().includes(q) || String(h.id).includes(q)
        ));
        if (buscadorDesktopHor) buscadorDesktopHor.value = buscadorMobileHor.value;
    });
}

// guarda el horario — detecta si es crear o editar y llama al archivo correcto
if (btnGuardarHor && formHor) {
    btnGuardarHor.addEventListener('click', () => {
        ocultarAlerta(alertaModal);
        if (!formHor.checkValidity()) { formHor.reportValidity(); return; }

        btnGuardarHor.disabled  = true;
        btnGuardarHor.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i>Guardando...';

        const datos = new FormData(formHor);
        let archivo = 'crear_horario.php';

        if (modoHorario === 'editar') {
            datos.append('horario_id', horarioEditId);
            archivo = 'editar_horario.php';
        }

        fetch(archivo, { method: 'POST', body: datos })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    if (modalHorarioBS) modalHorarioBS.hide();
                    mostrarAlertaGlobal('exito', resp.message);
                    cargarHorarios();
                } else {
                    mostrarAlerta(alertaModal, 'error', resp.message);
                }
            })
            .catch(() => mostrarAlerta(alertaModal, 'error', 'Error de conexión.'))
            .finally(() => {
                btnGuardarHor.disabled  = false;
                btnGuardarHor.innerHTML = modoHorario === 'editar'
                    ? '<i class="ri-save-line me-1"></i>Guardar cambios'
                    : '<i class="ri-save-line me-1"></i>Guardar horario';
            });
    });
}

// llama a cambiarEstado con los textos específicos para horarios
function cambiarEstadoHorario(id, estadoActual) {
    cambiarEstado(
        id,
        estadoActual,
        'cambiar_estado_horario.php',
        'horario',
        cargarHorarios,
        {
            desactivar: 'Este horario no estará disponible para las rutas.',
            activar:    'Este horario volverá a estar disponible para las rutas.'
        }
    );
}

// abre el modal en modo EDITAR con la hora actual precargada
function abrirModalEditarHorario(id, hora) {
    modoHorario   = 'editar';
    horarioEditId = id;

    // cambiar título y botón
    const tituloModal = modalHorarioElem?.querySelector('#modalTitle');
    if (tituloModal) tituloModal.textContent = 'Editar Horario';
    if (btnGuardarHor) btnGuardarHor.innerHTML = '<i class="ri-save-line me-1"></i>Guardar cambios';

    // pre-llenar el input con la hora actual (formato HH:MM para el input type="time")
    const inputHora = formHor?.querySelector('#hora_salida');
    if (inputHora) inputHora.value = hora.substring(0, 5); // recorta segundos si vienen HH:MM:SS

    ocultarAlerta(alertaModal);
    if (modalHorarioBS) modalHorarioBS.show();
}

// ══════════════════════════════════════════════════════════════
// PARA LA SECCIÓN DE RUTAS
// ══════════════════════════════════════════════════════════════
const elModalRuta             = document.getElementById('modalRuta');
const modalRutaBS             = elModalRuta ? new bootstrap.Modal(elModalRuta) : null;
const btnAbrirRuta            = document.getElementById('btnAbrirModalRuta');
const btnGuardarRuta          = document.getElementById('btnGuardarRuta');
const formRuta                = document.getElementById('formRuta');
const alertaModalRuta         = document.getElementById('alertaModalRuta');
const cuerpoTablaRutas        = document.getElementById('cuerpoTablaRuta');
const contenedorTarjetasRutas = document.getElementById('contenedorTarjetasRutas');
const buscadorDesktopRutas    = document.getElementById('buscadorRuta');
const buscadorMobileRutas     = document.getElementById('buscadorMobileRutas');
const selectOrigen            = document.getElementById('origen');
const selectDestino           = document.getElementById('destino');

let rutas      = [];
let modoRuta   = 'crear';
let rutaEditId = null;

if (cuerpoTablaRutas) {
    document.addEventListener('DOMContentLoaded', cargarRutas);

    // abre el modal limpio para agregar ruta
    if (btnAbrirRuta) {
        btnAbrirRuta.addEventListener('click', () => {
            modoRuta   = 'crear';
            rutaEditId = null;

            const tituloModal = elModalRuta?.querySelector('#modalTitle');
            if (tituloModal) tituloModal.textContent = 'Nueva Ruta';
            btnGuardarRuta.innerHTML = '<i class="ri-save-line me-1"></i>Guardar ruta';

            if (formRuta) formRuta.reset();
            cargarSedes();
            ocultarAlerta(alertaModalRuta);
            if (modalRutaBS) modalRutaBS.show();
        });
    }

    // limpia el form al cerrar y resetea el modo
    if (elModalRuta) {
        elModalRuta.addEventListener('hidden.bs.modal', () => {
            modoRuta   = 'crear';
            rutaEditId = null;

            const tituloModal = elModalRuta?.querySelector('#modalTitle');
            if (tituloModal) tituloModal.textContent = 'Nueva Ruta';
            btnGuardarRuta.innerHTML = '<i class="ri-save-line me-1"></i>Guardar ruta';

            if (formRuta) formRuta.reset();
            ocultarAlerta(alertaModalRuta);
        });
    }
}

// carga las sedes en ambos selects
function cargarSedes() {
    fetch('listar_sedes.php')
        .then(r => r.json())
        .then(sedes => {
            [selectOrigen, selectDestino].forEach(sel => {
                if (!sel) return;
                sel.innerHTML = '<option value="">Seleccionar sede</option>';
                sedes.forEach(s => {
                    sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
                });
            });
        })
        .catch(() => mostrarAlertaGlobal('error', 'No se pudieron cargar las sedes.'));
}

// carga las rutas al entrar
function cargarRutas() {
    if (!cuerpoTablaRutas) return;
    cuerpoTablaRutas.innerHTML = `<tr><td colspan="4" class="tabla-empty"><i class="ri-loader-4-line ri-spin"></i> Cargando rutas...</td></tr>`;

    fetch('listar_rutas.php')
        .then(r => r.json())
        .then(data => { rutas = data; renderTablaRutas(rutas); })
        .catch(() => {
            cuerpoTablaRutas.innerHTML = `<tr><td colspan="4" class="tabla-empty"><i class="ri-error-warning-line"></i> Error al cargar los datos.</td></tr>`;
        });
}

// carga la tabla de rutas en desktop
function renderTablaRutas(lista) {
    if (!cuerpoTablaRutas) return;

    const activos   = lista.filter(r => parseInt(r.estado) === 1);
    const inactivos = lista.filter(r => parseInt(r.estado) === 0);
    lista = [...activos, ...inactivos];

    if (!lista.length) {
        cuerpoTablaRutas.innerHTML = `<tr><td colspan="4" class="tabla-empty"><i class="ri-steering-2-line"></i> No hay rutas registradas.</td></tr>`;
        renderTarjetasRutas([]);
        return;
    }

    cuerpoTablaRutas.innerHTML = lista.map(r => {
        const esInactivo = parseInt(r.estado) === 0;
        return `
        <tr class="animate__animated animate__fadeIn ${esInactivo ? 'fila-inactiva' : ''}">
            <td>${r.id}</td>
            <td><i class="ri-map-pin-2-line me-1 text-muted"></i>${r.origen}</td>
            <td><i class="ri-map-pin-5-line me-1 text-muted"></i>${r.destino}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    ${!esInactivo ? `
                        <button class="btn-accion btn-accion-editar" onclick="editarRuta(${r.id})">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    ` : ''}
                    <button class="btn-accion ${!esInactivo ? 'btn-accion-activo' : 'btn-accion-inactivo'}"
                            onclick="cambiarEstadoRuta(${r.id}, ${r.estado})">
                        ${!esInactivo ? 'Activo' : 'Inactivo'}
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    renderTarjetasRutas(lista);
}

// carga las tarjetas de rutas en móvil
function renderTarjetasRutas(lista) {
    if (!contenedorTarjetasRutas) return;
    if (!lista.length) {
        contenedorTarjetasRutas.innerHTML = `<div class="text-center p-4 text-muted"><i class="ri-steering-2-line d-block mb-2" style="font-size:2rem;"></i>No hay rutas registradas.</div>`;
        return;
    }

    contenedorTarjetasRutas.innerHTML = lista.map(r => {
        const esInactivo = parseInt(r.estado) === 0;
        return `
        <div class="conductor-card-mobile animate__animated animate__fadeIn ${esInactivo ? 'fila-inactiva' : ''}">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="conductor-card-avatar"><i class="ri-steering-2-line"></i></div>
                    <div>
                        <p class="fw-semibold mb-0" style="color:#0d2346; font-size:14px;">
                            <i class="ri-map-pin-2-line me-1"></i>${r.origen}
                        </p>
                        <p class="text-muted mb-0" style="font-size:12px;">
                            <i class="ri-arrow-right-line me-1"></i>${r.destino}
                        </p>
                    </div>
                </div>
                <div class="d-flex flex-column gap-1">
                    ${!esInactivo ? `
                        <button class="btn-accion btn-accion-editar" onclick="editarRuta(${r.id})">
                            <i class="ri-edit-line me-1"></i>Editar
                        </button>
                    ` : ''}
                    <button class="btn-accion ${!esInactivo ? 'btn-accion-activo' : 'btn-accion-inactivo'}"
                            onclick="cambiarEstadoRuta(${r.id}, ${r.estado})">
                        ${!esInactivo ? 'Activo' : 'Inactivo'}
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}

// buscador desktop rutas
if (buscadorDesktopRutas) {
    buscadorDesktopRutas.addEventListener('input', () => {
        const q = buscadorDesktopRutas.value.toLowerCase();
        renderTablaRutas(rutas.filter(r =>
            r.origen.toLowerCase().includes(q)  ||
            r.destino.toLowerCase().includes(q) ||
            String(r.id).includes(q)
        ));
    });
}

// buscador móvil rutas — sincronizado con el de desktop
if (buscadorMobileRutas) {
    buscadorMobileRutas.addEventListener('input', () => {
        const q = buscadorMobileRutas.value.toLowerCase();
        renderTablaRutas(rutas.filter(r =>
            r.origen.toLowerCase().includes(q)  ||
            r.destino.toLowerCase().includes(q) ||
            String(r.id).includes(q)
        ));
        if (buscadorDesktopRutas) buscadorDesktopRutas.value = buscadorMobileRutas.value;
    });
}

// guarda la ruta — detecta si es crear o editar y llama al archivo correcto
if (btnGuardarRuta && formRuta) {
    btnGuardarRuta.addEventListener('click', () => {
        ocultarAlerta(alertaModalRuta);
        if (!formRuta.checkValidity()) { formRuta.reportValidity(); return; }

        // validación extra: origen y destino no pueden ser iguales
        if (selectOrigen?.value === selectDestino?.value) {
            mostrarAlerta(alertaModalRuta, 'error', 'El origen y el destino no pueden ser la misma sede.');
            return;
        }

        btnGuardarRuta.disabled  = true;
        btnGuardarRuta.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i>Guardando...';

        const datos = new FormData(formRuta);
        let archivo = 'crear_ruta.php';

        if (modoRuta === 'editar') {
            datos.append('ruta_id', rutaEditId);
            archivo = 'editar_ruta.php';
        }

        fetch(archivo, { method: 'POST', body: datos })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    if (modalRutaBS) modalRutaBS.hide();
                    mostrarAlertaGlobal('exito', resp.message);
                    cargarRutas();
                } else {
                    mostrarAlerta(alertaModalRuta, 'error', resp.message);
                }
            })
            .catch(() => mostrarAlerta(alertaModalRuta, 'error', 'Error de conexión. Intenta de nuevo.'))
            .finally(() => {
                btnGuardarRuta.disabled  = false;
                btnGuardarRuta.innerHTML = modoRuta === 'editar'
                    ? '<i class="ri-save-line me-1"></i>Guardar cambios'
                    : '<i class="ri-save-line me-1"></i>Guardar ruta';
            });
    });
}

// llama a cambiarEstado con los textos específicos para rutas
function cambiarEstadoRuta(id, estadoActual) {
    cambiarEstado(
        id,
        estadoActual,
        'cambiar_estado_ruta.php',
        'ruta',
        cargarRutas,
        {
            desactivar: 'Esta ruta no estará disponible en el cronograma.',
            activar:    'Esta ruta volverá a estar disponible en el cronograma.'
        }
    );
}

// abre el modal en modo EDITAR con los selects de origen y destino precargados
function editarRuta(id) {
    const r = rutas.find(r => parseInt(r.id) === parseInt(id));
    if (!r) {
        mostrarAlertaGlobal('error', 'No se encontraron los datos de la ruta.');
        return;
    }

    modoRuta   = 'editar';
    rutaEditId = id;

    // cambiar título y botón
    const tituloModal = elModalRuta?.querySelector('#modalTitle');
    if (tituloModal) tituloModal.textContent = 'Editar Ruta';
    btnGuardarRuta.innerHTML = '<i class="ri-save-line me-1"></i>Guardar cambios';

    // cargar las sedes primero y luego pre-seleccionar origen y destino
    fetch('listar_sedes.php')
        .then(res => res.json())
        .then(sedes => {
            [selectOrigen, selectDestino].forEach(sel => {
                if (!sel) return;
                sel.innerHTML = '<option value="">Seleccionar sede</option>';
                sedes.forEach(s => {
                    sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
                });
            });

            // pre-seleccionar los valores actuales de la ruta
            if (selectOrigen)  selectOrigen.value  = r.id_sede_origen;
            if (selectDestino) selectDestino.value = r.id_sede_destino;
        })
        .catch(() => mostrarAlertaGlobal('error', 'No se pudieron cargar las sedes.'));

    ocultarAlerta(alertaModalRuta);
    if (modalRutaBS) modalRutaBS.show();
}