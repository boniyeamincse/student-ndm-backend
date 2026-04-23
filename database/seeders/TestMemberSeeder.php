<?php

namespace Database\Seeders;

use App\Enum\MemberStatus;
use App\Enum\UserStatus;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestMemberSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'member.test@ndm.local';
        $password = 'Member@12345';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Member Test User',
                'password' => Hash::make($password),
                'status' => UserStatus::Active,
                'role_type' => 'member',
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        $member = Member::firstWhere('user_id', $user->id);

        if (! $member) {
            Member::create([
                'user_id' => $user->id,
                'member_no' => $this->generateMemberNo(),
                'full_name' => 'Member Test User',
                'email' => $email,
                'mobile' => '01700000099',
                'status' => MemberStatus::Active,
                'joined_at' => now(),
                'approved_at' => now(),
            ]);
        } else {
            $member->update([
                'full_name' => $member->full_name ?: 'Member Test User',
                'email' => $email,
                'mobile' => $member->mobile ?: '01700000099',
                'status' => MemberStatus::Active,
            ]);
        }

        $this->command?->info('Test member account ready: member.test@ndm.local / Member@12345');
    }

    private function generateMemberNo(): string
    {
        $year = now()->format('Y');
        $prefix = "MEM-{$year}-";

        $lastNo = Member::query()
            ->where('member_no', 'like', "{$prefix}%")
            ->orderByDesc('member_no')
            ->value('member_no');

        $next = 1;
        if (is_string($lastNo)) {
            $lastSeq = (int) substr($lastNo, -6);
            $next = $lastSeq + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
