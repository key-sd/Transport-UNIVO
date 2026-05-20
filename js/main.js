// PARA EL LOGIN
// Carrusel
const slides = document.querySelectorAll('.fondo-slide');
const dots   = document.querySelectorAll('.dot');
let actual   = 0;

function cambiarSlide(nuevo) {
    if (!slides[actual] || !dots[actual]) return; // Control para evitar romper el hilo si no existen
    slides[actual].classList.remove('activo');
    dots[actual].classList.remove('activo');
    actual = nuevo;
    slides[actual].classList.add('activo');
    dots[actual].classList.add('activo');
}

// Avanza solo cada 4 segundos
setInterval(() => {
    if (slides.length > 0) cambiarSlide((actual + 1) % slides.length);
}, 4000);

// Clic en los puntitos
dots.forEach(dot => {
    dot.addEventListener('click', function () {
        cambiarSlide(parseInt(this.dataset.index));
    });
});

// Ojito contraseña
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

//limpiar campos al recargar la página del login
window.onload = function() {
    const txtUsuario = document.getElementById('usuario');
    const txtPassword = document.getElementById('password');
    if (txtUsuario) txtUsuario.value = '';
    if (txtPassword) txtPassword.value = '';
}

// librería de Moment.js para la fecha en tiempo real
const diasES  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const mesesES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];

const txtFechaHoy = document.getElementById('fecha-hoy');
if (txtFechaHoy && typeof moment !== 'undefined') {
    const hoy = moment();
    txtFechaHoy.textContent =
        diasES[hoy.day()] + ', ' + hoy.date() + ' de ' + mesesES[hoy.month()] + ' de ' + hoy.year();
}



///// Para la gestión de conductores en el admin /////
const elModalConductor = document.getElementById('modalConductor');
const modalBS      = elModalConductor ? new bootstrap.Modal(elModalConductor) : null;
const btnAbrir     = document.getElementById('btnAbrirModal');
const btnGuardar   = document.getElementById('btnGuardar');
const form         = document.getElementById('formConductor');
const alertaModal  = document.getElementById('alertaModal');
const alertaGlobal = document.getElementById('alertaGlobal');
const cuerpoTabla  = document.getElementById('cuerpoTabla');
const buscador     = document.getElementById('buscador');

let conductores = [];

// abre el modal limpio
if (btnAbrir) {
    btnAbrir.addEventListener('click', () => {
        document.getElementById('modalTitle').textContent = 'Nuevo Conductor';
        form.reset();
        ocultarAlerta(alertaModal);
        if (modalBS) modalBS.show();
    });
}

// limpia el form cuando se cierra el modal
if (elModalConductor) {
    elModalConductor.addEventListener('hidden.bs.modal', () => {
        form.reset();
        ocultarAlerta(alertaModal);
    });
}

// carga la tabla al entrar
if (cuerpoTabla) {
    document.addEventListener('DOMContentLoaded', cargarConductores);
}

function cargarConductores() {
    if (!cuerpoTabla) return;
    cuerpoTabla.innerHTML = `
        <tr>
            <td colspan="5" class="tabla-empty">
                <i class="ri-loader-4-line ri-spin"></i> Cargando conductores...
            </td>
        </tr>`;

    fetch('listar_conductores.php')
        .then(r => r.json())
        .then(data => {
            conductores = data;
            renderTabla(conductores);
        })
        .catch(() => {
            cuerpoTabla.innerHTML = `
                <tr>
                    <td colspan="5" class="tabla-empty">
                        <i class="ri-error-warning-line"></i> Error al cargar los datos.
                    </td>
                </tr>`;
        });
}

function renderTabla(lista) {
    if (!cuerpoTabla) return;
    if (!lista.length) {
        cuerpoTabla.innerHTML = `
            <tr>
                <td colspan="5" class="tabla-empty">
                    <i class="ri-user-search-line"></i> No hay conductores registrados.
                </td>
            </tr>`;
        return;
    }

    cuerpoTabla.innerHTML = lista.map((c, i) => {
        const iniciales = (c.nombre[0] + c.apellido[0]).toUpperCase();
        return `
            <tr class="animate__animated animate__fadeIn">
                <td>${i + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="conductor-tabla-avatar">${iniciales}</div>
                        <span>${c.nombre} ${c.apellido}</span>
                    </div>
                </td>
                <td><i class="ri-phone-line me-1 text-muted"></i>${c.telefono}</td>
                <td><i class="ri-id-card-line me-1 text-muted"></i><code>${c.codigo_universitario}</code></td>
                <td>
                    <button class="btn-tabla-editar" onclick="editarConductor(${c.id})" title="Editar">
                        <i class="ri-edit-line"></i>
                    </button>
                    <button class="${parseInt(c.estado) === 0 ? 'btn-tabla-activar' : 'btn-tabla-eliminar'}" 
                            onclick="cambiarEstadoConductor(${c.id}, ${c.estado})" 
                            title="${parseInt(c.estado) === 0 ? 'Activar' : 'Desactivar'}">
                        <i class="${parseInt(c.estado) === 0 ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'}"></i>
                    </button>
                </td>
            </tr>`;
    }).join('');
}

