<?php

require_once 'Database.php';

class EventManager {
    private $connection;
    private $currentDate;

    public function __construct() {
        $this->connection = Database::connect();
        $this->currentDate = date('Y-m-d');
    }

    private function getEventStatus($eventDate, $eventTime) {
        $currentDateTime = strtotime($this->currentDate . ' 00:00:00');
        $eventDateTime = strtotime($eventDate . ' ' . $eventTime);
        return $eventDateTime > $currentDateTime ? 'published' : 'finished';
    }

    private function mapEvent(array $row) {
        $date = substr((string)$row['event_date'], 0, 10);
        $time = substr((string)$row['event_time'], 0, 5);

        return [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'club' => $row['club'],
            'clubLogo' => $row['club_logo'] ?? '',
            'image' => $row['image'],
            'date' => $date,
            'time' => $time,
            'location' => $row['location'],
            'description' => $row['description'],
            'participants' => (int)$row['participants'],
            'maxParticipants' => (int)$row['max_participants'],
            'featured' => (bool)$row['featured'],
            'status' => $this->getEventStatus($date, $time),
        ];
    }

    private function resolveClubId($clubName) {
        if (!is_string($clubName) || trim($clubName) === '') {
            return null;
        }

        $clubName = trim($clubName);

        $stmt = $this->connection->prepare(
            'SELECT id
             FROM public.clubs
             WHERE LOWER(name) = LOWER(:club_name)
             LIMIT 1'
        );
        $stmt->execute([
            ':club_name' => $clubName
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : null;
    }

    private function fetchEvents($whereClause = '', $params = []) {
        $query = '
            SELECT
                e.id,
                e.title,
                c.name AS club,
                c.logo AS club_logo,
                e.image,
                e.event_date,
                e.event_time,
                e.location,
                e.description,
                e.participants,
                e.max_participants,
                e.featured
            FROM public.events e
            INNER JOIN public.clubs c ON c.id = e.club_id
        ';

        if ($whereClause !== '') {
            $query .= ' WHERE ' . $whereClause;
        }

        $query .= ' ORDER BY e.event_date DESC, e.event_time DESC, e.id DESC';

        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);

        return array_map([$this, 'mapEvent'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getEventById($id) {
        $events = $this->fetchEvents('e.id = :id', [':id' => (int)$id]);
        return $events[0] ?? null;
    }

    public function getEventsByClub($club) {
        $clubId = $this->resolveClubId($club);
        if ($clubId === null) {
            return [];
        }

        return $this->fetchEvents('e.club_id = :club_id', [':club_id' => $clubId]);
    }

    public function getEventsByClubAndStatus($club, $status) {
        $events = $this->getEventsByClub($club);

        return array_values(array_filter($events, function ($event) use ($status) {
            return $event['status'] === $status;
        }));
    }

    public function getAllEvents() {
        return $this->fetchEvents();
    }

    public function getEventsByStatus($status) {
        $events = $this->getAllEvents();

        return array_values(array_filter($events, function ($event) use ($status) {
            return $event['status'] === $status;
        }));
    }

    public function getFeaturedEvents() {
        return $this->fetchEvents('e.featured = TRUE');
    }

    public function createEvent($payload) {
        if (!isset($payload['title'], $payload['club'], $payload['date'], $payload['time'], $payload['location'], $payload['description'])) {
            throw new Exception('Missing required fields');
        }

        $clubId = $this->resolveClubId($payload['club']);
        if ($clubId === null) {
            throw new Exception('Invalid club name');
        }

        $stmt = $this->connection->prepare(
            'INSERT INTO public.events (
                club_id,
                title,
                image,
                event_date,
                event_time,
                location,
                description,
                participants,
                max_participants,
                featured
             ) VALUES (
                :club_id,
                :title,
                :image,
                :event_date,
                :event_time,
                :location,
                :description,
                :participants,
                :max_participants,
                :featured
             ) RETURNING id'
        );

        $stmt->execute([
            ':club_id' => $clubId,
            ':title' => $payload['title'],
            ':image' => $payload['image'] ?? '',
            ':event_date' => $payload['date'],
            ':event_time' => $payload['time'],
            ':location' => $payload['location'],
            ':description' => $payload['description'],
            ':participants' => (int)($payload['participants'] ?? 0),
            ':max_participants' => (int)($payload['maxParticipants'] ?? 0),
            ':featured' => (bool)($payload['featured'] ?? false),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this->getEventById($row['id']);
    }

    public function updateEvent($id, $payload) {
        $eventId = (int)$id;
        if ($eventId <= 0) {
            throw new Exception('Invalid event ID');
        }

        $existing = $this->getEventById($eventId);
        if (!$existing) {
            throw new Exception('Event not found');
        }

        unset($payload['id'], $payload['status'], $payload['clubLogo']);
        $updated = array_merge($existing, $payload);

        $clubId = $this->resolveClubId($updated['club']);
        if ($clubId === null) {
            throw new Exception('Invalid club name');
        }

        $stmt = $this->connection->prepare(
            'UPDATE public.events
             SET club_id = :club_id,
                 title = :title,
                 image = :image,
                 event_date = :event_date,
                 event_time = :event_time,
                 location = :location,
                 description = :description,
                 participants = :participants,
                 max_participants = :max_participants,
                 featured = :featured,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $eventId,
            ':club_id' => $clubId,
            ':title' => $updated['title'],
            ':image' => $updated['image'] ?? '',
            ':event_date' => $updated['date'],
            ':event_time' => $updated['time'],
            ':location' => $updated['location'],
            ':description' => $updated['description'],
            ':participants' => (int)($updated['participants'] ?? 0),
            ':max_participants' => (int)($updated['maxParticipants'] ?? 0),
            ':featured' => (bool)($updated['featured'] ?? false),
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Event not found');
        }

        return $this->getEventById($eventId);
    }

    public function deleteEvent($id) {
        $eventId = (int)$id;
        if ($eventId <= 0) {
            throw new Exception('Invalid event ID');
        }

        $stmt = $this->connection->prepare('DELETE FROM public.events WHERE id = :id');
        $stmt->execute([':id' => $eventId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Event not found');
        }

        return true;
    }
}
