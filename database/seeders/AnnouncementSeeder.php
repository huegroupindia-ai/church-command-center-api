<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $announcements = [
            [
                'title' => 'Easter Service Preparation',
                'body' => 'All teams please arrive by 6:00 AM for Easter service setup. Sound, lighting, and hospitality teams are priority. Please check your department checklist in the app.',
                'category' => 'general',
                'priority' => 'high',
                'is_pinned' => true,
                'published_at' => now()->subHours(2),
            ],
            [
                'title' => 'New Volunteer Training Session',
                'body' => 'Mandatory training session for all new volunteers this Saturday at 10:00 AM in the fellowship hall. Please bring your church ID and a notebook.',
                'category' => 'training',
                'priority' => 'medium',
                'is_pinned' => false,
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Equipment Maintenance Schedule',
                'body' => 'Monthly equipment maintenance will be performed next Tuesday. All department leaders please submit any equipment issues by Monday using the Equipment module.',
                'category' => 'equipment',
                'priority' => 'medium',
                'is_pinned' => false,
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Worship Team Rehearsal',
                'body' => 'Worship team rehearsal this Thursday at 7:00 PM. New songs for Sunday will be reviewed. Please practice your parts beforehand.',
                'category' => 'worship',
                'priority' => 'low',
                'is_pinned' => false,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Emergency: Roof Leak in Fellowship Hall',
                'body' => 'There is a roof leak in the fellowship hall. Please avoid using that area until further notice. Maintenance team has been notified.',
                'category' => 'urgent',
                'priority' => 'high',
                'is_pinned' => true,
                'published_at' => now()->subHours(6),
            ],
            [
                'title' => 'Youth Group Fundraiser',
                'body' => 'Youth group is organizing a bake sale next Sunday after service to raise funds for the summer camp. Volunteers needed for setup and teardown.',
                'category' => 'general',
                'priority' => 'low',
                'is_pinned' => false,
                'published_at' => now()->subDays(5),
            ],
        ];

        foreach ($announcements as $data) {
            Announcement::create(array_merge($data, [
                'church_id' => 1,
                'author_id' => 1,
                'is_active' => true,
            ]));
        }
    }
}