// buscador en tiempo real
if (buscador) {
    buscador.addEventListener('input', () => {
        const q = buscador.value.toLowerCase();
        renderTabla(conductores.filter(c =>
            c.nombre.toLowerCase().includes(q)       ||
            c.apellido.toLowerCase().includes(q)     ||
            c.codigo_universitario.toLowerCase().includes(q) ||
            c.telefono.includes(q)
        ));
    });
}

// guardar conductor
if (btnGuardar) {
    btnGuardar.addEventListener('click', () => {
        ocultarAlerta(alertaModal);

        const pwd  = document.getElementById('password') ? document.getElementById('password').value : '';
        const pwd2 = document.getElementById('confirmar_password') ? document.getElementById('confirmar_password').value : '';

        if (!form.checkValidity()) { form.reportValidity(); return; }

        if (pwd !== pwd2) {
            mostrarAlerta(alertaModal, 'error', 'Las contraseñas no coinciden.'); return;
        }
        if (pwd.length < 8) {
            mostrarAlerta(alertaModal, 'error', 'La contraseña debe tener al menos 8 caracteres.'); return;
        }

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i>Guardando...';

        fetch('crear_conductor.php', { method: 'POST', body: new FormData(form) })
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
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="ri-save-line me-1"></i>Guardar conductor';
            });
    });
}

function mostrarAlerta(el, tipo, mensaje) {
    if (!el) return;
    el.className = `alerta-modal animate__animated animate__shakeX ${tipo === 'error' ? 'alerta-error' : 'alerta-exito'}`;
    el.innerHTML = `<i class="ri-${tipo === 'error' ? 'error-warning' : 'checkbox-circle'}-line me-2"></i>${mensaje}`;
    el.classList.remove('d-none');
}

// Se saca de condicionales para asegurar disponibilidad global
const alertaGlobalElement = document.getElementById('alertaGlobal');
function mostrarAlertaGlobal(tipo, mensaje) {
    if (!alertaGlobalElement) return;
    alertaGlobalElement.className = `alerta-global animate__animated animate__fadeInDown ${tipo === 'exito' ? 'alerta-exito' : 'alerta-error'}`;
    alertaGlobalElement.innerHTML = `<i class="ri-${tipo === 'exito' ? 'checkbox-circle' : 'error-warning'}-line me-2"></i>${mensaje}`;
    alertaGlobalElement.classList.remove('d-none');
    setTimeout(() => alertaGlobalElement.classList.add('d-none'), 4000);
}

function ocultarAlerta(el) {
    if (!el) return;
    el.classList.add('d-none');
    el.className = 'd-none';
}


///// Para las tarjetas de conductores para móvil /////
const contenedorTarjetas = document.getElementById('contenedorTarjetas');
const buscadorMobile     = document.getElementById('buscadorMobile');

