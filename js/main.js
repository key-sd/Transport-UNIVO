// PARA EL LOGIN
// Carrusel
const slides = document.querySelectorAll('.fondo-slide');
const dots   = document.querySelectorAll('.dot');
let actual   = 0;

function cambiarSlide(nuevo) {
    slides[actual].classList.remove('activo');
    dots[actual].classList.remove('activo');
    actual = nuevo;
    slides[actual].classList.add('activo');
    dots[actual].classList.add('activo');
}

// Avanza solo cada 4 segundos
setInterval(() => {
    cambiarSlide((actual + 1) % slides.length);
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
    document.getElementById('usuario').value = '';
    document.getElementById('password').value = '';
}

// librería de Moment.js para la fecha en tiempo real
const diasES  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const mesesES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];

const hoy = moment();
document.getElementById('fecha-hoy').textContent =
    diasES[hoy.day()] + ', ' + hoy.date() + ' de ' + mesesES[hoy.month()] + ' de ' + hoy.year();



///// Para la gestión de conductores en el admin /////
const modalBS      = new bootstrap.Modal(document.getElementById('modalConductor'));
const btnAbrir     = document.getElementById('btnAbrirModal');
const btnGuardar   = document.getElementById('btnGuardar');
const form         = document.getElementById('formConductor');
const alertaModal  = document.getElementById('alertaModal');
const alertaGlobal = document.getElementById('alertaGlobal');
const cuerpoTabla  = document.getElementById('cuerpoTabla');
const buscador     = document.getElementById('buscador');

let conductores = [];

// abre el modal limpio
btnAbrir.addEventListener('click', () => {
    document.getElementById('modalTitle').textContent = 'Nuevo Conductor';
    form.reset();
    ocultarAlerta(alertaModal);
    modalBS.show();
});

// limpia el form cuando se cierra el modal
document.getElementById('modalConductor').addEventListener('hidden.bs.modal', () => {
    form.reset();
    ocultarAlerta(alertaModal);
});

// carga la tabla al entrar
document.addEventListener('DOMContentLoaded', cargarConductores);

function cargarConductores() {
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
                    <button class="btn-tabla-editar" title="Editar">
                        <i class="ri-pencil-line"></i>
                    </button>
                    <button class="btn-tabla-eliminar" title="Eliminar">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            </tr>`;
    }).join('');
}

// buscador en tiempo real
buscador.addEventListener('input', () => {
    const q = buscador.value.toLowerCase();
    renderTabla(conductores.filter(c =>
        c.nombre.toLowerCase().includes(q)       ||
        c.apellido.toLowerCase().includes(q)     ||
        c.codigo_universitario.toLowerCase().includes(q) ||
        c.telefono.includes(q)
    ));
});

// guardar conductor
btnGuardar.addEventListener('click', () => {
    ocultarAlerta(alertaModal);

    const pwd  = document.getElementById('password').value;
    const pwd2 = document.getElementById('confirmar_password').value;

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
                modalBS.hide();
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

function mostrarAlerta(el, tipo, mensaje) {
    el.className = `alerta-modal animate__animated animate__shakeX ${tipo === 'error' ? 'alerta-error' : 'alerta-exito'}`;
    el.innerHTML = `<i class="ri-${tipo === 'error' ? 'error-warning' : 'checkbox-circle'}-line me-2"></i>${mensaje}`;
    el.classList.remove('d-none');
}

function mostrarAlertaGlobal(tipo, mensaje) {
    alertaGlobal.className = `alerta-global animate__animated animate__fadeInDown ${tipo === 'exito' ? 'alerta-exito' : 'alerta-error'}`;
    alertaGlobal.innerHTML = `<i class="ri-${tipo === 'exito' ? 'checkbox-circle' : 'error-warning'}-line me-2"></i>${mensaje}`;
    alertaGlobal.classList.remove('d-none');
    setTimeout(() => alertaGlobal.classList.add('d-none'), 4000);
}

function ocultarAlerta(el) {
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
                        <button class="btn-tabla-editar" title="Editar">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button class="btn-tabla-eliminar" title="Eliminar">
                            <i class="ri-delete-bin-line"></i>
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