// JavaScript untuk TripGo
(function() {
    'use strict';

// Smooth Scroll untuk navigasi
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '#login') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// Navbar background change on scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (navbar && navbar.classList.contains('fixed-top')) {
        if (window.scrollY > 50) {
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.2)';
        } else {
            navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        }
    }
});

// Form validation
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});

// Date input minimum date (hari ini)
const dateInputs = document.querySelectorAll('input[type="date"]');
const today = new Date().toISOString().split('T')[0];
dateInputs.forEach(input => {
    input.setAttribute('min', today);
});

// Filter toggle untuk mobile
function toggleFilter() {
    const filterCard = document.querySelector('.col-lg-3 .card');
    if (filterCard) {
        filterCard.classList.toggle('d-none');
    }
}

// Animasi counter untuk halaman tentang
function animateCounter(element, target) {
    let current = 0;
    const increment = target / 100;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target.toLocaleString('id-ID') + '+';
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current).toLocaleString('id-ID');
        }
    }, 20);
}

// Jalankan counter animation ketika element terlihat
const observerOptions = {
    threshold: 0.5
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const counters = entry.target.querySelectorAll('h2.fw-bold');
            counters.forEach(counter => {
                const text = counter.textContent;
                const number = parseInt(text.replace(/\D/g, ''));
                if (number) {
                    counter.textContent = '0';
                    animateCounter(counter, number);
                }
            });
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe stats section jika ada
const statsSection = document.querySelector('.stats-section');
if (statsSection) {
    observer.observe(statsSection);
}

// Toast notification function
function showToast(message, type = 'success') {
    const toastHTML = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Console log untuk development
console.log('TripGo - Aplikasi Bus Terpercaya');
console.log('© 2025 TripGo. All rights reserved.');

})(); // End of IIFE
