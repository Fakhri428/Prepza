<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class TrendInsightService
{
    public function buildTrendPayload(array $orders): ?array
    {
        $payloads = $this->buildTrendPayloadsByGender($orders);

        return $payloads[0] ?? null;
    }

    public function buildTrendPayloadsByGender(array $orders, array $menus = []): array
    {
        $menuLookup = $this->buildMenuLookup($menus);
        $totalsByGender = [
            'male' => [],
            'female' => [],
        ];
        $totalsOverall = [];
        $hasGenderData = false;

        foreach ($orders as $order) {
            $gender = $this->resolveGender((string) Arr::get($order, 'gender', ''));

            foreach ((array) Arr::get($order, 'items', []) as $item) {
                $name = trim((string) Arr::get($item, 'item_name', ''));
                if ($name === '') {
                    continue;
                }

                $normalized = mb_strtolower($name);
                $qty = max(1, (int) Arr::get($item, 'qty', 1));

                $totalsOverall[$normalized] = ($totalsOverall[$normalized] ?? 0) + $qty;

                if ($gender === null) {
                    continue;
                }

                $hasGenderData = true;
                $totalsByGender[$gender][$normalized] = ($totalsByGender[$gender][$normalized] ?? 0) + $qty;
            }
        }

        $minimumRepeat = (int) config('services.service_a.trend_min_repeat', 4);
        $minimumRepeatGender = (int) config('services.service_a.trend_min_repeat_gender', 2);
        $now = Carbon::now();
        $expiresAt = $now->copy()->addMinutes((int) config('services.service_a.trend_expire_minutes', 180));

        $payloads = [];

        foreach (['male' => 'laki-laki', 'female' => 'perempuan'] as $gender => $genderLabel) {
            $totals = $totalsByGender[$gender] ?? [];
            if ($totals === []) {
                continue;
            }

            arsort($totals);
            $pickedMenuKeys = [];

            foreach ($totals as $topItem => $topCount) {
                $topItem = (string) $topItem;
                $topCount = (int) $topCount;

                if ($topCount < $minimumRepeatGender) {
                    continue;
                }

                $displayName = mb_convert_case($topItem, MB_CASE_TITLE, 'UTF-8');
                $score = min(100, 50 + ($topCount * 5));
                $menuData = $this->resolveTrendMenuData($topItem, $displayName, $menuLookup);
                $menuKey = $this->menuIdentityKey($menuData);

                if (in_array($menuKey, $pickedMenuKeys, true)) {
                    continue;
                }

                $pickedMenuKeys[] = $menuKey;

                $payloads[] = [
                    'title' => $displayName.' Sedang Naik ('.$genderLabel.')',
                    'image_url' => $this->resolvePrimaryImageUrl($menuData),
                    'caption' => "Terdeteksi {$topCount} porsi aktif untuk {$displayName} dari pelanggan {$genderLabel} pada antrean saat ini.",
                    'score' => $score,
                    'source_timestamp' => $now->toIso8601String(),
                    'expires_at' => $expiresAt->toIso8601String(),
                    'is_active' => true,
                    'menu' => $menuData,
                ];

                if (count($pickedMenuKeys) >= 2) {
                    break;
                }
            }
        }

        if ($payloads === [] && ! $hasGenderData && $totalsOverall !== []) {
            arsort($totalsOverall);
            $pickedMenuKeys = [];

            foreach ($totalsOverall as $topItem => $topCount) {
                $topItem = (string) $topItem;
                $topCount = (int) $topCount;

                if ($topCount < $minimumRepeat) {
                    continue;
                }

                $displayName = mb_convert_case($topItem, MB_CASE_TITLE, 'UTF-8');
                $score = min(100, 50 + ($topCount * 5));
                $menuData = $this->resolveTrendMenuData($topItem, $displayName, $menuLookup);
                $menuKey = $this->menuIdentityKey($menuData);

                if (in_array($menuKey, $pickedMenuKeys, true)) {
                    continue;
                }

                $pickedMenuKeys[] = $menuKey;

                $payloads[] = [
                    'title' => $displayName.' Sedang Naik',
                    'image_url' => $this->resolvePrimaryImageUrl($menuData),
                    'caption' => "Terdeteksi {$topCount} porsi aktif untuk {$displayName} pada antrean saat ini.",
                    'score' => $score,
                    'source_timestamp' => $now->toIso8601String(),
                    'expires_at' => $expiresAt->toIso8601String(),
                    'is_active' => true,
                    'menu' => $menuData,
                ];

                if (count($pickedMenuKeys) >= 2) {
                    break;
                }
            }
        }

        return $payloads;
    }

    protected function resolveGender(string $gender): ?string
    {
        $normalized = strtolower(trim($gender));

        if (in_array($normalized, ['male', 'm', 'laki-laki', 'laki laki', 'pria'], true)) {
            return 'male';
        }

        if (in_array($normalized, ['female', 'f', 'perempuan', 'wanita'], true)) {
            return 'female';
        }

        return null;
    }

    protected function buildMenuLookup(array $menus): array
    {
        $lookup = [];

        foreach ($menus as $menu) {
            $name = trim((string) Arr::get($menu, 'name', ''));
            if ($name === '') {
                continue;
            }

            $resolvedMenu = [
                'name' => $name,
                'slug' => Arr::get($menu, 'slug'),
                'description' => Arr::get($menu, 'description'),
                'image_path' => Arr::get($menu, 'image_path'),
                'image_external_url' => Arr::get($menu, 'image_external_url'),
                'image_url' => Arr::get($menu, 'image_url'),
                'price' => Arr::get($menu, 'price'),
                'is_active' => (bool) Arr::get($menu, 'is_active', true),
            ];

            $lookup[mb_strtolower($name)] = $resolvedMenu;

            foreach ((array) Arr::get($menu, 'aliases', []) as $alias) {
                $aliasName = trim((string) Arr::get($alias, 'alias', ''));
                if ($aliasName === '') {
                    continue;
                }

                $lookup[mb_strtolower($aliasName)] = $resolvedMenu;
            }
        }

        return $lookup;
    }

    protected function resolveTrendMenuData(string $normalizedTopItem, string $displayName, array $menuLookup): array
    {
        $fallbackImageUrl = (string) config('services.service_a.trend_placeholder_image');
        $fallback = [
            'name' => $displayName,
            'slug' => null,
            'description' => null,
            'image_path' => null,
            'image_external_url' => null,
            'image_url' => $fallbackImageUrl !== '' ? $fallbackImageUrl : null,
            'price' => null,
            'is_active' => true,
        ];

        $matched = $menuLookup[$normalizedTopItem] ?? null;
        if (! is_array($matched)) {
            return $fallback;
        }

        return [
            'name' => (string) Arr::get($matched, 'name', $fallback['name']),
            'slug' => Arr::get($matched, 'slug'),
            'description' => Arr::get($matched, 'description'),
            'image_path' => Arr::get($matched, 'image_path'),
            'image_external_url' => Arr::get($matched, 'image_external_url'),
            'image_url' => Arr::get($matched, 'image_url') ?: $fallback['image_url'],
            'price' => Arr::get($matched, 'price'),
            'is_active' => (bool) Arr::get($matched, 'is_active', true),
        ];
    }

    protected function resolvePrimaryImageUrl(array $menuData): string
    {
        $fallback = (string) config('services.service_a.trend_placeholder_image');

        $candidates = [
            Arr::get($menuData, 'image_external_url'),
            Arr::get($menuData, 'image_url'),
            $this->buildImageUrlFromPath(Arr::get($menuData, 'image_path')),
            $fallback,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeImageUrl(is_string($candidate) ? $candidate : null);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return '';
    }

    protected function buildImageUrlFromPath(mixed $imagePath): ?string
    {
        if (! is_string($imagePath) || trim($imagePath) === '') {
            return null;
        }

        return '/storage/'.ltrim($imagePath, '/');
    }

    protected function normalizeImageUrl(?string $imageUrl): ?string
    {
        if ($imageUrl === null) {
            return null;
        }

        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return null;
        }

        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return $imageUrl;
        }

        $appUrl = rtrim((string) config('app.url', ''), '/');
        if ($appUrl === '') {
            return $imageUrl;
        }

        if (str_starts_with($imageUrl, '/')) {
            return $appUrl.$imageUrl;
        }

        return $appUrl.'/'.ltrim($imageUrl, '/');
    }

    protected function menuIdentityKey(array $menuData): string
    {
        $slug = trim((string) Arr::get($menuData, 'slug', ''));
        if ($slug !== '') {
            return 'slug:'.mb_strtolower($slug);
        }

        $name = trim((string) Arr::get($menuData, 'name', ''));
        if ($name !== '') {
            return 'name:'.mb_strtolower($name);
        }

        return 'unknown:'.md5((string) json_encode($menuData));
    }
}
