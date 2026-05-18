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