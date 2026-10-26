const body = document.body;
const detailModal = document.querySelector('#detailModal');
const inquiryModal = document.querySelector('#inquiryModal');
const toast = document.querySelector('#toast');
const apiAlert = document.querySelector('#apiAlert');
let activeListing = null;
let listings = [];
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
  if (detailModal.hidden && inquiryModal.hidden) body.classList.remove('modal-open');
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

function renderListings() {
  const grid = document.querySelector('#listingGrid');
  grid.innerHTML = listings.map((listing) => `
    <article class="listing-card" data-name="${escapeHtml(`${listing.name} ${listing.location}`.toLowerCase())}" data-type="${escapeHtml(listing.type)}" data-price="${listing.price}">
      <div class="listing-image-wrap"><img src="${escapeHtml(listing.image)}" alt="${escapeHtml(listing.name)}" loading="lazy"><span class="listing-tag">${escapeHtml(listing.tag)}</span><button class="favorite-button" type="button" aria-label="Simpan ${escapeHtml(listing.name)}" aria-pressed="false">♡</button></div>
      <div class="listing-content"><div class="listing-title-row"><h3>${escapeHtml(listing.name)}</h3><span class="rating"><span>★</span> ${Number(listing.rating).toFixed(1)}</span></div><p class="listing-location"><span>⌖</span> ${escapeHtml(listing.location)}</p><div class="feature-list">${listing.features.map((feature) => `<span>${escapeHtml(feature)}</span>`).join('')}</div><div class="listing-footer"><div><strong>${formatRupiah(listing.price)}</strong><small>/${escapeHtml(listing.period)}</small></div><button class="detail-button" type="button" data-listing-id="${listing.id}">Lihat detail <span>→</span></button></div></div>
    </article>
  `).join('');
  bindListingActions();
  filterListings();
}

function escapeHtml(value) {
  return String(value).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
}

function bindListingActions() {
  document.querySelectorAll('.detail-button').forEach((button) => button.addEventListener('click', () => {
    const listing = listings.find((item) => String(item.id) === button.dataset.listingId);
    if (listing) openDetail(listing);
  }));
  document.querySelectorAll('.favorite-button').forEach((button) => button.addEventListener('click', () => {
    const selected = button.getAttribute('aria-pressed') === 'true';
    button.setAttribute('aria-pressed', String(!selected));
    button.textContent = selected ? '♡' : '♥';
    showToast(selected ? 'Kos dihapus dari simpanan.' : 'Kos disimpan ke favoritmu.');
  }));
}

function filterListings() {
  const searchInput = document.querySelector('#searchInput');
  const typeFilter = document.querySelector('#typeFilter');
  const priceFilter = document.querySelector('#priceFilter');
  const keyword = searchInput.value.trim().toLowerCase();
  const type = typeFilter.value;
  const maxPrice = priceFilter.value === 'all' ? Infinity : Number(priceFilter.value);
  let visible = 0;

  document.querySelectorAll('.listing-card').forEach((card) => {
    const show = (!keyword || card.dataset.name.includes(keyword)) && (type === 'all' || card.dataset.type === type) && Number(card.dataset.price) <= maxPrice;
    card.hidden = !show;
    if (show) visible += 1;
  });
  document.querySelector('#resultCount').textContent = `${visible} kos ditemukan`;
  document.querySelector('#emptyState').hidden = visible !== 0;
}

async function loadListings() {
  try {
    const response = await fetch('/api/listings');
    if (!response.ok) throw new Error('API listings gagal.');
    listings = await response.json();
    renderListings();
  } catch (error) {
    console.error(error);
    apiAlert.hidden = false;
    document.querySelector('#resultCount').textContent = 'Kos belum tersedia';
  }
}

async function loadSession() {
  try {
    const response = await fetch('/api/auth/me');
    const data = await response.json();
    if (data.user) {
      const actions = document.querySelector('#authActions');
      actions.innerHTML = `<span class="user-greeting">Hai, ${escapeHtml(data.user.name)}</span><button class="outline-button" id="logoutButton" type="button">Keluar</button>`;
      document.querySelector('#logoutButton').addEventListener('click', async () => {
        await fetch('/api/auth/logout', { method: 'POST' });
        window.location.reload();
      });
    }
  } catch (error) {
    console.warn('Session check skipped', error);
  }
}

document.querySelector('#openInquiry').addEventListener('click', () => {
  closeModal(detailModal);
  document.querySelector('#inquiryListing').value = activeListing?.name || '';
  document.querySelector('#inquiryTitle').textContent = `Tertarik dengan ${activeListing?.name || 'kos ini'}?`;
  openModal(inquiryModal);
});

document.querySelector('#inquiryForm').addEventListener('submit', async (event) => {
  event.preventDefault();
  const errorBox = document.querySelector('#inquiryError');
  errorBox.hidden = true;
  try {
    const response = await fetch('/api/inquiries', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(Object.fromEntries(new FormData(event.target))) });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Inquiry gagal dikirim.');
    closeModal(inquiryModal);
    event.target.reset();
    showToast('Permintaanmu sudah terkirim.');
  } catch (error) {
    errorBox.textContent = error.message;
    errorBox.hidden = false;
  }
});

document.querySelectorAll('.modal-close').forEach((button) => button.addEventListener('click', () => closeModal(button.closest('.modal-backdrop'))));
document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.addEventListener('click', (event) => { if (event.target === backdrop) closeModal(backdrop); }));
document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { closeModal(detailModal); closeModal(inquiryModal); } });

const menuToggle = document.querySelector('.menu-toggle');
menuToggle.addEventListener('click', () => {
  const isOpen = document.querySelector('.site-header').classList.toggle('menu-open');
  menuToggle.setAttribute('aria-expanded', String(isOpen));
});
document.querySelectorAll('[data-toast]').forEach((button) => button.addEventListener('click', () => showToast(button.dataset.toast)));
const searchInput = document.querySelector('#searchInput');
const typeFilter = document.querySelector('#typeFilter');
const priceFilter = document.querySelector('#priceFilter');
[searchInput, typeFilter, priceFilter].forEach((field) => field.addEventListener('input', filterListings));
document.querySelector('#searchButton').addEventListener('click', () => { filterListings(); document.querySelector('#listings').scrollIntoView({ behavior: 'smooth', block: 'start' }); });
document.querySelector('#resetFilters').addEventListener('click', () => { searchInput.value = ''; typeFilter.value = 'all'; priceFilter.value = 'all'; filterListings(); });

let sortAscending = false;
document.querySelector('#sortButton').addEventListener('click', () => {
  sortAscending = !sortAscending;
  listings.sort((a, b) => sortAscending ? a.price - b.price : b.price - a.price);
  renderListings();
  showToast(sortAscending ? 'Diurutkan dari harga termurah.' : 'Diurutkan dari harga termahal.');
});

loadListings();
loadSession();
