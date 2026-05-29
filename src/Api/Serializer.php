<?php

namespace App\Api;

use App\Entity\Club;
use App\Entity\Event;
use App\Entity\User;

/**
 * Maps Doctrine entities to the exact JSON shape the legacy API produced,
 * so the frontend receives identical payloads.
 */
final class Serializer
{
    /** Mirrors EventModel::mapEvent() category translation. */
    private static function mapCategory(?string $dbCategory): string
    {
        return match (strtolower(trim((string) $dbCategory))) {
            'technology'       => 'academic',
            'arts'             => 'culture',
            'entrepreneurship' => 'career',
            'social'           => 'social',
            'sports'           => 'sports',
            default            => 'other',
        };
    }

    public static function event(Event $e): array
    {
        $club = $e->getClub();

        return [
            'id'              => (int) $e->getId(),
            'title'           => $e->getTitle(),
            'club'            => $club?->getName(),
            'clubLogo'        => $club?->getLogo() ?? '',
            'image'           => $e->getImage(),
            'date'            => $e->getEventDate()?->format('Y-m-d'),
            'time'            => $e->getEventTime()?->format('H:i'),
            'location'        => $e->getLocation(),
            'description'     => $e->getDescription(),
            'participants'    => $e->getParticipants(),
            'maxParticipants' => $e->getMaxParticipants(),
            'featured'        => $e->isFeatured(),
            'is_approved'     => $e->isApproved(),
            'status'          => $e->getStatus(),
            'category'        => self::mapCategory($club?->getCategory()),
        ];
    }

    /** @param Event[] $events */
    public static function events(array $events): array
    {
        return array_map([self::class, 'event'], $events);
    }

    public static function club(Club $c): array
    {
        return [
            'id'          => $c->getId(),
            'name'        => $c->getName(),
            'category'    => $c->getCategory(),
            'logo'        => $c->getLogo(),
            'banner'      => $c->getBanner(),
            'description' => $c->getDescription(),
        ];
    }

    /** @param Club[] $clubs */
    public static function clubs(array $clubs): array
    {
        return array_map([self::class, 'club'], $clubs);
    }

    public static function user(User $u): array
    {
        return [
            'id'        => (int) $u->getId(),
            'full_name' => $u->getFullName(),
            'username'  => $u->getUsername(),
            'email'     => $u->getEmail(),
            'role'      => $u->getRole(),
            'club_id'   => $u->getClubId(),
        ];
    }
}
