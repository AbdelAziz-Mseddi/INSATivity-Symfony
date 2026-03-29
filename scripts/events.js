// Event data - loaded from PHP API
let eventsData = [];

// Load events from PHP API
async function loadEvents() {
  try {
    const response = await fetch("../backend/events.php?action=getAll",{ method: 'GET'});
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    
    if (result.status === 'success' && result.data) {
      eventsData = result.data;
      renderFeaturedEvents();
      renderUpcomingEvents();
    } else {
      console.error("Error from API:", result.errors || result.message);
    }
  } catch (error) {
    console.error("Error loading events from API:", error);
  }
}

// Render featured events to the DOM
function renderFeaturedEvents() {
  const featuredGrid = document.querySelector(".featured-grid");
  if (!featuredGrid) return;

  // Clear existing events
  featuredGrid.innerHTML = "";

  // Filter and render featured events
  const featured = eventsData.filter((event) => event.featured);

  featured.forEach((event) => {
    const eventCard = document.createElement("div");
    eventCard.className = "event-card-featured";
    eventCard.innerHTML = `
      <img src="${event.image}" class="featured-bg" alt="${event.title}" />
      <div class="featured-overlay">
        <div class="club-tag">
          <img src="${event.clubLogo}" alt="${event.club} logo" /> ${event.club}
        </div>
        <h3>${event.title}</h3>
      </div>
    `;
    eventCard.addEventListener("click", () => viewEvent(event.id));
    featuredGrid.appendChild(eventCard);
  });
}

// Render upcoming events to the DOM
function renderUpcomingEvents() {
  const upcomingSection = document.querySelector(".upcoming-section");
  if (!upcomingSection) return;

  let eventsContainer = document.querySelector(".events-container");
  if (!eventsContainer) {
    eventsContainer = document.createElement("div");
    eventsContainer.className = "events-container";
    upcomingSection.appendChild(eventsContainer);
  }

  // Clear existing events
  eventsContainer.innerHTML = "";

  // Get current date (midnight) for comparison
  const now = new Date();
  now.setHours(0, 0, 0, 0);

  // Filter events that haven't happened yet and sort by date
  const upcoming = [...eventsData]
    .filter((event) => new Date(event.date) >= now)
    .sort((a, b) => new Date(a.date) - new Date(b.date));

  upcoming.forEach((event) => {
    const eventCard = document.createElement("div");
    eventCard.className = "small-event-card";
    eventCard.innerHTML = `
      <div class="thumb">
        <img src="${event.image}" alt="${event.title}" />
      </div>
      <div class="small-card-content">
        <div class="club-tag">
          <img src="${event.clubLogo}" alt="${event.club} logo" /> ${event.club}
        </div>
        <h4>${event.title}</h4>
        <p><i class="far fa-calendar"></i> ${formatDate(event.date)} at ${event.time}</p>
        <p><i class="far fa-map-pin"></i> ${event.location}</p>
      </div>
    `;
    eventCard.addEventListener("click", () => viewEvent(event.id));
    eventsContainer.appendChild(eventCard);
  });
}

// Format date for display
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

// View event details
function viewEvent(eventId) {
  const event = eventsData.find((e) => e.id === eventId);
  if (!event) return;

  // Store event in sessionStorage for the event detail page
  sessionStorage.setItem("currentEvent", JSON.stringify(event));
  // Navigate to event page
  window.location.href = "event.html";
}

// Add a new event
async function addEvent(eventObj) {
  try {
    const response = await fetch("../backend/events.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(eventObj),
    });

    const result = await response.json();
    if (!response.ok || result.status !== "success") {
      throw new Error((result.errors && result.errors[0]) || "Failed to create event");
    }

    await loadEvents();
    return result.data;
  } catch (error) {
    console.error("Error creating event:", error);
    return null;
  }
}

// Remove an event by ID
async function removeEvent(eventId) {
  try {
    const response = await fetch(`../backend/events.php?id=${encodeURIComponent(eventId)}`, {
      method: "DELETE",
    });

    const result = await response.json();
    if (!response.ok || result.status !== "success") {
      throw new Error((result.errors && result.errors[0]) || "Failed to delete event");
    }

    await loadEvents();
    return true;
  } catch (error) {
    console.error("Error deleting event:", error);
    return false;
  }
}

// Update an event
async function updateEvent(eventId, updates) {
  try {
    const response = await fetch(`../backend/events.php?id=${encodeURIComponent(eventId)}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(updates),
    });

    const result = await response.json();
    if (!response.ok || result.status !== "success") {
      throw new Error((result.errors && result.errors[0]) || "Failed to update event");
    }

    await loadEvents();
    return result.data;
  } catch (error) {
    console.error("Error updating event:", error);
    return null;
  }
}

// Get all events
function getAllEvents() {
  return [...eventsData];
}

// Get event by ID
function getEventById(eventId) {
  return eventsData.find((e) => e.id === eventId);
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  loadEvents();
});
