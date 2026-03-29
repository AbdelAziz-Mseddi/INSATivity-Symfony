<?php

class EventManager {
    private $events = [];
    private $dataDir;
    private $currentDate;
    
    // Mapping of club IDs to display names
    private $clubIdToName = [
        'acm' => 'ACM',
        'jci' => 'JCI',
        'ieee' => 'IEEE',
        'cine_radio' => 'Cine Radio',
        'securinets' => 'Securinets',
        'junior' => 'Junior',
        'aerobotix' => 'Aerobotix',
        'theatro' => 'Theatro',
        '3zero' => '3ZERO',
        'android' => 'Android Club',
        'genesis_labs' => 'Genesis Labs',
        'insat_press' => 'INSAT Press'
    ];

    private $clubNameToId = [];

    public function __construct() {
        $this->dataDir = __DIR__ . '/../data/';
        $this->currentDate = date('Y-m-d');
        $this->clubNameToId = array_change_key_case(array_flip($this->clubIdToName), CASE_LOWER);
        $this->loadEventsFromFiles();
    }

    /**
     * Load all events from JSON files in the data directory
     */
    private function loadEventsFromFiles() {
        $this->events = [];

        foreach ($this->clubIdToName as $clubId => $clubName) {
            $filePath = $this->dataDir . $clubId . '_events.json';
            
            if (file_exists($filePath)) {
                try {
                    $jsonContent = file_get_contents($filePath);
                    $eventsData = json_decode($jsonContent, true);
                    
                    if (is_array($eventsData)) {
                        foreach ($eventsData as $event) {
                            // Add computed status field
                            $event['status'] = $this->getEventStatus($event['date'], $event['time']);
                            $this->events[] = $event;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Failed to load events from $filePath: " . $e->getMessage());
                }
            }
        }
    }

    private function resolveClubId($clubName) {
        if (!is_string($clubName) || trim($clubName) === '') {
            return null;
        }

        $normalizedName = strtolower(trim($clubName));
        return $this->clubNameToId[$normalizedName] ?? null;
    }

    private function readClubEvents($clubId) {
        $filePath = $this->dataDir . $clubId . '_events.json';

        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function writeClubEvents($clubId, $events) {
        $filePath = $this->dataDir . $clubId . '_events.json';
        $encoded = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw new Exception('Failed to encode events JSON');
        }

        $result = file_put_contents($filePath, $encoded . PHP_EOL, LOCK_EX);
        if ($result === false) {
            throw new Exception('Failed to persist events file');
        }
    }

    private function getNextEventId() {
        $maxId = 0;
        foreach ($this->events as $event) {
            if (isset($event['id']) && is_numeric($event['id'])) {
                $maxId = max($maxId, (int)$event['id']);
            }
        }
        return $maxId + 1;
    }



    /**
     * Calculate event status based on current date and event date/time
     * Status values: "pending", "published", "finished", "history" (for now only finished and published are implemented)
     */
    private function getEventStatus($eventDate, $eventTime) {
        $currentDateTime = strtotime($this->currentDate . ' 00:00:00');
        $eventDateTime = strtotime($eventDate . ' ' . $eventTime);
        
        if ($eventDateTime > $currentDateTime) {
            // Event is in the future
            return 'published';
        } else {
            // Event is in the past
            return 'finished';
        }
    }

    /**
     * Get a single event by ID
     * @param int $id Event ID
     * @return array|null Event object or null if not found
     */
    public function getEventById($id) {
        foreach ($this->events as $event) {
            if ($event['id'] == $id) {
                return $event;
            }
        }
        return null;
    }

    /**
     * Get all events for a specific club
     * @param string $club Club name
     * @return array Array of events for the club or empty array
     */
    public function getEventsByClub($club) {
        $result = [];
        foreach ($this->events as $event) {
            if ($event['club'] === $club) {
                $result[] = $event;
            }
        }
        return $result;
    }

    /**
     * Get events for a specific club filtered by status
     * @param string $club Club name
     * @param string $status Event status (pending, published, finished, history)
     * @return array Array of filtered events or empty array
     */
    public function getEventsByClubAndStatus($club, $status) {
        $result = [];
        foreach ($this->events as $event) {
            if ($event['club'] === $club && $event['status'] === $status) {
                $result[] = $event;
            }
        }
        return $result;
    }

    /**
     * Get all events (useful for debugging or dashboard views)
     * @return array All loaded events
     */
    public function getAllEvents() {
        return $this->events;
    }

    /**
     * Get all events with a specific status
     * @param string $status Event status
     * @return array Events matching the status
     */
    public function getEventsByStatus($status) {
        $result = [];
        foreach ($this->events as $event) {
            if ($event['status'] === $status) {
                $result[] = $event;
            }
        }
        return $result;
    }

    /**
     * Get featured events across all clubs
     * @return array Featured events
     */
    public function getFeaturedEvents() {
        $result = [];
        foreach ($this->events as $event) {
            if (isset($event['featured']) && $event['featured'] === true) {
                $result[] = $event;
            }
        }
        return $result;
    }

    public function createEvent($payload) {
        if (!isset($payload['title'], $payload['club'], $payload['date'], $payload['time'], $payload['location'], $payload['description'])) {
            throw new Exception('Missing required fields');
        }

        $clubId = $this->resolveClubId($payload['club']);
        if ($clubId === null) {
            throw new Exception('Invalid club name');
        }

        $event = [
            'id' => $this->getNextEventId(),
            'title' => $payload['title'],
            'club' => $payload['club'],
            'clubLogo' => $payload['clubLogo'] ?? '',
            'image' => $payload['image'] ?? '',
            'date' => $payload['date'],
            'time' => $payload['time'],
            'location' => $payload['location'],
            'description' => $payload['description'],
            'participants' => $payload['participants'] ?? 0,
            'maxParticipants' => $payload['maxParticipants'] ?? 0,
            'featured' => $payload['featured'] ?? false
        ];

        $events = $this->readClubEvents($clubId);
        $events[] = $event;
        $this->writeClubEvents($clubId, $events);

        $this->loadEventsFromFiles();
        return $this->getEventById($event['id']);
    }

    public function updateEvent($id, $payload) {
        $eventId = (int)$id;
        if ($eventId <= 0) {
            throw new Exception('Invalid event ID');
        }

        $existingEvent = $this->getEventById($eventId);
        if (!$existingEvent) {
            throw new Exception('Event not found');
        }

        unset($payload['id'], $payload['status']);
        $existingWithoutComputed = $existingEvent;
        unset($existingWithoutComputed['status']);
        $updatedEvent = array_merge($existingWithoutComputed, $payload);

        $oldClubId = $this->resolveClubId($existingEvent['club']);
        $newClubId = $this->resolveClubId($updatedEvent['club']);

        if ($oldClubId === null || $newClubId === null) {
            throw new Exception('Invalid club mapping');
        }

        $oldClubEvents = $this->readClubEvents($oldClubId);
        $newClubEvents = ($oldClubId === $newClubId) ? $oldClubEvents : $this->readClubEvents($newClubId);

        $found = false;
        foreach ($oldClubEvents as $index => $clubEvent) {
            if (isset($clubEvent['id']) && (int)$clubEvent['id'] === $eventId) {
                if ($oldClubId === $newClubId) {
                    $oldClubEvents[$index] = $updatedEvent;
                } else {
                    array_splice($oldClubEvents, $index, 1);
                    $newClubEvents[] = $updatedEvent;
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception('Event not found in source file');
        }

        $this->writeClubEvents($oldClubId, $oldClubEvents);
        if ($oldClubId !== $newClubId) {
            $this->writeClubEvents($newClubId, $newClubEvents);
        }

        $this->loadEventsFromFiles();
        return $this->getEventById($eventId);
    }

    public function deleteEvent($id) {
        $eventId = (int)$id;
        if ($eventId <= 0) {
            throw new Exception('Invalid event ID');
        }

        $existingEvent = $this->getEventById($eventId);
        if (!$existingEvent) {
            throw new Exception('Event not found');
        }

        $clubId = $this->resolveClubId($existingEvent['club']);
        if ($clubId === null) {
            throw new Exception('Invalid club mapping');
        }

        $events = $this->readClubEvents($clubId);
        $initialCount = count($events);

        $events = array_values(array_filter($events, function ($event) use ($eventId) {
            return !(isset($event['id']) && (int)$event['id'] === $eventId);
        }));

        if (count($events) === $initialCount) {
            throw new Exception('Event not found in data file');
        }

        $this->writeClubEvents($clubId, $events);

        $this->loadEventsFromFiles();
        return true;
    }
}
