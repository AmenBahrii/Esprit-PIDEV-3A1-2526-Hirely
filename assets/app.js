import './bootstrap.js';
import './styles/app.css';
import { gsap } from 'gsap';
import Lenis from 'lenis';
import SplitType from 'split-type';

const hoverBound = new WeakSet();
let pointerBound = false;
let currentCleanup = null;
let lenisInstance = null;
let lenisRafId = null;
let splitInstances = [];

const shellSelectors = {
    topbar: '.topbar-layout',
    sidebar: '.sidebar-shell',
    hero: '.dashboard-hero, .flow-hero-panel, .analytics-grid-plan .analytics-card, .analytics-grid .analytics-card',
    blocks: '.content-stage > *, .record-card, .metric-card, .quick-link-card, .flow-side-block, .flow-phase-card, .form-field, .search-form-panel, .attachment-uploader',
    buttons: '.btn-primary, .btn-secondary, .btn-danger, .search-toggle, .custom-select-trigger, .workspace-nav-link, .sidebar-link, .attachment-link'
};

const clearTransientStyles = (scope = document) => {
    scope.querySelectorAll('.record-card, .metric-card, .quick-link-card, .analytics-card, .flow-side-block, .flow-phase-card, .form-field, .search-form-panel, .attachment-uploader, .topbar-layout, .sidebar-shell, .dashboard-hero, .flow-hero-panel').forEach((node) => {
        node.style.removeProperty('opacity');
        node.style.removeProperty('transform');
        node.style.removeProperty('filter');
    });
};

const animateEntrances = (scope = document) => {
    const topbar = scope.querySelector(shellSelectors.topbar);
    const sidebar = scope.querySelector(shellSelectors.sidebar);
    const heroNodes = scope.querySelectorAll(shellSelectors.hero);
    const blockNodes = scope.querySelectorAll(shellSelectors.blocks);

    const timeline = gsap.timeline({
        defaults: {
            duration: 0.65,
            ease: 'power3.out'
        }
    });

    if (sidebar) {
        timeline.fromTo(sidebar, {
            autoAlpha: 0,
            x: -26,
            filter: 'blur(10px)'
        }, {
            autoAlpha: 1,
            x: 0,
            filter: 'blur(0px)'
        }, 0);
    }

    if (topbar) {
        timeline.fromTo(topbar, {
            autoAlpha: 0,
            y: -18,
            filter: 'blur(10px)'
        }, {
            autoAlpha: 1,
            y: 0,
            filter: 'blur(0px)'
        }, sidebar ? 0.04 : 0);
    }

    if (heroNodes.length) {
        timeline.fromTo(heroNodes, {
            autoAlpha: 0,
            y: 22,
            scale: 0.985,
            filter: 'blur(12px)'
        }, {
            autoAlpha: 1,
            y: 0,
            scale: 1,
            filter: 'blur(0px)',
            stagger: 0.08
        }, 0.14);
    }

    if (blockNodes.length) {
        timeline.fromTo(blockNodes, {
            autoAlpha: 0,
            y: 26,
            scale: 0.985,
            filter: 'blur(10px)'
        }, {
            autoAlpha: 1,
            y: 0,
            scale: 1,
            filter: 'blur(0px)',
            stagger: 0.028
        }, 0.2);
    }
};

const animateAmbientShell = (scope = document) => {
    const glows = scope.querySelectorAll('.shell-glow-a, .shell-glow-b');
    const hero = scope.querySelector('.flow-hero-panel, .dashboard-hero');
    const pulseDots = scope.querySelectorAll('.topbar-kicker-dot, .flow-kicker-dot');

    glows.forEach((glow, index) => {
        gsap.to(glow, {
            x: index === 0 ? -24 : 32,
            y: index === 0 ? 24 : -18,
            scale: index === 0 ? 1.14 : 1.1,
            duration: index === 0 ? 8.2 : 9.6,
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true
        });
    });

    if (hero) {
        gsap.to(hero, {
            boxShadow: '0 28px 54px rgba(35, 30, 83, 0.24)',
            duration: 2.8,
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true
        });
    }

    pulseDots.forEach((dot, index) => {
        gsap.to(dot, {
            scale: 1.35,
            opacity: 1,
            duration: 0.95,
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true,
            delay: index * 0.08
        });
    });
};

const bindHoverAnimations = (scope = document) => {
    scope.querySelectorAll(`${shellSelectors.buttons}, .record-card, .metric-card, .quick-link-card, .analytics-card, .flow-side-block, .flow-phase-card, .flow-score-chip, .flow-mission-card`).forEach((node) => {
        if (hoverBound.has(node)) {
            return;
        }

        hoverBound.add(node);

        const enter = () => {
            gsap.to(node, {
                y: -4,
                scale: 1.006,
                duration: 0.22,
                ease: 'power2.out'
            });
        };

        const leave = () => {
            gsap.to(node, {
                y: 0,
                scale: 1,
                duration: 0.24,
                ease: 'power2.out'
            });
        };

        node.addEventListener('mouseenter', enter);
        node.addEventListener('mouseleave', leave);
        node.addEventListener('focus', enter, true);
        node.addEventListener('blur', leave, true);
    });
};

