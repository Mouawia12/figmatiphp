/**
 * أنيميشن متقدمة وإبداعية - عزم الإنجاز
 * تأثيرات حديثة ومبتكرة ✨
 */

(function() {
    'use strict';

    // ============ Scroll Progress Indicator ============
    function initScrollIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'scroll-indicator';
        indicator.innerHTML = '<div class="scroll-progress"></div>';
        document.body.appendChild(indicator);

        const progress = indicator.querySelector('.scroll-progress');
        
        window.addEventListener('scroll', () => {
            const winScroll = document.documentElement.scrollTop || document.body.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            progress.style.width = scrolled + '%';
        });
    }

    // ============ Advanced Interactive Cursor ============
    function initAdvancedCursor() {
        if (window.innerWidth < 768) return; // فقط للشاشات الكبيرة

        const cursor = document.createElement('div');
        cursor.className = 'cursor-dot';
        document.body.appendChild(cursor);

        const outline = document.createElement('div');
        outline.className = 'cursor-outline';
        document.body.appendChild(outline);

        let mouseX = 0, mouseY = 0;
        let outlineX = 0, outlineY = 0;

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            
            cursor.style.left = mouseX - 5 + 'px';
            cursor.style.top = mouseY - 5 + 'px';
        });

        const animate = () => {
            outlineX += (mouseX - outlineX) * 0.1;
            outlineY += (mouseY - outlineY) * 0.1;
            
            outline.style.left = outlineX - 20 + 'px';
            outline.style.top = outlineY - 20 + 'px';
            
            requestAnimationFrame(animate);
        };
        animate();

        // تأثير عند hover على العناصر التفاعلية
        document.querySelectorAll('a, button, .card').forEach(el => {
            el.addEventListener('mouseenter', () => {
                cursor.style.transform = 'scale(1.5)';
                outline.style.transform = 'scale(1.5)';
                outline.style.borderColor = '#667eea';
            });
            el.addEventListener('mouseleave', () => {
                cursor.style.transform = 'scale(1)';
                outline.style.transform = 'scale(1)';
                outline.style.borderColor = '#667eea';
            });
        });
    }

    // ============ Magnetic Effect for Buttons ============
    function initMagneticButtons() {
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                
                const moveX = x * 0.3;
                const moveY = y * 0.3;
                
                this.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translate(0, 0)';
            });
        });
    }

    // ============ Text Split Animation ============
    function splitText(element) {
        if (!element) return;
        
        const text = element.textContent;
        const words = text.split(' ');
        element.innerHTML = words.map(word => 
            `<span style="display: inline-block;">${word.split('').map(char => 
                `<span style="display: inline-block;">${char}</span>`
            ).join('')}</span>`
        ).join(' ');
    }

    // ============ Advanced Parallax ============
    function initAdvancedParallax() {
        const parallaxElements = document.querySelectorAll('.hero-section, .section-pad');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach((el, index) => {
                const speed = (index + 1) * 0.1;
                el.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    }

    // ============ Intersection Observer for Advanced Animations ============
    const advancedObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                
                // إضافة تأثيرات خاصة
                if (entry.target.classList.contains('reveal')) {
                    entry.target.style.animation = 'reveal 1s ease-out forwards';
                }
                
                if (entry.target.classList.contains('flip-card')) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                    }, 200);
                }
            }
        });
    }, {
        threshold: 0.2,
        rootMargin: '0px'
    });

    // ============ Particle System ============
    function createAdvancedParticles() {
        const canvas = document.createElement('canvas');
        canvas.className = 'particle-canvas';
        canvas.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: 0.3;
        `;
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const particles = [];
        const particleCount = 50;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 3 + 1;
                this.speedX = Math.random() * 2 - 1;
                this.speedY = Math.random() * 2 - 1;
                this.opacity = Math.random() * 0.5 + 0.2;
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                if (this.x > canvas.width) this.x = 0;
                if (this.x < 0) this.x = canvas.width;
                if (this.y > canvas.height) this.y = 0;
                if (this.y < 0) this.y = canvas.height;
            }

            draw() {
                ctx.fillStyle = `rgba(102, 126, 234, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });

            // ربط الجزيئات بخطوط
            particles.forEach((particle, i) => {
                particles.slice(i + 1).forEach(otherParticle => {
                    const dx = particle.x - otherParticle.x;
                    const dy = particle.y - otherParticle.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < 100) {
                        ctx.strokeStyle = `rgba(102, 126, 234, ${0.2 * (1 - distance / 100)})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particle.x, particle.y);
                        ctx.lineTo(otherParticle.x, otherParticle.y);
                        ctx.stroke();
                    }
                });
            });

            requestAnimationFrame(animate);
        }

        animate();

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    }

    // ============ Glitch Effect on Hover ============
    function initGlitchEffect() {
        const titles = document.querySelectorAll('h1, h2, h3');
        titles.forEach(title => {
            title.classList.add('glitch');
        });
    }

    // ============ Morphing Background ============
    function initMorphingBg() {
        const hero = document.querySelector('.hero-section');
        if (hero) {
            hero.classList.add('morph-bg');
        }
    }

    // ============ Stagger Animation for Lists ============
    function initStaggerLists() {
        document.querySelectorAll('.hero-card ul, .list-unstyled').forEach(list => {
            list.classList.add('stagger-list');
        });
    }

    // ============ Card Flip on Hover ============
    function initCardFlip() {
        document.querySelectorAll('.card').forEach(card => {
            if (!card.querySelector('.flip-card-back')) return;
            
            card.classList.add('flip-card');
            const inner = document.createElement('div');
            inner.className = 'flip-card-inner';
            
            Array.from(card.children).forEach(child => {
                inner.appendChild(child.cloneNode(true));
            });
            
            card.innerHTML = '';
            card.appendChild(inner);
        });
    }

    // ============ Smooth Scroll with Easing ============
    function smoothScrollTo(target, duration = 1000) {
        const targetElement = typeof target === 'string' 
            ? document.querySelector(target) 
            : target;
        
        if (!targetElement) return;

        const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        let startTime = null;

        function animation(currentTime) {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const run = easeInOutCubic(timeElapsed, startPosition, distance, duration);
            window.scrollTo(0, run);
            if (timeElapsed < duration) requestAnimationFrame(animation);
        }

        function easeInOutCubic(t, b, c, d) {
            t /= d / 2;
            if (t < 1) return c / 2 * t * t * t + b;
            t -= 2;
            return c / 2 * (t * t * t + 2) + b;
        }

        requestAnimationFrame(animation);
    }

    // ============ Initialize All ============
    window.addEventListener('DOMContentLoaded', function() {
        // Scroll indicator
        initScrollIndicator();
        
        // Advanced cursor (only desktop)
        if (window.innerWidth >= 768) {
            initAdvancedCursor();
        }
        
        // Magnetic buttons
        initMagneticButtons();
        
        // Advanced parallax
        initAdvancedParallax();
        
        // Particle system
        createAdvancedParticles();
        
        // Glitch effect
        initGlitchEffect();
        
        // Morphing background
        initMorphingBg();
        
        // Stagger lists
        initStaggerLists();
        
        // Observe elements
        document.querySelectorAll('.reveal, .flip-card, .card-advanced').forEach(el => {
            advancedObserver.observe(el);
        });
        
        // Split text animation for hero title
        const heroTitle = document.querySelector('.hero-section h1');
        if (heroTitle) {
            splitText(heroTitle);
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '') return;
                e.preventDefault();
                smoothScrollTo(href);
            });
        });
    });

    // ============ Window Load ============
    window.addEventListener('load', function() {
        // Remove loading overlay
        const loading = document.querySelector('.loading-overlay');
        if (loading) {
            setTimeout(() => {
                loading.style.opacity = '0';
                setTimeout(() => loading.remove(), 500);
            }, 1000);
        }

        // Fade in body
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.8s ease-in';
        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 200);
    });

})();

