<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Asset;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo trader (buyer)
        $buyer = User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'balance' => '10000.00',
            ]
        );

        // Maker/seller with BTC to sell
        $seller = User::updateOrCreate(
            ['email' => 'maker@example.com'],
            [
                'name' => 'Demo Maker',
                'password' => bcrypt('password'),
                'balance' => '0.00',
            ]
        );

        // Ensure seller has BTC and some locked_amount = 0
        Asset::updateOrCreate(
            ['user_id' => $seller->id, 'symbol' => 'BTC'],
            ['amount' => '1.50000000', 'locked_amount' => '0.00000000']
        );

        // Add another asset for buyer (empty)
        Asset::updateOrCreate(
            ['user_id' => $buyer->id, 'symbol' => 'BTC'],
            ['amount' => '0.00000000', 'locked_amount' => '0.00000000']
        );
    }
}
