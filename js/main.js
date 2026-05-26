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
// PARA LA SECCIÓN DE CRONOGRAMA DE HORARIOS
// ══════════════════════════════════════════════════════════════
const elModalCronograma  = document.getElementById('modalCronograma');
const modalCronogramaBS  = elModalCronograma ? new bootstrap.Modal(elModalCronograma) : null;
const btnAbrirCronograma = document.getElementById('btnAbrirModalCronograma');
const formCronograma     = document.getElementById('formCronograma');
const btnGuardarCronograma = document.getElementById('btnGuardarCronograma');

let cronogramas = [];
let modoModalCronograma = 'crear';
let cronogramaEditId = null;

if (btnAbrirCronograma) {
    btnAbrirCronograma.addEventListener('click', () => {
        modoModalCronograma = 'crear';
        cronogramaEditId = null;
        
        document.getElementById('modalTitleCronograma').textContent = 'Nuevo Cronograma';
        btnGuardarCronograma.innerHTML = '<i class="ri-save-line me-1"></i>Guardar cronograma';
        
        activarRequiredPassword();
        setHintPassword('');
        formCronograma.reset();
        ocultarAlerta(alertaModal);
        if (modalCronogramaBS) modalCronogramaBS.show();
    });
}

// Limpia el form y resetea el modo cuando se cierra el modal
if (elModalCronograma) {
    elModalCronograma.addEventListener('hidden.bs.modal', () => {
        modoModalCronograma = 'crear';
        cronogramaEditId = null;

        document.getElementById('modalTitleCronograma').textContent = 'Nuevo Cronograma';
        btnGuardarCronograma.innerHTML = '<i class="ri-save-line me-1"></i>Guardar cronograma';

        activarRequiredPassword();
        setHintPassword('');
        formCronograma.reset();
        ocultarAlerta(alertaModal);
    });
}