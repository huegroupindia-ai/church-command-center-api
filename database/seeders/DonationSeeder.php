<?php

namespace Database\Seeders;

use App\Models\Donation;
use Illuminate\Database\Seeder;

class DonationSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['tithe', 'offering', 'building_fund', 'missions', 'benevolence', 'youth'];
        $methods = ['cash', 'check', 'card', 'bank_transfer', 'online'];
        $names = ['John Smith', 'Maria Garcia', 'David Kim', 'Sarah Chen', 'James Wilson', 'Lisa Johnson', 'Robert Brown', 'Emily Davis', 'Michael Lee', 'Jennifer Martinez'];

        for ($i = 0; $i < 30; $i++) {
            $daysAgo = rand(0, 90);
            $type = $types[array_rand($types)];
            $amount = match($type) {
                'tithe' => rand(50, 500),
                'offering' => rand(10, 200),
                'building_fund' => rand(100, 1000),
                'missions' => rand(25, 300),
                'benevolence' => rand(50, 500),
                'youth' => rand(10, 100),
                default => rand(10, 200),
            };

            Donation::create([
                'church_id' => 1,
                'donor_id' => $i < 5 ? 1 : null,
                'donor_name' => $names[array_rand($names)],
                'donor_email' => strtolower(str_replace(' ', '.', $names[array_rand($names)])) . '@email.com',
                'amount' => $amount,
                'type' => $type,
                'method' => $methods[array_rand($methods)],
                'reference_number' => 'REF-' . strtoupper(bin2hex(random_bytes(3))),
                'is_recurring' => rand(0, 10) > 7,
                'recurring_frequency' => rand(0, 10) > 7 ? 'monthly' : null,
                'donated_at' => now()->subDays($daysAgo)->subHours(rand(0, 23)),
            ]);
        }
    }
}
