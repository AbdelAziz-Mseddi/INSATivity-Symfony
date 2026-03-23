export async function fetchClubById(clubId) {
  const response = await fetch(`../backend/clubs.php?action=get&id=${encodeURIComponent(clubId)}`, {
    method: 'GET'
  });

  const result = await response.json();
  if (!response.ok || result.status !== 'success' || !result.data) {
    throw new Error((result.errors && result.errors[0]) || 'Failed to load club information');
  }

  return result.data;
}

export async function fetchAllEvents() {
  const response = await fetch('../backend/events.php?action=getAll', {
    method: 'GET'
  });

  const result = await response.json();
  if (!response.ok || result.status !== 'success' || !Array.isArray(result.data)) {
    throw new Error((result.errors && result.errors[0]) || 'Failed to load events');
  }

  return result.data;
}

export async function uploadCoverImage(file, prefix) {
  const formData = new FormData();
  formData.append('file', file);

  const response = await fetch(`../backend/media.php?action=upload&prefix=${encodeURIComponent(prefix)}`, {
    method: 'POST',
    body: formData
  });

  const result = await response.json();
  if (!response.ok || result.status !== 'success') {
    throw new Error((result.errors && result.errors[0]) || 'Failed to upload image');
  }

  return result.data.path;
}

export async function createEvent(payload) {
  const response = await fetch('../backend/events.php?action=create', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });

  const result = await response.json();
  if (!response.ok || result.status !== 'success') {
    throw new Error((result.errors && result.errors[0]) || 'Failed to create event');
  }

  return result.data;
}
