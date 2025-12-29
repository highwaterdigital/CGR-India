document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lightbox
    const lightboxLinks = document.querySelectorAll('a[data-lightbox]');
    if (lightboxLinks.length === 0) return;

    // Create Lightbox DOM
    const lightbox = document.createElement('div');
    lightbox.className = 'cgr-lightbox-overlay';
    lightbox.innerHTML = `
        <button class="cgr-lightbox-close" aria-label="Close"><span class="cgr-lightbox-icon"></span></button>
        <button class="cgr-lightbox-prev" aria-label="Previous"><span class="cgr-lightbox-arrow"></span></button>
        <button class="cgr-lightbox-next" aria-label="Next"><span class="cgr-lightbox-arrow"></span></button>
        <div class="cgr-lightbox-toolbar" aria-label="Zoom controls">
            <button class="cgr-lightbox-zoom-out" aria-label="Zoom out"><span class="cgr-lightbox-zoom-icon">-</span></button>
            <button class="cgr-lightbox-zoom-reset" aria-label="Reset zoom"><span class="cgr-lightbox-zoom-icon">1x</span></button>
            <button class="cgr-lightbox-zoom-in" aria-label="Zoom in"><span class="cgr-lightbox-zoom-icon">+</span></button>
        </div>
        <button class="cgr-lightbox-play" aria-label="Play Slideshow"><span class="cgr-lightbox-play-icon"></span></button>
        <div class="cgr-lightbox-content">
            <img src="" class="cgr-lightbox-image" alt="">
            <div class="cgr-lightbox-caption"></div>
        </div>
    `;
    document.body.appendChild(lightbox);

    // Elements
    const img = lightbox.querySelector('.cgr-lightbox-image');
    const caption = lightbox.querySelector('.cgr-lightbox-caption');
    const closeBtn = lightbox.querySelector('.cgr-lightbox-close');
    const prevBtn = lightbox.querySelector('.cgr-lightbox-prev');
    const nextBtn = lightbox.querySelector('.cgr-lightbox-next');
    const playBtn = lightbox.querySelector('.cgr-lightbox-play');
    const zoomInBtn = lightbox.querySelector('.cgr-lightbox-zoom-in');
    const zoomOutBtn = lightbox.querySelector('.cgr-lightbox-zoom-out');
    const zoomResetBtn = lightbox.querySelector('.cgr-lightbox-zoom-reset');

    let currentGroup = [];
    let currentIndex = 0;
    let slideshowInterval = null;
    let isPlaying = false;
    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let dragPointerId = null;
    let dragStartX = 0;
    let dragStartY = 0;
    let pinchStartDistance = null;
    let pinchStartScale = 1;
    let lastTapTime = 0;

    const MIN_SCALE = 1;
    const MAX_SCALE = 3.5;
    const SCALE_STEP = 0.25;

    // Open Lightbox
    lightboxLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const groupName = this.getAttribute('data-lightbox');
            currentGroup = Array.from(document.querySelectorAll(`a[data-lightbox="${groupName}"]`));
            currentIndex = currentGroup.indexOf(this);
            updateLightbox();
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
            stopSlideshow(); // Ensure slideshow is stopped when opening
        });
    });

    // Update Image
    function updateLightbox() {
        const link = currentGroup[currentIndex];
        img.src = link.href;
        caption.textContent = link.getAttribute('title') || '';
        resetZoom();
        
        // Handle navigation visibility
        if (currentGroup.length > 1) {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
            playBtn.style.display = 'flex';
        } else {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
            playBtn.style.display = 'none';
        }
    }

    // Navigation
    function showNext() {
        currentIndex = (currentIndex + 1) % currentGroup.length;
        updateLightbox();
    }

    function showPrev() {
        currentIndex = (currentIndex - 1 + currentGroup.length) % currentGroup.length;
        updateLightbox();
    }

    // Slideshow Logic
    function toggleSlideshow() {
        if (isPlaying) {
            stopSlideshow();
        } else {
            startSlideshow();
        }
    }

    function startSlideshow() {
        isPlaying = true;
        playBtn.classList.add('playing');
        playBtn.setAttribute('aria-label', 'Pause Slideshow');
        slideshowInterval = setInterval(showNext, 3000); // Change slide every 3 seconds
    }

    function stopSlideshow() {
        isPlaying = false;
        playBtn.classList.remove('playing');
        playBtn.setAttribute('aria-label', 'Play Slideshow');
        if (slideshowInterval) {
            clearInterval(slideshowInterval);
            slideshowInterval = null;
        }
    }

    // Zoom logic
    function applyTransform() {
        img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        img.style.cursor = scale > 1.01 ? (isDragging ? 'grabbing' : 'grab') : 'default';
        lightbox.classList.toggle('cgr-lightbox-zoomed', scale > 1.01);
    }

    function setScale(nextScale) {
        const bounded = Math.min(MAX_SCALE, Math.max(MIN_SCALE, nextScale));
        scale = bounded;
        if (scale === MIN_SCALE) {
            translateX = 0;
            translateY = 0;
        }
        applyTransform();
    }

    function resetZoom() {
        scale = 1;
        translateX = 0;
        translateY = 0;
        applyTransform();
    }

    function zoomBy(delta) {
        setScale(scale + delta);
    }

    function toggleZoomAtPointer(pointerEvent) {
        if (scale > 1.01) {
            resetZoom();
        } else {
            setScale(2);
        }
    }

    // Pointer drag for panning
    img.addEventListener('pointerdown', function(e) {
        if (scale <= 1.01) return;
        isDragging = true;
        dragPointerId = e.pointerId;
        dragStartX = e.clientX - translateX;
        dragStartY = e.clientY - translateY;
        img.setPointerCapture(e.pointerId);
        applyTransform();
    });

    img.addEventListener('pointermove', function(e) {
        if (!isDragging || e.pointerId !== dragPointerId) return;
        translateX = e.clientX - dragStartX;
        translateY = e.clientY - dragStartY;
        applyTransform();
    });

    function endDrag(e) {
        if (e && e.pointerId !== dragPointerId) return;
        isDragging = false;
        dragPointerId = null;
        applyTransform();
    }

    img.addEventListener('pointerup', endDrag);
    img.addEventListener('pointercancel', endDrag);
    img.addEventListener('pointerleave', endDrag);

    // Wheel zoom
    lightbox.addEventListener('wheel', function(e) {
        if (!lightbox.classList.contains('active')) return;
        if (Math.abs(e.deltaY) < 1) return;
        const factor = e.deltaY > 0 ? 0.9 : 1.1;
        const next = scale * factor;
        setScale(next);
        if (scale > 1.01) {
            e.preventDefault();
        }
    }, { passive: false });

    // Double click / double tap zoom toggle
    img.addEventListener('dblclick', function(e) {
        e.preventDefault();
        toggleZoomAtPointer(e);
    });

    img.addEventListener('touchend', function(e) {
        if (e.touches.length > 0) return;
        const now = Date.now();
        if (now - lastTapTime < 300) {
            toggleZoomAtPointer(e);
        }
        lastTapTime = now;
    });

    // Pinch zoom on touch
    img.addEventListener('touchmove', function(e) {
        if (e.touches.length === 2) {
            e.preventDefault();
            const dist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            if (pinchStartDistance === null) {
                pinchStartDistance = dist;
                pinchStartScale = scale;
            } else {
                const scaleFactor = dist / pinchStartDistance;
                setScale(pinchStartScale * scaleFactor);
            }
        }
    }, { passive: false });

    img.addEventListener('touchend', function(e) {
        if (e.touches.length < 2) {
            pinchStartDistance = null;
        }
    });

    img.addEventListener('touchcancel', function() {
        pinchStartDistance = null;
    });

    // Zoom button interactions
    zoomInBtn.addEventListener('click', function() {
        zoomBy(SCALE_STEP);
    });

    zoomOutBtn.addEventListener('click', function() {
        zoomBy(-SCALE_STEP);
    });

    zoomResetBtn.addEventListener('click', function() {
        resetZoom();
    });

    nextBtn.addEventListener('click', () => {
        showNext();
        stopSlideshow(); // Stop slideshow on manual interaction
    });
    prevBtn.addEventListener('click', () => {
        showPrev();
        stopSlideshow(); // Stop slideshow on manual interaction
    });
    playBtn.addEventListener('click', toggleSlideshow);

    // Close
    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
        stopSlideshow();
        setTimeout(() => {
            img.src = ''; // Clear src to stop loading
        }, 300);
    }

    closeBtn.addEventListener('click', closeLightbox);
    
    // Close on background click
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    // Keyboard support
    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;
        
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') {
            showNext();
            stopSlideshow();
        }
        if (e.key === 'ArrowLeft') {
            showPrev();
            stopSlideshow();
        }
        if (e.key === ' ') { // Spacebar to toggle play/pause
            e.preventDefault();
            toggleSlideshow();
        }
    });
});
