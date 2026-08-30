<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->count() < 2) return;

        $pastor = $users->firstWhere('role', 'admin') ?? $users[0];
        $others = $users->where('id', '!=', $pastor->id);

        // 1:1 conversations
        foreach ($others->take(3) as $user) {
            $conv = Conversation::create([
                'name' => null,
                'is_group' => false,
                'created_by' => $pastor->id,
            ]);
            $conv->participants()->attach([$pastor->id, $user->id]);

            Message::create([
                'conversation_id' => $conv->id,
                'user_id' => $pastor->id,
                'body' => "Hey {$user->name}, how are things going?",
            ]);
            Message::create([
                'conversation_id' => $conv->id,
                'user_id' => $user->id,
                'body' => "Going well, Pastor! Ready for Sunday service.",
            ]);
            Message::create([
                'conversation_id' => $conv->id,
                'user_id' => $pastor->id,
                'body' => "Great! Let me know if you need anything.",
            ]);
        }

        // Group conversations
        $groupData = [
            ['name' => 'Sound & AV Team', 'icon' => 'speaker'],
            ['name' => 'Worship Team', 'icon' => 'music'],
            ['name' => 'Setup Crew', 'icon' => 'wrench'],
        ];

        foreach ($groupData as $group) {
            $conv = Conversation::create([
                'name' => $group['name'],
                'is_group' => true,
                'created_by' => $pastor->id,
            ]);
            
            $participantIds = [$pastor->id];
            foreach ($others->take(4) as $user) {
                $participantIds[] = $user->id;
            }
            $conv->participants()->attach($participantIds);

            // Add some messages
            $messages = [
                [$pastor->id, "Welcome to {$group['name']}! Let's coordinate for Sunday."],
                [$others[1]->id ?? $pastor->id, "Sounds good! I'll check the equipment."],
                [$others[2]->id ?? $pastor->id, "I'll be there by 7am for setup."],
                [$pastor->id, "Perfect. See everyone then! 🙏"],
            ];

            foreach ($messages as [$userId, $body]) {
                Message::create([
                    'conversation_id' => $conv->id,
                    'user_id' => $userId,
                    'body' => $body,
                ]);
            }
        }

        $this->command->info('Chat seeder completed: ' . Conversation::count() . ' conversations, ' . Message::count() . ' messages');
    }
}
