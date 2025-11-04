/**
 * أنيميشن وحركات إبداعية - عزم الإنجاز
 * إضافة حركات ديناميكية للصفحة الرئيسية
 */

(function() {
    'use strict';

    // ============ Scroll Animations ============
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        });
    }, observerOptions);

    const heroSection = document.querySelector('.hero-section');
    const heroVisual = document.querySelector('.hero-visual');

    // إضافة fade-on-scroll للعناصر + تحضير بطاقات الهيرو
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.section-pad, .accordion-item, .soft-link');
        elements.forEach(el => {
            el.classList.add('fade-on-scroll');
            observer.observe(el);
        });

        const animatedElements = document.querySelectorAll('.animate-fade, .animate-slide-right, .animate-slide-left');
        animatedElements.forEach(el => observer.observe(el));

        if (heroVisual) {
            heroVisual.setAttribute('data-interactive', 'true');

            const counters = heroVisual.querySelectorAll('.metric-value[data-count]');
            if (counters.length) {
                const metricsObserver = new IntersectionObserver((entries, obs) => {
                    entries.forEach(entry => {
                        if (!entry.isIntersecting) return;
                        const el = entry.target;
                        const target = parseInt(el.getAttribute('data-count') || '0', 10);
                        if (!Number.isNaN(target)) {
                            animateCounter(el, target, 1600);
                        }
                        obs.unobserve(el);
                    });
                }, { threshold: 0.6 });

                counters.forEach(counter => metricsObserver.observe(counter));
            }
        }
    });

    // ============ Parallax Effect ============
    const updateHeroOnScroll = () => {
        const scrolled = window.pageYOffset;

        if (heroSection) {
            const fadeProgress = Math.min(scrolled / 600, 1);
            heroSection.style.transform = 'translateY(0)';
            heroSection.style.opacity = (1 - fadeProgress * 0.35).toString();
        }

        if (heroVisual) {
            const progress = Math.min(scrolled / 1200, 1);
            heroVisual.style.setProperty('--scroll-progress', progress.toFixed(3));
        }
    };

    updateHeroOnScroll();
    window.addEventListener('scroll', updateHeroOnScroll, { passive: true });

    // ============ Particles Background ============
    function createParticles() {
        const particlesContainer = document.createElement('div');
        particlesContainer.className = 'particles';
        document.body.appendChild(particlesContainer);

        const particleCount = 30;
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particlesContainer.appendChild(particle);
        }
    }

    // ============ Typing Effect ============
    function typeWriter(element, text, speed = 100) {
        if (!element) return;
        
        let i = 0;
        element.textContent = '';
        
        function type() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        }
        
        type();
    }

    // ============ Counter Animation ============
    function animateCounter(element, target, duration = 2000) {
        if (!element) return;

        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        const prefix = element.getAttribute('data-prefix') || '';
        const suffix = element.getAttribute('data-suffix') || '';

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = `${prefix}${target}${suffix}`;
                clearInterval(timer);
            } else {
                element.textContent = `${prefix}${Math.floor(current)}${suffix}`;
            }
        }, 16);
    }

    // ============ Smooth Scroll to Anchor ============
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#' || href === '') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ============ Navbar Scroll Effect ============
    const navbar = document.querySelector('.floating-nav');
    const isHomePage = document.body.classList.contains('home-page');

    const updateNavState = () => {
        if (!navbar) return;
        if (!isHomePage) {
            navbar.classList.add('scrolled');
            return;
        }
        const shouldSolid = (window.pageYOffset || document.documentElement.scrollTop || 0) > 50;
        navbar.classList.toggle('scrolled', shouldSolid);
    };

    if (navbar) {
        if (isHomePage) {
            updateNavState();
            window.addEventListener('scroll', updateNavState, { passive: true });
        } else {
            navbar.classList.add('scrolled');
        }

        const navMenu = document.getElementById('navMenu');
        if (navMenu) {
            if (navMenu.classList.contains('show')) {
                navbar.classList.add('nav-open');
            }

            navMenu.addEventListener('shown.bs.collapse', () => {
                navbar.classList.add('nav-open');
            });

            navMenu.addEventListener('hidden.bs.collapse', () => {
                navbar.classList.remove('nav-open');
                updateNavState();
            });
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth > 991.98) {
                navbar.classList.remove('nav-open');
            }
        });
    }

    // ============ Cursor Follow Effect ============
    let cursor = null;
    
    function initCursorEffect() {
        cursor = document.createElement('div');
        cursor.className = 'custom-cursor';
        cursor.style.cssText = `
            width: 20px;
            height: 20px;
            border: 2px solid var(--primary);
            border-radius: 50%;
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            transition: transform 0.2s ease;
            display: none;
        `;
        document.body.appendChild(cursor);
        
        document.addEventListener('mousemove', (e) => {
            if (cursor) {
                cursor.style.left = e.clientX - 10 + 'px';
                cursor.style.top = e.clientY - 10 + 'px';
                cursor.style.display = 'block';
            }
        });
        
        document.querySelectorAll('a, button').forEach(el => {
            el.addEventListener('mouseenter', () => {
                if (cursor) cursor.style.transform = 'scale(1.5)';
            });
            el.addEventListener('mouseleave', () => {
                if (cursor) cursor.style.transform = 'scale(1)';
            });
        });
    }

    // ============ Page Load Animation ============
    window.addEventListener('load', function() {
        // إزالة loading overlay إن وجد
        const loading = document.querySelector('.loading-overlay');
        if (loading) {
            setTimeout(() => {
                loading.remove();
            }, 1500);
        }
        
        // إنشاء particles
        createParticles();
        
        // تفعيل cursor effect (اختياري)
        // initCursorEffect();
        
        // إضافة fade-in للصفحة
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.5s ease-in';

        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 100);

        if (heroVisual) {
            heroVisual.style.setProperty('--pointer-x', '0');
            heroVisual.style.setProperty('--pointer-y', '0');
        }
    });

    // ============ Accordion Smooth Animation ============
    document.querySelectorAll('.accordion-button').forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('.accordion-icon');
            if (icon) {
                icon.style.transform = this.classList.contains('collapsed') 
                    ? 'rotate(0deg)' 
                    : 'rotate(180deg)';
            }
        });
    });

    // ============ Button Click Ripple ============
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                border-radius: 50%;
                background: rgba(255,255,255,0.5);
                left: ${x}px;
                top: ${y}px;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // إضافة keyframe للripple
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // ============ Stagger Animation for Lists ============
    function staggerListItems(list) {
        const items = list.querySelectorAll('li');
        items.forEach((item, index) => {
            item.style.animationDelay = (index * 0.1) + 's';
            item.classList.add('fade-on-scroll');
            observer.observe(item);
        });
    }

    document.querySelectorAll('.hero-card ul, .footer ul').forEach(list => {
        staggerListItems(list);
    });

    // ============ Hero Interactive Motion ============
    if (heroSection && heroVisual) {
        let pointerX = 0;
        let pointerY = 0;
        let raf = null;

        const applyPointer = () => {
            heroVisual.style.setProperty('--pointer-x', pointerX.toFixed(3));
            heroVisual.style.setProperty('--pointer-y', pointerY.toFixed(3));
            raf = null;
        };

        const schedule = () => {
            if (raf !== null) return;
            raf = requestAnimationFrame(applyPointer);
        };

        const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

        heroSection.addEventListener('pointermove', (event) => {
            const rect = heroVisual.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width;
            const y = (event.clientY - rect.top) / rect.height;

            if (x >= 0 && x <= 1 && y >= 0 && y <= 1) {
                pointerX = clamp((x - 0.5) * 30, -18, 18);
                pointerY = clamp((y - 0.5) * 30, -18, 18);
            } else {
                pointerX = 0;
                pointerY = 0;
            }
            schedule();
        });

        heroSection.addEventListener('pointerleave', () => {
            pointerX = 0;
            pointerY = 0;
            schedule();
        });
    }

})();
