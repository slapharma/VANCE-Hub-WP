/*
 * User Guide page (page-user-guide.php) behaviour: scroll-reveal, scrollspy
 * sub-nav, and a lightweight lightbox for screenshots/GIFs. Conditionally
 * enqueued via is_page_template('page-user-guide.php') — inert elsewhere.
 */
document.addEventListener('DOMContentLoaded', function () {
	// --- Scroll-reveal ---
	var revealEls = document.querySelectorAll('.vug-reveal');
	if ('IntersectionObserver' in window && revealEls.length) {
		var revealObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					revealObserver.unobserve(entry.target);
				}
			});
		}, { threshold: 0.15 });
		revealEls.forEach(function (el) { revealObserver.observe(el); });
	} else {
		revealEls.forEach(function (el) { el.classList.add('is-visible'); });
	}

	// --- Scrollspy sub-nav ---
	var subnavLinks = document.querySelectorAll('.vug-subnav a[href^="#"]');
	var sections = [];
	subnavLinks.forEach(function (link) {
		var section = document.querySelector(link.getAttribute('href'));
		if (section) { sections.push({ link: link, section: section }); }
	});
	if ('IntersectionObserver' in window && sections.length) {
		var subnavHeight = (document.querySelector('.vug-subnav') || {}).offsetHeight || 60;
		var spy = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				var match = sections.find(function (s) { return s.section === entry.target; });
				if (!match) return;
				if (entry.isIntersecting) {
					subnavLinks.forEach(function (l) { l.classList.remove('is-active'); });
					match.link.classList.add('is-active');
				}
			});
		}, { rootMargin: '-' + (subnavHeight + 10) + 'px 0px -70% 0px' });
		sections.forEach(function (s) { spy.observe(s.section); });
	}

	// --- Lightbox ---
	var lightbox = document.getElementById('vug-lightbox');
	var lightboxImg = document.getElementById('vug-lightbox__img');
	var lightboxClose = lightbox ? lightbox.querySelector('.vug-lightbox__close') : null;
	if (lightbox && lightboxImg) {
		document.querySelectorAll('[data-vug-lightbox]').forEach(function (fig) {
			fig.addEventListener('click', function (e) {
				// Tool cards nest a real "Open <tool> →" link — let that navigate
				// instead of also popping the lightbox on the same click.
				if (e.target.closest('a')) return;
				var img = fig.querySelector('img');
				if (!img) return;
				lightboxImg.src = img.src;
				lightboxImg.alt = img.alt || '';
				lightbox.hidden = false;
				document.body.style.overflow = 'hidden';
			});
		});
		function closeLightbox() {
			lightbox.hidden = true;
			lightboxImg.src = '';
			document.body.style.overflow = '';
		}
		lightbox.addEventListener('click', closeLightbox);
		if (lightboxClose) {
			lightboxClose.addEventListener('click', function (e) { e.stopPropagation(); closeLightbox(); });
		}
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !lightbox.hidden) closeLightbox();
		});
	}
});
