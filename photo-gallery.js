(() => {
  const header = document.getElementById('header');
  const lightbox = document.getElementById('lightbox');
  const cards = Array.from(document.querySelectorAll('#galleryGrid .gallery-card'));
  const image = document.getElementById('lightboxImg');
  const title = document.getElementById('lightboxTitle');
  const description = document.getElementById('lightboxDesc');
  const counter = document.getElementById('lightboxCounter');
  let activeIndex = 0;
  let opener = null;
  let previousOverflow = '';

  const updateHeader = () => header.classList.toggle('scrolled', window.scrollY > 50);
  window.addEventListener('scroll', updateHeader, { passive: true });
  updateHeader();

  function showPhoto(index) {
    activeIndex = (index + cards.length) % cards.length;
    const card = cards[activeIndex];
    image.src = card.querySelector('.photo-open').getAttribute('href');
    image.alt = card.querySelector('img').alt;
    title.textContent = card.querySelector('.card-title').textContent;
    description.textContent = card.querySelector('.card-desc').textContent;
    counter.textContent = `${activeIndex + 1} / ${cards.length}`;
  }

  cards.forEach((card, index) => {
    card.querySelector('.photo-open').addEventListener('click', (event) => {
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      opener = event.currentTarget;
      previousOverflow = document.body.style.overflow;
      showPhoto(index);
      lightbox.showModal();
      document.body.style.overflow = 'hidden';
    });
  });

  lightbox.querySelector('.lightbox-close').addEventListener('click', () => lightbox.close());
  lightbox.querySelector('.lightbox-prev').addEventListener('click', () => showPhoto(activeIndex - 1));
  lightbox.querySelector('.lightbox-next').addEventListener('click', () => showPhoto(activeIndex + 1));
  lightbox.addEventListener('click', (event) => {
    if (event.target === lightbox) lightbox.close();
  });
  lightbox.addEventListener('close', () => {
    document.body.style.overflow = previousOverflow;
    opener?.focus({ preventScroll: true });
  });
  lightbox.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault();
      showPhoto(activeIndex + (event.key === 'ArrowLeft' ? -1 : 1));
    }
  });
})();
