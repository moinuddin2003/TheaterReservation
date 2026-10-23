<?php

namespace Database\Seeders\Staff;

/**
 * Who booked what for each of the demo day's shows (key = "start time + screen").
 * `used` = how many of the booking's tickets were already admitted.
 */
class DemoPlan
{
    /**
     * @return array<string, list<array{ref: int, customer: string, seats: int, used: int, labels: list<string>, cancelled: bool}>>
     */
    public static function bookings(): array
    {
        return [
            // The Figma story: 21 bookings, 45 seats, 19 admitted.
            '11:00 Screen 1' => [
                self::booking(482, 'Alex Morgan', 2, 1, ['G7', 'G8']),
                self::booking(478, 'Maya Chen', 2, 2),
                self::booking(476, 'Noah Patel', 2, 2),
                self::booking(473, 'Ella Brooks', 1, 1),
                self::booking(471, 'Liam Park', 2, 1),
                self::booking(470, 'Olivia Reed', 2, 2),
                self::booking(468, 'Ava Clark', 2, 0),
                self::booking(465, 'Ethan Ross', 1, 0),
                self::booking(462, 'Isabella Moore', 3, 2),
                self::booking(460, 'James Cooper', 2, 2),
                self::booking(459, 'Sophia Bennett', 4, 0),
                self::booking(457, 'Lucas Hayes', 1, 0),
                self::booking(455, 'Maya Chen', 2, 2),
                self::booking(453, 'Noah Patel', 3, 3),
                self::booking(451, 'Alex Morgan', 2, 0),
                self::booking(449, 'Liam Park', 2, 1),
                self::booking(447, 'Olivia Reed', 3, 0),
                self::booking(444, 'Ava Clark', 2, 0),
                self::booking(441, 'Ella Brooks', 4, 0),
                self::booking(438, 'Ethan Ross', 1, 0),
                self::booking(436, 'James Cooper', 2, 0),
                self::booking(480, 'Isabella Moore', 2, 0, [], true),
            ],
            '15:00 Screen 1' => [
                self::booking(510, 'Ava Clark', 2),
                self::booking(512, 'Liam Park', 3),
                self::booking(514, 'Sophia Bennett', 2),
                self::booking(516, 'Lucas Hayes', 1),
                self::booking(518, 'Isabella Moore', 4),
                self::booking(520, 'James Cooper', 2),
            ],
            '19:00 Screen 1' => [
                self::booking(610, 'Maya Chen', 2),
                self::booking(612, 'Noah Patel', 2),
                self::booking(614, 'Ella Brooks', 1),
                self::booking(616, 'Olivia Reed', 3),
                self::booking(618, 'Ethan Ross', 2),
                self::booking(620, 'Alex Morgan', 2),
                self::booking(622, 'Ava Clark', 4),
                self::booking(624, 'Liam Park', 2),
            ],
            '11:00 IMAX' => [
                self::booking(710, 'Alex Morgan', 2, 2),
                self::booking(712, 'Sophia Bennett', 2, 1),
                self::booking(714, 'Lucas Hayes', 3),
                self::booking(716, 'Isabella Moore', 2),
                self::booking(718, 'Ella Brooks', 1),
            ],
            '15:00 IMAX' => [
                self::booking(810, 'Maya Chen', 2),
                self::booking(812, 'Noah Patel', 2),
                self::booking(814, 'Olivia Reed', 3),
                self::booking(816, 'Ethan Ross', 2),
                self::booking(818, 'Ava Clark', 1),
            ],
            '19:00 IMAX' => [
                self::booking(910, 'Liam Park', 2),
                self::booking(912, 'James Cooper', 4),
                self::booking(914, 'Sophia Bennett', 2),
                self::booking(916, 'Lucas Hayes', 2),
                self::booking(918, 'Alex Morgan', 3),
                self::booking(920, 'Maya Chen', 2),
                self::booking(922, 'Noah Patel', 1),
            ],
        ];
    }

    /**
     * @param  list<string>  $labels  exact seats, or empty for the next free seats side by side
     * @return array{ref: int, customer: string, seats: int, used: int, labels: list<string>, cancelled: bool}
     */
    private static function booking(int $ref, string $customer, int $seats, int $used = 0, array $labels = [], bool $cancelled = false): array
    {
        return compact('ref', 'customer', 'seats', 'used', 'labels', 'cancelled');
    }
}