const bindPointerParallax = (scope = document) => {
    if (pointerBound) {
        return;
    }

    pointerBound = true;

    const glowA = scope.querySelector('.shell-glow-a');
    const glowB = scope.querySelector('.shell-glow-b');
    const topbar = scope.querySelector('.topbar-layout');
    const hero = scope.querySelector('.flow-hero-panel');

    const handleMove = (event) => {
        const x = (event.clientX / window.innerWidth) - 0.5;
        const y = (event.clientY / window.innerHeight) - 0.5;

        if (glowA) {
            gsap.to(glowA, {
                x: x * -46,
                y: y * -38,
                duration: 0.9,
                ease: 'power3.out',
                overwrite: true
            });
        }

        if (glowB) {
            gsap.to(glowB, {
                x: x * 56,
                y: y * 34,
                duration: 0.9,
                ease: 'power3.out',
                overwrite: true
            });
        }

        if (topbar) {
            gsap.to(topbar, {
                x: x * 8,
                y: y * 5,
                duration: 0.8,
                ease: 'power3.out',
                overwrite: true
            });
        }

        if (hero) {
            gsap.to(hero, {
                x: x * 10,
                y: y * 8,
                duration: 0.9,
                ease: 'power3.out',
                overwrite: true
            });
        }
    };

    window.addEventListener('mousemove', handleMove, { passive: true });
};

const destroySplitText = () => {
    splitInstances.forEach((instance) => instance.revert());
    splitInstances = [];
};

const destroyLenis = () => {
    if (lenisRafId) {
        cancelAnimationFrame(lenisRafId);
        lenisRafId = null;
    }

    if (lenisInstance) {
        lenisInstance.destroy();
        lenisInstance = null;
    }
};

const initLenis = () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.classList.remove('lenis');
        return () => {};
    }

    destroyLenis();

    lenisInstance = new Lenis({
        duration: 1.05,
        lerp: 0.08,
        smoothWheel: true,
        wheelMultiplier: 0.92,
        touchMultiplier: 1,
        syncTouch: false
    });

    const raf = (time) => {
        lenisInstance?.raf(time);
        lenisRafId = requestAnimationFrame(raf);
    };

    lenisRafId = requestAnimationFrame(raf);

    const handleHashScroll = (event) => {
        const anchor = event.target.closest('a[href^="#"]');
        if (!anchor) {
            return;
        }

        const targetId = anchor.getAttribute('href');
        if (!targetId || targetId === '#') {
            return;
        }

        const target = document.querySelector(targetId);
        if (!target) {
            return;
        }

        event.preventDefault();
        lenisInstance?.scrollTo(target, {
            offset: -24,
            duration: 1.1
        });
    };

    document.addEventListener('click', handleHashScroll);

    return () => {
        document.removeEventListener('click', handleHashScroll);
        destroyLenis();
    };
};

const initSplitHeadlines = (scope = document) => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return () => {};
    }

    destroySplitText();

    const targets = Array.from(scope.querySelectorAll(
        '.dashboard-title, .section-title, .flow-hero-title, .analytics-title, .record-title, .plan-focus-title, .topbar-title'
    )).filter((node) => node.textContent && node.textContent.trim().length > 0);

    if (!targets.length) {
        return () => {};
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            const split = splitInstances.find((instance) => instance.elements[0] === entry.target);
            if (!split) {
                observer.unobserve(entry.target);
                return;
            }

            gsap.fromTo(split.chars, {
                yPercent: 110,
                opacity: 0,
                rotateX: -28
            }, {
                yPercent: 0,
                opacity: 1,
                rotateX: 0,
                duration: 0.78,
                ease: 'power4.out',
                stagger: 0.014,
                clearProps: 'transform,opacity'
            });

            observer.unobserve(entry.target);
        });
    }, {
        rootMargin: '0px 0px -10% 0px',
        threshold: 0.18
    });

    targets.forEach((target) => {
        const split = new SplitType(target, {
            types: 'lines, words, chars'
        });

        splitInstances.push(split);
        gsap.set(split.chars, {
            opacity: 0,
            yPercent: 110,
            rotateX: -28,
            transformOrigin: '0% 100%'
        });
        observer.observe(target);
    });

    return () => {
        observer.disconnect();
        destroySplitText();
    };
};

const initGsapShell = () => {
    if (currentCleanup) {
        currentCleanup();
    }

    const scope = document;
    clearTransientStyles(scope);
    animateEntrances(scope);
    animateAmbientShell(scope);
    bindHoverAnimations(scope);
    bindPointerParallax(scope);
    const lenisCleanup = initLenis();
    const splitCleanup = initSplitHeadlines(scope);

    currentCleanup = () => {
        gsap.killTweensOf('.shell-glow-a, .shell-glow-b, .flow-hero-panel, .topbar-layout, .topbar-kicker-dot, .flow-kicker-dot');
        splitCleanup();
        lenisCleanup();
    };
};

document.addEventListener('DOMContentLoaded', initGsapShell);
document.addEventListener('turbo:load', initGsapShell);