function renderTarjetas(lista) {
    if (!contenedorTarjetas) return;

    if (!lista.length) {
        contenedorTarjetas.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="ri-user-search-line d-block mb-2" style="font-size:2rem;"></i>
                No hay conductores registrados.
            </div>`;
        return;
    }

    contenedorTarjetas.innerHTML = lista.map(c => {
        const iniciales = (c.nombre[0] + c.apellido[0]).toUpperCase();
        return `
            <div class="conductor-card-mobile animate__animated animate__fadeIn">
                <div class="d-flex align-items-center gap-3">
                    <div class="conductor-card-avatar">${iniciales}</div>
                    <div class="flex-grow-1">
                        <p class="fw-semibold mb-0" style="color:#0d2346; font-size:14px;">${c.nombre} ${c.apellido}</p>
                        <p class="text-muted mb-0" style="font-size:12px;">
                            <i class="ri-id-card-line me-1"></i><code>${c.codigo_universitario}</code>
                        </p>
                        <p class="text-muted mb-0" style="font-size:12px;">
                            <i class="ri-phone-line me-1"></i>${c.telefono}
                        </p>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button class="btn-tabla-editar" onclick="editarConductor(${c.id})" title="Editar">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button class="${parseInt(c.estado) === 0 ? 'btn-tabla-activar' : 'btn-tabla-eliminar'}" 
                                onclick="cambiarEstadoConductor(${c.id}, ${c.estado})" 
                                title="${parseInt(c.estado) === 0 ? 'Activar' : 'Desactivar'}">
                            <i class="${parseInt(c.estado) === 0 ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'}"></i>
                        </button>
                    </div>
                </div>
            </div>`;
    }).join('');
}

// engancha el render de tarjetas al cargarConductores existente
const _cargarOriginal = cargarConductores;
cargarConductores = function() {
    _cargarOriginal();
};

// cuando el fetch termina, también llena las tarjetas
// sobreescribe renderTabla para que llame también a renderTarjetas
const _renderTablaOriginal = renderTabla;
renderTabla = function(lista) {
    _renderTablaOriginal(lista);
    renderTarjetas(lista);
};

// buscador móvil — sincronizado con el de desktop
if (buscadorMobile) {
    buscadorMobile.addEventListener('input', () => {
        const q = buscadorMobile.value.toLowerCase();
        const filtrados = conductores.filter(c =>
            c.nombre.toLowerCase().includes(q)                ||
            c.apellido.toLowerCase().includes(q)              ||
            c.codigo_universitario.toLowerCase().includes(q)  ||
            c.telefono.includes(q)
        );
        renderTabla(filtrados);
        if (buscador) buscador.value = buscadorMobile.value;
    });
}
// función para camnbiar el estaado del conductor
function cambiarEstadoConductor(id, estadoActual) {
    const nuevoEstado = parseInt(estadoActual) === 1 ? 0 : 1;
    const accionTexto = nuevoEstado === 0 ? 'desactivar' : 'activar';
// Confirmación antes de cambiar el estado
    if (confirm(`¿Estás seguro de que deseas ${accionTexto} a este conductor?`)) {
        const datos = new FormData();
        datos.append('id', id);
        datos.append('estado', nuevoEstado);

        fetch('cambiar_estado_conductor.php', {
            method: 'POST',
            body: datos
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                mostrarAlertaGlobal('exito', resp.message);
                cargarConductores();
            } else {
                mostrarAlertaGlobal('error', resp.message);
            }
        })
        .catch(() => mostrarAlertaGlobal('error', 'Error al procesar la solicitud.'));
    }
}

// para edicion xd
function editarConductor(id) {
    console.log("Editar conductor con ID:", id);
}
///// Para la gestión de horarios en el admin /////
const modalHorarioElem = document.getElementById('modalHorario');
const modalHorarioBS   = modalHorarioElem ? new bootstrap.Modal(modalHorarioElem) : null;
const btnAbrirHor      = document.getElementById('btnAbrirModalHorario');
const btnGuardarHor    = document.getElementById('btnGuardarHorario');
const formHor          = document.getElementById('formHorario');
const cuerpoTablaHor   = document.getElementById('cuerpoTablaHorarios');

let horarios = [];

if (cuerpoTablaHor) {
    if (btnAbrirHor) {
        btnAbrirHor.addEventListener('click', () => {
            document.getElementById('modalTitle').textContent = 'Nuevo Horario';
            if (formHor) formHor.reset();
            ocultarAlerta(alertaModal);
            if (modalHorarioBS) modalHorarioBS.show();
        });
    }

    if (modalHorarioElem) {
        modalHorarioElem.addEventListener('hidden.bs.modal', () => {
            if (formHor) formHor.reset();
            ocultarAlerta(alertaModal);
        });
    }

    document.addEventListener('DOMContentLoaded', cargarHorarios);
}

function cargarHorarios() {
    if (!cuerpoTablaHor) return;
    cuerpoTablaHor.innerHTML = `
        <tr>
            <td colspan="3" class="tabla-empty">
                <i class="ri-loader-4-line ri-spin"></i> Cargando horarios...
            </td>
        </tr>`;

    fetch('listar_horarios.php')
        .then(r => r.json())
        .then(data => {
            horarios = data;
            renderTablaHorarios(horarios);
        })
        .catch(() => {
            cuerpoTablaHor.innerHTML = `
                <tr>
                    <td colspan="3" class="tabla-empty">
                        <i class="ri-error-warning-line"></i> Error al cargar los datos.
                    </td>
                </tr>`;
        });
}

function renderTablaHorarios(lista) {
    if (!cuerpoTablaHor) return;

    if (!lista.length) {
        cuerpoTablaHor.innerHTML = `
            <tr>
                <td colspan="3" class="tabla-empty">
                    <i class="ri-time-line"></i> No hay horarios registrados.
                </td>
            </tr>`;
        return;
    }

    cuerpoTablaHor.innerHTML = lista.map(h => {
        const esInactivo = parseInt(h.estado) === 0;
        return `
            <tr class="animate__animated animate__fadeIn">
                <td>${h.id}</td>
                <td>
                    <span class="hora-badge">
                        <i class="ri-time-fill"></i>${h.hora_formateada}
                    </span>
                </td>
                <td class="text-end">
                    <button class="btn-tabla-editar"
                            onclick="abrirModalEditarHorario(${h.id}, '${h.hora}')"
                            title="Editar">
                        <i class="ri-edit-line"></i>
                    </button>
                    <button class="${esInactivo ? 'btn-tabla-activar' : 'btn-tabla-eliminar'}"
                            onclick="cambiarEstadoHorario(${h.id}, ${h.estado})"
                            title="${esInactivo ? 'Activar' : 'Desactivar'}">
                        <i class="${esInactivo ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'}"></i>
                    </button>
                </td>
            </tr>`;
    }).join('');
}

///// Tarjetas de horarios para móvil /////
const contenedorTarjetasHorarios = document.getElementById('contenedorTarjetasHorarios');

function renderTarjetasHorarios(lista) {
    if (!contenedorTarjetasHorarios) return;

    if (!lista.length) {
        contenedorTarjetasHorarios.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="ri-time-line d-block mb-2" style="font-size:2rem;"></i>
                No hay horarios registrados.
            </div>`;
        return;
    }

    contenedorTarjetasHorarios.innerHTML = lista.map(h => {
        const esInactivo = parseInt(h.estado) === 0;
        return `
            <div class="conductor-card-mobile animate__animated animate__fadeIn">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="conductor-card-avatar">
                            <i class="ri-time-fill"></i>
                        </div>
                        <div>
                            <p class="fw-semibold mb-0" style="color:#0d2346; font-size:15px;">${h.hora_formateada}</p>
                            <p class="text-muted mb-0" style="font-size:12px;">ID: ${h.id}</p>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button class="btn-tabla-editar"
                                onclick="abrirModalEditarHorario(${h.id}, '${h.hora}')"
                                title="Editar">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button class="${esInactivo ? 'btn-tabla-activar' : 'btn-tabla-eliminar'}"
                                onclick="cambiarEstadoHorario(${h.id}, ${h.estado})"
                                title="${esInactivo ? 'Activar' : 'Desactivar'}">
                            <i class="${esInactivo ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'}"></i>
                        </button>
                    </div>
                </div>
            </div>`;
    }).join('');
}

