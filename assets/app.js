const body = document.body;
const detailModal = document.querySelector('#detailModal');
const inquiryModal = document.querySelector('#inquiryModal');
const authModal = document.querySelector('#authModal');
const toast = document.querySelector('#toast');
let activeListing = null;
let toastTimer;

const formatRupiah = (number) => `Rp${new Intl.NumberFormat('id-ID').format(number)}`;

function showToast(message) {
  toast.textContent = message;
  toast.classList.add('visible');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('visible'), 2800);
}

function openModal(modal) {
  modal.hidden = false;
  body.classList.add('modal-open');
}

function closeModal(modal) {
  if (!modal) return;
  modal.hidden = true;
  if (detailModal.hidden && inquiryModal.hidden && authModal.hidden) body.classList.remove('modal-open');
}

function switchAuthTab(tab) {
  document.querySelectorAll('[data-auth-panel]').forEach((panel) => {
    panel.hidden = panel.dataset.authPanel !== tab;
  });
  document.querySelectorAll('[data-auth-switch]').forEach((button) => {
    button.classList.toggle('active', button.dataset.authSwitch === tab);
  });
}

function openAuth(tab = 'login') {
  switchAuthTab(tab);
  openModal(authModal);
}

function openDetail(listing) {
  activeListing = listing;
  document.querySelector('#modalImage').src = listing.image;
  document.querySelector('#modalImage').alt = listing.name;
  document.querySelector('#modalTag').textContent = listing.tag;
  document.querySelector('#modalTitle').textContent = listing.name;
  document.querySelector('#modalLocation').innerHTML = `<span>⌖</span> ${listing.location}`;
  document.querySelector('#modalDescription').textContent = listing.description;
  document.querySelector('#modalPrice').textContent = formatRupiah(listing.price);
  document.querySelector('#modalFeatures').innerHTML = listing.features.map((feature) => `<span>${feature}</span>`).join('');
  openModal(detailModal);
}

document.querySelectorAll('.detail-button').forEach((button) => {
  button.addEventListener('click', () => openDetail(JSON.parse(button.dataset.detail)));
});

document.querySelectorAll('.favorite-button').forEach((button) => {
  button.addEventListener('click', () => {
    const selected = button.getAttribute('aria-pressed') === 'true';
    button.setAttribute('aria-pressed', String(!selected));
    button.textContent = selected ? '♡' : '♥';
    showToast(selected ? 'Kos dihapus dari simpanan.' : 'Kos disimpan ke favoritmu.');
  });
});

document.querySelector('#openInquiry').addEventListener('click', () => {
  closeModal(detailModal);
  document.querySelector('#inquiryListing').value = activeListing?.name || '';
  document.querySelector('#inquiryTitle').textContent = `Tertarik dengan ${activeListing?.name || 'kos ini'}?`;
  openModal(inquiryModal);
});

document.querySelectorAll('.modal-close').forEach((button) => {
  button.addEventListener('click', () => closeModal(button.closest('.modal-backdrop')));
});

document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
  backdrop.addEventListener('click', (event) => {
    if (event.target === backdrop) closeModal(backdrop);
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeModal(detailModal);
    closeModal(inquiryModal);
    closeModal(authModal);
  }
});

const menuToggle = document.querySelector('.menu-toggle');
menuToggle.addEventListener('click', () => {
  const isOpen = document.querySelector('.site-header').classList.toggle('menu-open');
  menuToggle.setAttribute('aria-expanded', String(isOpen));
});

document.querySelectorAll('[data-toast]').forEach((button) => button.addEventListener('click', () => showToast(button.dataset.toast)));
document.querySelectorAll('[data-auth-tab]').forEach((button) => button.addEventListener('click', () => openAuth(button.dataset.authTab)));
document.querySelectorAll('[data-auth-switch]').forEach((button) => button.addEventListener('click', () => switchAuthTab(button.dataset.authSwitch)));

if (authModal.dataset.authResult === 'show') {
  openAuth(authModal.dataset.initialTab || 'login');
}

const searchInput = document.querySelector('#searchInput');
const typeFilter = document.querySelector('#typeFilter');
const priceFilter = document.querySelector('#priceFilter');
const resultCount = document.querySelector('#resultCount');
const emptyState = document.querySelector('#emptyState');
const cards = [...document.querySelectorAll('.listing-card')];

function filterListings() {
  const keyword = searchInput.value.trim().toLowerCase();
  const type = typeFilter.value;
  const maxPrice = priceFilter.value === 'all' ? Infinity : Number(priceFilter.value);
  let visible = 0;

  cards.forEach((card) => {
    const matchesKeyword = !keyword || card.dataset.name.includes(keyword);
    const matchesType = type === 'all' || card.dataset.type === type;
    const matchesPrice = Number(card.dataset.price) <= maxPrice;
    const show = matchesKeyword && matchesType && matchesPrice;
    card.hidden = !show;
    if (show) visible += 1;
  });

  resultCount.textContent = `${visible} kos ditemukan`;
  emptyState.hidden = visible !== 0;
}

document.querySelector('#searchButton').addEventListener('click', () => {
  filterListings();
  document.querySelector('#listings').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
[searchInput, typeFilter, priceFilter].forEach((field) => field.addEventListener('input', filterListings));
document.querySelector('#resetFilters').addEventListener('click', () => {
  searchInput.value = '';
  typeFilter.value = 'all';
  priceFilter.value = 'all';
  filterListings();
});

let sortAscending = false;
document.querySelector('#sortButton').addEventListener('click', () => {
  sortAscending = !sortAscending;
  const grid = document.querySelector('#listingGrid');
  cards.sort((a, b) => {
    const first = Number(a.dataset.price);
    const second = Number(b.dataset.price);
    return sortAscending ? first - second : second - first;
  }).forEach((card) => grid.appendChild(card));
  showToast(sortAscending ? 'Diurutkan dari harga termurah.' : 'Diurutkan dari harga termahal.');
});
