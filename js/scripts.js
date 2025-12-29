/*
 * CGR Advanced Theme JavaScript
 *
 * Provides simple interactivity.  Currently implements a sticky
 * header effect that shrinks the header or adds a shadow once
 * scrolled.  Extend this file to add animations, AJAX form
 * submissions or other behaviour.
 */
(function($){
    $(document).ready(function(){
        var $header = $('.site-header');

        var $menuToggle = $('.menu-toggle');
        var $navMenu = $('#primary-menu');
        var $navDrawer = $('.menu-wrapper');

        /* 
         * Sticky header logic is now handled in header.php inline script 
         * to support different thresholds for Home vs Inner pages.
         */
        /*
        $(window).on('scroll', function(){
            if ($(this).scrollTop() > 50) {
                $header.addClass('scrolled');
            } else {
                $header.removeClass('scrolled');
            }
        });
        */

        // Mobile menu toggle
        $menuToggle.on('click', function() {
            $('body').toggleClass('nav-open');
            $navMenu.toggleClass('toggled-on');
            $navDrawer.toggleClass('is-open');

            var isExpanded = $(this).attr('aria-expanded') === 'true';
            $(this).attr('aria-expanded', !isExpanded);
        });

        $('#primary-menu a, .nav-login').on('click', function(){
            if ($navMenu.hasClass('toggled-on')) {
                $('body').removeClass('nav-open');
                $navMenu.removeClass('toggled-on');
                $navDrawer.removeClass('is-open');
                $menuToggle.attr('aria-expanded', false);
            }
        });

        $(window).on('resize', function(){
            if ($(this).width() > 1024) {
                $('body').removeClass('nav-open');
                $navMenu.removeClass('toggled-on');
                $navDrawer.removeClass('is-open');
            }
        });

        // --- IMPACT SECTION ANIMATIONS ---
        // Using IntersectionObserver for better performance and reliability
        const impactSection = document.querySelector('.cgr-impact-section');
        if (impactSection) {
            const impactObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        startImpactAnimations();
                        impactObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });
            
            impactObserver.observe(impactSection);
        }

        function startImpactAnimations() {
            const impactCards = document.querySelectorAll('.impact-card');
            const impactCounters = document.querySelectorAll('.impact-counter');
            
            if (impactCards.length > 0) {
                impactCards.forEach((card, index) => {
                    setTimeout(() => {
                        card.classList.add('is-visible');
                    }, index * 200);
                });
            }

            if (impactCounters.length > 0) {
                impactCounters.forEach((counter, index) => {
                    setTimeout(() => {
                        const target = parseFloat(counter.getAttribute('data-target'));
                        if (isNaN(target)) return;

                        const duration = 2000;
                        const startTime = performance.now();
                        
                        const updateCount = (currentTime) => {
                            const elapsed = currentTime - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            const ease = 1 - Math.pow(1 - progress, 4); // Ease out quart
                            const current = target * ease;
                            
                            if (target % 1 !== 0) {
                                counter.innerText = current.toFixed(1);
                            } else {
                                counter.innerText = Math.floor(current);
                            }
                            
                            if (progress < 1) {
                                requestAnimationFrame(updateCount);
                            } else {
                                counter.innerText = target;
                            }
                        };
                        requestAnimationFrame(updateCount);
                    }, index * 300);
                });
            }
        }

        // --- PROGRAMS SECTION ANIMATIONS ---
        const programElements = document.querySelectorAll('.programs-header, .program-card, .programs-actions, .events-sidebar');

        if (programElements.length > 0) {
            const programObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        if(entry.target.classList.contains('program-card')) {
                            const cards = Array.from(document.querySelectorAll('.program-card'));
                            const index = cards.indexOf(entry.target);
                            setTimeout(() => {
                                entry.target.classList.add('is-visible');
                            }, (index % 3) * 150);
                        } else {
                            entry.target.classList.add('is-visible');
                        }
                        programObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            programElements.forEach(el => programObserver.observe(el));
        }

    });
})(jQuery);

// Vanilla JS for Menu Toggle (Robustness)
document.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.querySelector('.menu-toggle');
    var navDrawer = document.querySelector('.menu-wrapper');
    var body = document.body;

    if (menuToggle && navDrawer) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', !isExpanded);
            
            navDrawer.classList.toggle('is-open');
            body.classList.toggle('nav-open');
        });

        // Close on link click
        var links = navDrawer.querySelectorAll('a');
        links.forEach(function(link) {
            link.addEventListener('click', function() {
                navDrawer.classList.remove('is-open');
                body.classList.remove('nav-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }
});
