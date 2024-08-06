<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
class ReservationsSeeder extends Seeder
{
    public array $reservationOptions = [
        [
            "first_name"       => "Don",
            "last_name"        => "Lee",
            "email"            => "lee@example.com",
            "phone"            => "35534523532",
            "ip_address"       => null,
            "reservation_date" => "2023-11-20",
            "restaurant_id"    => 1,
            "time_slot_id"     => 2,
            "table_id"         => 1,
            "guest_number"     => 2,
            "user_id"          => 5,
            "waiter_id"        => null,
            "user_agent"       => null,
            "status"           => 2,
            "notes"            => null,
            "creator_type"     => "App\Models\User",
            "creator_id"       => 5,
            "editor_type"      => "App\Models\User",
            "editor_id"        => 4,
        ],
        [
            "first_name"       => "Iron",
            "last_name"        => "Man",
            "email"            => "iron@example.com",
            "phone"            => "440380217309",
            "ip_address"       => null,
            "reservation_date" => "2023-11-21",
            "restaurant_id"    => 1,
            "time_slot_id"     => 1,
            "table_id"         => 1,
            "guest_number"     => 2,
            "user_id"          => 5,
            "waiter_id"        => null,
            "user_agent"       => null,
            "status"           => 1,
            "notes"            => null,
            "creator_type"     => "App\Models\User",
            "creator_id"       => 5,
            "editor_type"      => "App\Models\User",
            "editor_id"        => 5,
        ],
        [
            "first_name"       => "rrr",
            "last_name"        => "rrr",
            "email"            => "rrr@example.com",
            "phone"            => "880832876213",
            "ip_address"       => null,
            "reservation_date" => "2023-11-20",
            "restaurant_id"    => 1,
            "time_slot_id"     => 3,
            "table_id"         => 1,
            "guest_number"     => 2,
            "user_id"          => 5,
            "waiter_id"        => null,
            "user_agent"       => null,
            "status"           => 1,
            "notes"            => null,
            "creator_type"     => "App\Models\User",
            "creator_id"       => 5,
            "editor_type"      => "App\Models\User",
            "editor_id"        => 5,
        ],
        [
            "first_name"       => "customer",
            "last_name"        => "One",
            "email"            => "customerName@example.com",
            "phone"            => "880563636",
            "ip_address"       => null,
            "reservation_date" => "2023-11-22",
            "restaurant_id"    => 1,
            "time_slot_id"     => 1,
            "table_id"         => 1,
            "guest_number"     => 3,
            "user_id"          => 5,
            "waiter_id"        => null,
            "user_agent"       => null,
            "status"           => 1,
            "notes"            => null,
            "creator_type"     => "App\Models\User",
            "creator_id"       => 5,
            "editor_type"      => "App\Models\User",
            "editor_id"        => 5,
        ],
        [
            "first_name"       => "michale",
            "last_name"        => "box",
            "email"            => "michale@example.com",
            "phone"            => "165678867",
            "ip_address"       => null,
            "reservation_date" => "2023-11-30",
            "restaurant_id"    => 1,
            "time_slot_id"     => 5,
            "table_id"         => 2,
            "guest_number"     => 5,
            "user_id"          => 5,
            "waiter_id"        => null,
            "user_agent"       => null,
            "status"           => 1,
            "notes"            => null,
            "creator_type"     => "App\Models\User",
            "creator_id"       => 5,
            "editor_type"      => "App\Models\User",
            "editor_id"        => 5,
        ],
        [
            "first_name"       => "john",
            "last_name"        => "king",
            "email"            => "king@example.com",
            "phone"            => "154654436",
            "ip_address"       => null,
            "reservation_date" => "2023-11-23",
            "restaurant_id"    => 1,
            "time_slot_id"     => 5,
            "table_id"         => 1,
            "guest_number"     => 2,
            "user_id"          => 5,
            "waiter_id"        => null,
            "user_agent"       => null,
            "status"           => 1,
            "notes"            => null,
            "creator_type"     => "App\Models\User",
            "creator_id"       => 5,
            "editor_type"      => "App\Models\User",
            "editor_id"        => 5,
        ],
        [
            "first_name"       => "John",
            "last_name"        => "Doe",
            "email"            => "johnjohn@example.com",
            "phone"            => "880232132132",
            "ip_address"       => null,
            "reservation_date" => "2023-12-19",
            "restaurant_id"    => 1,
            "time_slot_id"     => 5,
            "table_id"         => 1,
            "guest_number"     => 2,
            "user_id"          => 1,
            "waiter_id"        => null,
            "user_agent"       => null,
            "status"           => 1,
            "notes"            => null,
            "creator_type"     => "App\Models\User",
            "creator_id"       => 1,
            "editor_type"      => "App\Models\User",
            "editor_id"        => 1,
        ],
        [
            "first_name"       => "Customer",
            "last_name"        => "Smith",
            "email"            => "customer@example.com",
            "phone"            => "880532132132",
            "ip_address"       => null,
            "reservation_date" => "2024-04-30",
            "restaurant_id"    => 1,
            "time_slot_id"     => 4,
            "table_id"         => 1,
            "guest_number"     => 2,
            "user_id"          => 2,
            "waiter_id"        => null,
            "user_agent"       => null,
            "status"           => 1,
            "notes"            => null,
            "creator_type"     => "App\Models\User",
            "creator_id"       => 1,
            "editor_type"      => "App\Models\User",
            "editor_id"        => 2,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(){
        if (env('DEMO_MODE')) {
            foreach ($this->reservationOptions as $reservationOption) {
                Reservation::create([
                    'first_name'       => $reservationOption['first_name'],
                    'last_name'        => $reservationOption['last_name'],
                    'email'            => $reservationOption['email'],
                    'phone'            => $reservationOption['phone'],
                    'ip_address'       => $reservationOption['ip_address'],
                    'reservation_date' => $reservationOption['reservation_date'],
                    'restaurant_id'    => $reservationOption['restaurant_id'],
                    'time_slot_id'     => $reservationOption['time_slot_id'],
                    'table_id'         => $reservationOption['table_id'],
                    'guest_number'     => $reservationOption['guest_number'],
                    'user_id'          => $reservationOption['user_id'],
                    'waiter_id'        => $reservationOption['waiter_id'],
                    'user_agent'       => $reservationOption['user_agent'],
                    'status'           => $reservationOption['status'],
                    'notes'            => $reservationOption['notes'],
                    'creator_type'     => $reservationOption['creator_type'],
                    'creator_id'       => $reservationOption['creator_id'],
                    'editor_type'      => $reservationOption['editor_type'],
                    'editor_id'        => $reservationOption['editor_id'],
                ]);
            }
        }
    }
}
