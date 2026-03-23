document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.querySelector('.sidebar');
  const panels = Array.from(document.querySelectorAll('[data-panel]'));
  const createForm = document.querySelector('.event-create-form');

  function showPanel(name) {
    panels.forEach(p => {
      p.hidden = p.dataset.panel !== name;
    });
    
    sidebar?.querySelectorAll('.sidebar_link').forEach(link => {
      link.classList.toggle('active', link.dataset.target === name);
    });
  }

  sidebar?.addEventListener('click', (e) => {
    const link = e.target.closest('.sidebar_link');
    if (!link) return;
    const target = link.dataset.target;
    if (!target) return;
    showPanel(target);
  });

  function setFormStatus(message, isError = false) {
    if (!createForm) return;

    let status = createForm.querySelector('.form-status');
    if (!status) {
      status = document.createElement('p');
      status.className = 'form-status';
      status.style.marginTop = '12px';
      status.style.fontSize = '14px';
      createForm.appendChild(status);
    }

    status.textContent = message;
    status.style.color = isError ? '#b42318' : '#027a48';
  }

  function getClubContext() {
    const params = new URLSearchParams(window.location.search);
    const clubId = (params.get('club') || '').trim().toLowerCase();

    const clubNameById = {
      acm: 'ACM',
      jci: 'JCI',
      ieee: 'IEEE',
      cine_radio: 'Cine Radio',
      securinets: 'Securinets',
      junior: 'Junior',
      aerobotix: 'Aerobotix',
      theatro: 'Theatro',
      '3zero': '3ZERO',
      android: 'Android Club',
      genesis_labs: 'Genesis Labs',
      insat_press: 'INSAT Press'
    };

    const resolvedId = clubNameById[clubId] ? clubId : 'acm';
    return {
      clubId: resolvedId,
      clubName: clubNameById[resolvedId]
    };
  }

  async function uploadCoverImage(file, prefix) {
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

  async function createEvent(payload) {
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

  createForm?.addEventListener('submit', async (e) => {
    // prevent page reload when form is submitted
    e.preventDefault();

    const submitButton = createForm.querySelector('.event-create-submit');
    const originalButtonText = submitButton?.textContent || 'Suggest Event';

    try {
      setFormStatus('Submitting event...');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
      }

      const formData = new FormData(createForm);
      const file = formData.get('event-cover');
      if (!(file instanceof File) || !file.size) {
        throw new Error('Please choose a cover image.');
      }

      const { clubId, clubName } = getClubContext();
      const uploadedImagePath = await uploadCoverImage(file, clubId);

      const title = String(formData.get('event-title') || '').trim();
      const date = String(formData.get('event-date') || '').trim();
      const startTime = String(formData.get('event-start') || '').trim();
      const endTime = String(formData.get('event-end') || '').trim();
      const maxParticipants = Number(formData.get('event-places') || 0);
      const location = String(formData.get('event-location') || '').trim();
      const description = String(formData.get('event-description') || '').trim();

      const eventPayload = {
        title,
        club: clubName,
        clubLogo: `../assets/images/${clubId}/profile.jpg`,
        image: uploadedImagePath,
        date,
        time: startTime,
        location,
        description: `${description}${endTime ? `\nEnd time: ${endTime}` : ''}`,
        participants: 0,
        maxParticipants,
        featured: false
      };

      await createEvent(eventPayload);
      createForm.reset();
      setFormStatus('Event created successfully and linked to backend.');
    } catch (error) {
      setFormStatus(error.message || 'Something went wrong while creating the event.', true);
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
      }
    }
  });

  const first = sidebar?.querySelector('.sidebar_link[data-target]');
  if (first?.dataset.target) showPanel(first.dataset.target);
});