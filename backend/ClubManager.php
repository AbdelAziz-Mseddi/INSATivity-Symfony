<?php

class ClubManager {
    private $clubs = [];
    private $dataDir;

    public function __construct() {
        $this->dataDir = __DIR__ . '/../data/';
        $this->loadClubsFromFile();
    }

    /**
     * Load all clubs from the JSON file
     */
    private function loadClubsFromFile() {
        $filePath = $this->dataDir . 'clubs.json';
        
        if (file_exists($filePath)) {
            try {
                $jsonContent = file_get_contents($filePath);
                $clubsData = json_decode($jsonContent, true);
                
                if (is_array($clubsData)) {
                    $this->clubs = $clubsData;
                }
            } catch (Exception $e) {
                error_log("Failed to load clubs from $filePath: " . $e->getMessage());
            }
        }
    }

    /**
     * Get a single club by ID
     * @param string $id Club ID
     * @return array|null Club object or null if not found
     */
    public function getClubById($id) {
        foreach ($this->clubs as $club) {
            if ($club['id'] === $id) {
                return $club;
            }
        }
        return null;
    }

    /**
     * Get all clubs
     * @return array All loaded clubs
     */
    public function getAllClubs() {
        return $this->clubs;
    }

    /**
     * Get clubs filtered by category
     * @param string $category Club category
     * @return array Array of clubs in the category or empty array
     */
    public function getClubsByCategory($category) {
        $result = [];
        foreach ($this->clubs as $club) {
            if ($club['category'] === $category) {
                $result[] = $club;
            }
        }
        return $result;
    }

    /**
     * Get all unique categories
     * @return array Array of unique categories
     */
    public function getAllCategories() {
        $categories = [];
        foreach ($this->clubs as $club) {
            if (!in_array($club['category'], $categories)) {
                $categories[] = $club['category'];
            }
        }
        return $categories;
    }

    /**
     * Create a new club
     * @param array $payload Club data
     * @return array Created club
     */
    public function createClub($payload) {
        if (!isset($payload['id'], $payload['name'], $payload['category'], $payload['description'])) {
            throw new Exception('Missing required fields');
        }

        if ($this->getClubById($payload['id'])) {
            throw new Exception('Club ID already exists');
        }

        $club = [
            'id' => $payload['id'],
            'name' => $payload['name'],
            'category' => $payload['category'],
            'banner' => $payload['banner'] ?? '',
            'description' => $payload['description']
        ];

        $this->clubs[] = $club;
        $this->writeClubsToFile();
        return $club;
    }

    /**
     * Update an existing club
     * @param string $id Club ID
     * @param array $payload Updated data
     * @return array Updated club
     */
    public function updateClub($id, $payload) {
        $club = $this->getClubById($id);
        if (!$club) {
            throw new Exception('Club not found');
        }

        unset($payload['id']);
        $updated = array_merge($club, $payload);

        foreach ($this->clubs as $index => $c) {
            if ($c['id'] === $id) {
                $this->clubs[$index] = $updated;
                break;
            }
        }

        $this->writeClubsToFile();
        return $updated;
    }

    /**
     * Delete a club
     * @param string $id Club ID
     * @return bool Success
     */
    public function deleteClub($id) {
        if (!$this->getClubById($id)) {
            throw new Exception('Club not found');
        }

        $this->clubs = array_values(array_filter($this->clubs, function ($club) use ($id) {
            return $club['id'] !== $id;
        }));

        $this->writeClubsToFile();
        return true;
    }

    /**
     * Write clubs to JSON file
     */
    private function writeClubsToFile() {
        $filePath = $this->dataDir . 'clubs.json';
        $encoded = json_encode($this->clubs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw new Exception('Failed to encode clubs JSON');
        }

        $result = file_put_contents($filePath, $encoded . PHP_EOL, LOCK_EX);
        if ($result === false) {
            throw new Exception('Failed to persist clubs file');
        }
    }
}
