/* ============================================================
   SUMMIT ASSESSORIA — app.js
   ============================================================ */

'use strict';

// ─── DOM READY ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  // ── Video Background Loader ───────────────────────────────
  const heroBg = document.querySelector('.hero-bg');
  if (heroBg) {
    const video = document.createElement('video');
    video.src = 'assets/video-background-hero.mp4';
    video.autoplay = true;
    video.loop = true;
    video.muted = true;
    video.setAttribute('playsinline', '');
    video.className = 'hero-video-bg';
    
    // Replace placeholder with video
    const placeholder = heroBg.querySelector('.hero-img-placeholder');
    if (placeholder) {
      heroBg.replaceChild(video, placeholder);
    } else {
      heroBg.prepend(video);
    }

    // Play video programmatically
    video.play().catch(err => {
      console.warn("Video background failed to autoplay:", err);
    });
  }

  // ── Navbar scroll effect ──────────────────────────────────
  const navbar = document.getElementById('navbar');

  const handleScroll = () => {
    navbar.classList.toggle('scrolled', window.scrollY > 60);
  };

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll(); // run once on load


  // ── Mobile hamburger menu ──────────────────────────────────
  const hamburger  = document.getElementById('hamburger');
  const navLinks   = document.getElementById('nav-links');
  
  if (hamburger && navLinks) {
    const navAnchors = navLinks.querySelectorAll('a');

    hamburger.addEventListener('click', () => {
      const isOpen = navLinks.classList.toggle('open');
      hamburger.classList.toggle('open', isOpen);
      hamburger.setAttribute('aria-expanded', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    navAnchors.forEach(a => {
      a.addEventListener('click', () => {
        navLinks.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }


  // ── Scroll reveal (IntersectionObserver) ──────────────────
  const reveals = document.querySelectorAll('.reveal, .card');

  const revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;

        const el    = entry.target;
        const delay = el.dataset.delay ? parseInt(el.dataset.delay, 10) : 0;

        setTimeout(() => {
          el.classList.add('visible');
        }, delay);

        revealObserver.unobserve(el);
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  reveals.forEach(el => revealObserver.observe(el));


  // ── Apply .reveal to main sections ──────────────────────────
  const revealTargets = document.querySelectorAll(
    '.statement-grid > *, .sobre-text, .sobre-img, .faq-left, .contato-form-area, .contato-img'
  );
  revealTargets.forEach(el => {
    if (!el.classList.contains('reveal')) {
      el.classList.add('reveal');
      revealObserver.observe(el);
    }
  });


  // ── FAQ accordion ──────────────────────────────────────────
  const faqItems = document.querySelectorAll('.faq-item');

  faqItems.forEach(item => {
    const btn = item.querySelector('.faq-q');

    btn.addEventListener('click', () => {
      const isActive = item.classList.contains('active');

      // Close all
      faqItems.forEach(i => {
        i.classList.remove('active');
        i.querySelector('.faq-q').setAttribute('aria-expanded', 'false');
      });

      // Open clicked (toggle)
      if (!isActive) {
        item.classList.add('active');
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });


  // ── Contact form validation & fake submit ──────────────────
  const form        = document.getElementById('contact-form');
  const successMsg  = document.getElementById('form-success');

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const fields = form.querySelectorAll('[required]');
      let valid = true;

      fields.forEach(field => {
        field.classList.remove('error');

        const isEmpty = field.value.trim() === '';
        const isEmail = field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value.trim());

        if (isEmpty || isEmail) {
          field.classList.add('error');
          valid = false;
        }
      });

      if (!valid) {
        const firstError = form.querySelector('.error');
        firstError?.focus();
        return;
      }

      // SUCCESS state
      form.style.display   = 'none';
      successMsg.classList.add('show');

      // Reset after 6s (optional UX)
      setTimeout(() => {
        form.reset();
        form.style.display   = '';
        successMsg.classList.remove('show');
      }, 6000);
    });

    // Remove error on input
    form.querySelectorAll('input, textarea').forEach(field => {
      field.addEventListener('input', () => field.classList.remove('error'));
    });
  }


  // ── Dynamic footer year ────────────────────────────────────
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();


  // ── Smooth active nav link on scroll ──────────────────────
  const sections    = document.querySelectorAll('section[id]');
  const linkMap     = {};
  document.querySelectorAll('.nav-links a[href^="#"]').forEach(a => {
    linkMap[a.getAttribute('href').slice(1)] = a;
  });

  const sectionObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const id = entry.target.id;
        Object.values(linkMap).forEach(a => a.removeAttribute('data-active'));
        if (linkMap[id]) linkMap[id].dataset.active = 'true';
      });
    },
    { threshold: 0.35 }
  );

  sections.forEach(s => sectionObserver.observe(s));

});
