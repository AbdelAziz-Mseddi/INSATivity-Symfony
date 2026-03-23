import {
  createEvent,
  fetchAllEvents,
  fetchClubById,
  uploadCoverImage
} from './club-dashboard/api.js';
import { DEFAULT_CLUB_ID, EVENT_MANAGER_CLUB_NAME_BY_ID } from './club-dashboard/constants.js';
import {
  bindSidebar,
  getDashboardDom,
  renderClubProfile,
  renderDoneEvents,
  renderFeedbackEventOptions,
  renderHistoryEvents,
  renderLoadError,
  renderPendingEvents,
  setFormStatus
} from './club-dashboard/render.js';
import { getRequestedClubId, normalizeText, splitEventsByDate } from './club-dashboard/utils.js';

document.addEventListener('DOMContentLoaded', () => {
  const dom = getDashboardDom();
  let activeClub = null;

  function getClubContext() {
    const requestedClubId = getRequestedClubId();

    if (activeClub?.id && activeClub?.name) {
      return {
        clubId: activeClub.id,
        clubName: EVENT_MANAGER_CLUB_NAME_BY_ID[activeClub.id] || activeClub.name
      };
    }

    const resolvedId = EVENT_MANAGER_CLUB_NAME_BY_ID[requestedClubId]
      ? requestedClubId
      : DEFAULT_CLUB_ID;

    return {
      clubId: resolvedId,
      clubName: EVENT_MANAGER_CLUB_NAME_BY_ID[resolvedId]
    };
  }

  function getClubEvents(allEvents, club) {
    const normalizedClubName = normalizeText(club.name);
    const normalizedManagerName = normalizeText(EVENT_MANAGER_CLUB_NAME_BY_ID[club.id]);
    const imageToken = `/assets/images/${club.id}/`;

    return allEvents.filter(event => {
      const eventClubName = normalizeText(event.club);
      const clubLogoPath = String(event.clubLogo || '');

      return (
        eventClubName === normalizedClubName ||
        eventClubName === normalizedManagerName ||
        clubLogoPath.includes(imageToken)
      );
    });
  }

  function applyClubDashboardContent(club, events) {
    const { upcomingEvents, finishedEvents } = splitEventsByDate(events);

    renderClubProfile(dom, club, events);
    renderPendingEvents(dom, club, upcomingEvents);
    renderHistoryEvents(dom, club, finishedEvents);
    renderDoneEvents(dom, club, finishedEvents);
    renderFeedbackEventOptions(dom, finishedEvents);
  }

  async function loadDashboardData() {
    const requestedClubId = getRequestedClubId() || DEFAULT_CLUB_ID;

    try {
      let club;
      try {
        club = await fetchClubById(requestedClubId);
      } catch {
        // Fallback to default club when an unknown club id is provided in URL.
        club = await fetchClubById(DEFAULT_CLUB_ID);
      }

      const allEvents = await fetchAllEvents();
      const clubEvents = getClubEvents(allEvents, club);

      activeClub = club;
      applyClubDashboardContent(club, clubEvents);
    } catch (error) {
      renderLoadError(dom, error.message);
    }
  }

  dom.createForm?.addEventListener('submit', async event => {
    event.preventDefault();

    const submitButton = dom.createForm.querySelector('.event-create-submit');
    const originalButtonText = submitButton?.textContent || 'Suggest Event';

    try {
      setFormStatus(dom, 'Submitting event...');

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
      }

      const formData = new FormData(dom.createForm);
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
      dom.createForm.reset();
      setFormStatus(dom, 'Event created successfully and linked to backend.');
      await loadDashboardData();
    } catch (error) {
      setFormStatus(dom, error.message || 'Something went wrong while creating the event.', true);
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
      }
    }
  });

  bindSidebar(dom);
  loadDashboardData();
});