// Sobreescritura para que renderTablaHorarios también llene las tarjetas móviles
const _renderTablaHorariosOriginal = renderTablaHorarios;
renderTablaHorarios = function(lista) {
    _renderTablaHorariosOriginal(lista);
    renderTarjetasHorarios(lista);
};

// Buscador móvil horarios
const buscadorMobileHor = document.getElementById('buscadorMobileHorarios');
if (buscadorMobileHor) {
    buscadorMobileHor.addEventListener('input', () => {
        const q = buscadorMobileHor.value.toLowerCase();
        const filtrados = horarios.filter(h =>
            h.hora_formateada.toLowerCase().includes(q) ||
            String(h.id).includes(q)
        );
        renderTablaHorarios(filtrados);
        const buscadorDesktopHor = document.getElementById('buscadorHorarios');
        if (buscadorDesktopHor) buscadorDesktopHor.value = buscadorMobileHor.value;
    });
}

// Buscador desktop horarios
const buscadorDesktopHor = document.getElementById('buscadorHorarios');
if (buscadorDesktopHor) {
    buscadorDesktopHor.addEventListener('input', () => {
        const q = buscadorDesktopHor.value.toLowerCase();
        renderTablaHorarios(horarios.filter(h =>
            h.hora_formateada.toLowerCase().includes(q) ||
            String(h.id).includes(q)
        ));
    });
}

if (btnGuardarHor && formHor) {
    btnGuardarHor.addEventListener('click', () => {
        ocultarAlerta(alertaModal);
        if (!formHor.checkValidity()) { formHor.reportValidity(); return; }

        btnGuardarHor.disabled = true;
        btnGuardarHor.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i>Guardando...';

        fetch('crear_horario.php', { method: 'POST', body: new FormData(formHor) })
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
                btnGuardarHor.disabled = false;
                btnGuardarHor.innerHTML = '<i class="ri-save-line me-1"></i>Guardar horario';
            });
    });
}

function cambiarEstadoHorario(id, estadoActual) {
    const nuevoEstado = parseInt(estadoActual) === 1 ? 0 : 1;
    const accionTexto = nuevoEstado === 0 ? 'desactivar' : 'activar';

    if (confirm(`¿Estás seguro de que deseas ${accionTexto} este horario?`)) {
        const datos = new FormData();
        datos.append('id', id);
        datos.append('estado', nuevoEstado);

        fetch('cambiar_estado_horario.php', {
            method: 'POST',
            body: datos
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                mostrarAlertaGlobal('exito', resp.message);
                cargarHorarios();
            } else {
                mostrarAlertaGlobal('error', resp.message);
            }
        })
        .catch(() => mostrarAlertaGlobal('error', 'Error al procesar la solicitud.'));
    }
}

function abrirModalEditarHorario(id, hora) {
    console.log("Editar horario con ID:", id, "Hora:", hora);
}