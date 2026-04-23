<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresMemberAccess;
use App\Services\SelfProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberIdCardController extends Controller
{
    use EnsuresMemberAccess;

    public function __construct(private readonly SelfProfileService $selfProfileService)
    {
    }

    public function show(Request $request): JsonResponse|
        \Symfony\Component\HttpFoundation\Response
    {
        if ($unauthorized = $this->ensureMemberAccess()) {
            return $unauthorized;
        }

        $summary = $this->selfProfileService->summary($request->user());
        $member = $request->user()->member;

        if (! $member) {
            return $this->error('No linked member profile found for ID card generation.', 404);
        }

        $payload = [
            'name' => $summary['name'] ?? $request->user()->name,
            'member_no' => $summary['member_no'] ?? $member->member_no,
            'main_committee' => $summary['main_committee'] ?? null,
            'primary_position' => $summary['primary_position'] ?? null,
            'photo_url' => $summary['photo_url'] ?? null,
            'issued_at' => now()->toDateString(),
            'validity_note' => 'Official member card for Student Movement – NDM',
        ];

        $svg = $this->buildSvg($payload);

        if ($request->boolean('download')) {
            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="member-id-card-'.$payload['member_no'].'.svg"',
            ]);
        }

        return $this->success([
            ...$payload,
            'svg' => $svg,
        ], 'Member ID card retrieved successfully.');
    }

    private function buildSvg(array $data): string
    {
        $name = htmlspecialchars((string) ($data['name'] ?? 'Member'), ENT_QUOTES, 'UTF-8');
        $memberNo = htmlspecialchars((string) ($data['member_no'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $committee = htmlspecialchars((string) ($data['main_committee'] ?? 'General Member'), ENT_QUOTES, 'UTF-8');
        $position = htmlspecialchars((string) ($data['primary_position'] ?? 'Member'), ENT_QUOTES, 'UTF-8');
        $issuedAt = htmlspecialchars((string) ($data['issued_at'] ?? now()->toDateString()), ENT_QUOTES, 'UTF-8');
        $photo = $data['photo_url'] ? '<image href="'.htmlspecialchars($data['photo_url'], ENT_QUOTES, 'UTF-8').'" x="28" y="84" width="120" height="120" preserveAspectRatio="xMidYMid slice" clip-path="url(#clip)" />' : '';

        return <<<SVG
<svg width="860" height="520" viewBox="0 0 860 520" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="860" y2="520" gradientUnits="userSpaceOnUse">
      <stop stop-color="#16213E"/>
      <stop offset="0.58" stop-color="#0F3460"/>
      <stop offset="1" stop-color="#96281B"/>
    </linearGradient>
    <clipPath id="clip">
      <rect x="28" y="84" width="120" height="120" rx="24" />
    </clipPath>
  </defs>
  <rect width="860" height="520" rx="32" fill="url(#bg)"/>
  <circle cx="786" cy="72" r="96" fill="#F39C12" fill-opacity="0.12"/>
  <circle cx="720" cy="450" r="150" fill="#C0392B" fill-opacity="0.14"/>
  <rect x="22" y="22" width="816" height="476" rx="28" stroke="rgba(255,255,255,0.18)"/>

  <rect x="28" y="28" width="120" height="36" rx="18" fill="rgba(255,255,255,0.08)"/>
  <text x="88" y="50" text-anchor="middle" fill="#F8F9FA" font-size="14" font-family="Inter, Arial, sans-serif" font-weight="700">MEMBER ID CARD</text>

  <rect x="28" y="84" width="120" height="120" rx="24" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)"/>
  {$photo}
  <text x="176" y="130" fill="#FFFFFF" font-size="38" font-family="Inter, Arial, sans-serif" font-weight="800">{$name}</text>
  <text x="176" y="168" fill="#F7C66A" font-size="20" font-family="Inter, Arial, sans-serif" font-weight="700">{$position}</text>
  <text x="176" y="198" fill="#D8DEE8" font-size="18" font-family="Inter, Arial, sans-serif">{$committee}</text>

  <rect x="176" y="238" width="240" height="92" rx="20" fill="rgba(255,255,255,0.08)"/>
  <text x="200" y="272" fill="#C8D3E2" font-size="14" font-family="Inter, Arial, sans-serif" font-weight="700">MEMBER NUMBER</text>
  <text x="200" y="312" fill="#FFFFFF" font-size="30" font-family="Inter, Arial, sans-serif" font-weight="800">{$memberNo}</text>

  <rect x="444" y="238" width="196" height="92" rx="20" fill="rgba(255,255,255,0.08)"/>
  <text x="468" y="272" fill="#C8D3E2" font-size="14" font-family="Inter, Arial, sans-serif" font-weight="700">ISSUED DATE</text>
  <text x="468" y="312" fill="#FFFFFF" font-size="24" font-family="Inter, Arial, sans-serif" font-weight="800">{$issuedAt}</text>

  <rect x="28" y="372" width="804" height="98" rx="24" fill="rgba(255,255,255,0.08)"/>
  <text x="56" y="410" fill="#FFFFFF" font-size="24" font-family="Inter, Arial, sans-serif" font-weight="800">Student Movement – NDM</text>
  <text x="56" y="440" fill="#D8DEE8" font-size="16" font-family="Inter, Arial, sans-serif">Official membership identification for organization access and verification.</text>
  <text x="56" y="462" fill="#F7C66A" font-size="14" font-family="Inter, Arial, sans-serif">Valid while membership remains active in the NDM member registry.</text>
</svg>
SVG;
    }
}