<?php

namespace App\Services;

use App\Models\MasterItem;
use InvalidArgumentException;

class TransactionCalculatorService
{
    public function calculate(array $items): array
    {
        $serviceIds = [];
        $durationIds = [];
        $addonIds = [];

        foreach ($items as $item) {
            $serviceIds[] = (int) ($item['service_id'] ?? 0);

            if (! empty($item['duration_id'])) {
                $durationIds[] = (int) $item['duration_id'];
            }

            foreach (($item['addon_ids'] ?? []) as $addonId) {
                $addonIds[] = (int) $addonId;
            }
        }

        $services = MasterItem::query()
            ->where('type', 'service')
            ->where('is_active', true)
            ->whereIn('id', $serviceIds)
            ->get()
            ->keyBy('id');

        $durations = MasterItem::query()
            ->where('type', 'duration')
            ->where('is_active', true)
            ->whereIn('id', $durationIds)
            ->get()
            ->keyBy('id');

        $addons = MasterItem::query()
            ->where('type', 'addon')
            ->where('is_active', true)
            ->whereIn('id', $addonIds)
            ->get()
            ->keyBy('id');

        $lines = [];
        $total = 0.0;

        foreach ($items as $item) {
            $serviceId = (int) $item['service_id'];
            $qty = (float) $item['qty'];
            $service = $services->get($serviceId);

            if (! $service) {
                throw new InvalidArgumentException('Service is invalid or inactive.');
            }

            if ($qty <= 0) {
                throw new InvalidArgumentException('Quantity must be greater than zero.');
            }

            $baseAmount = $qty * (float) $service->base_price;
            $durationAmount = 0.0;
            $durationSnapshot = null;

            if (! empty($item['duration_id'])) {
                $duration = $durations->get((int) $item['duration_id']);

                if (! $duration) {
                    throw new InvalidArgumentException('Duration is invalid or inactive.');
                }

                $durationAmount = $duration->is_percentage_modifier
                    ? ($baseAmount * ((float) $duration->base_price / 100))
                    : (float) $duration->base_price;

                $durationSnapshot = [
                    'id' => $duration->id,
                    'name' => $duration->name,
                    'price' => (float) $duration->base_price,
                    'is_percentage_modifier' => (bool) $duration->is_percentage_modifier,
                ];
            }

            $addonSnapshots = [];
            $addonsAmount = 0.0;

            foreach (($item['addon_ids'] ?? []) as $addonId) {
                $addon = $addons->get((int) $addonId);

                if (! $addon) {
                    throw new InvalidArgumentException('Addon is invalid or inactive.');
                }

                $calculatedAddonAmount = $addon->is_percentage_modifier
                    ? ($baseAmount * ((float) $addon->base_price / 100))
                    : (float) $addon->base_price;

                $addonsAmount += $calculatedAddonAmount;

                $addonSnapshots[] = [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'price' => (float) $addon->base_price,
                    'is_percentage_modifier' => (bool) $addon->is_percentage_modifier,
                    'calculated_amount' => round($calculatedAddonAmount, 2),
                ];
            }

            $lineTotal = $baseAmount + $durationAmount + $addonsAmount;
            $total += $lineTotal;

            $lines[] = [
                'service_id' => $service->id,
                'qty' => $qty,
                'snapshot_data' => [
                    'service_name' => $service->name,
                    'service_price' => (float) $service->base_price,
                    'unit' => $service->unit,
                    'duration' => $durationSnapshot,
                    'addons' => $addonSnapshots,
                    'base_amount' => round($baseAmount, 2),
                    'duration_amount' => round($durationAmount, 2),
                    'addons_amount' => round($addonsAmount, 2),
                    'sub_total_calculated' => round($lineTotal, 2),
                ],
            ];
        }

        return [
            'lines' => $lines,
            'total_amount' => round($total, 2),
        ];
    }
}
