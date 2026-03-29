let allClubs = [];
let selectedCategory = 'All';

// Fetch clubs data from JSON
async function loadClubs() {
  try {
    const response = await fetch("../backend/clubs.php?action=getAll",{ method: 'GET'});
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    
    if (result.status === 'success' && result.data) {
      allClubs = result.data;
      renderClubs(allClubs);
      setupFilterButtons();
    } else {
      console.error("Error from API:", result.errors || result.message);
    }
  } catch (error) {
    console.error("Error loading events from API:", error);
  }
}

// Render clubs to the page
function renderClubs(clubs) {
  const clubsContainer = document.getElementById('clubs-container');
  
  if (!clubsContainer) {
    console.error('clubs-container element not found');
    return;
  }

  clubsContainer.innerHTML = '';

  clubs.forEach((club) => {
    const clubCard = document.createElement('div');
    clubCard.className = 'col-md-6 col-lg-4';
    clubCard.innerHTML = `
      <div class="card-club">
        <div class="card-img-wrapper">
          <span class="category-badge">${club.category}</span>
          <img
            src="${club.banner}"
            alt="${club.name} club"
            class="card-img-top"
          />
        </div>
        <div class="card-body">
          <div class="club-title">${club.name}</div>
          <p class="club-desc">${club.description}</p>
          <div class="card-footer-custom">
            <div class="d-flex gap-2">
              <a href="club-dashboard.html?club=${encodeURIComponent(club.id)}" class="btn-view">View Club</a>
              <button class="btn-join">Join</button>
            </div>
          </div>
        </div>
      </div>
    `;
    clubsContainer.appendChild(clubCard);
  });
}

// Setup filter buttons
function setupFilterButtons() {
  const filterButtons = document.querySelectorAll('.filter-btn');

  filterButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
      // Remove active class from all buttons
      filterButtons.forEach((btn) => btn.classList.remove('btn-dark'));
      filterButtons.forEach((btn) => btn.classList.add('bg-white', 'border'));

      // Add active class to clicked button
      button.classList.add('btn-dark');
      button.classList.remove('bg-white', 'border');

      const category = button.getAttribute('data-category');
      selectedCategory = category;

      if (category === 'All') {
        renderClubs(allClubs);
      } else {
        const filteredClubs = allClubs.filter(
          (club) => club.category === category
        );
        renderClubs(filteredClubs);
      }
    });
  });
}

// Load clubs when the page loads
document.addEventListener('DOMContentLoaded', loadClubs);
