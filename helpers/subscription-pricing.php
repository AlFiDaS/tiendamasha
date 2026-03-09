<?php
/**
 * Precios de suscripción por plan y duración
 * Duraciones: 1, 6, 12, 36 meses (con descuentos progresivos)
 */
require_once dirname(__FILE__) . '/plans.php';

/** Duraciones disponibles y descuento aplicado */
function getSubscriptionDurations() {
    return [
        1   => ['months' => 1,  'label' => '1 mes',   'discount' => 0],
        6   => ['months' => 6,  'label' => '6 meses', 'discount' => 10],
        12  => ['months' => 12, 'label' => '1 año',   'discount' => 17],
        36  => ['months' => 36, 'label' => '3 años',  'discount' => 25],
    ];
}

/**
 * Calcula el precio total para un plan y duración
 * @return array ['total' => float, 'monthly' => float, 'discount_percent' => int, 'duration_label' => string]
 */
function getSubscriptionPrice($plan, $durationMonths) {
    $limits = getPlanLimits($plan);
    $baseMonthly = (float) ($limits['price'] ?? 0);
    if ($baseMonthly <= 0) {
        return ['total' => 0, 'monthly' => 0, 'discount_percent' => 0, 'duration_label' => ''];
    }

    $durations = getSubscriptionDurations();
    $d = $durations[$durationMonths] ?? $durations[1];
    $discount = $d['discount'] ?? 0;
    $months = $d['months'] ?? 1;

    $totalBeforeDiscount = $baseMonthly * $months;
    $total = round($totalBeforeDiscount * (1 - $discount / 100));
    $effectiveMonthly = $months > 0 ? round($total / $months) : $baseMonthly;

    return [
        'total'           => $total,
        'monthly'         => $effectiveMonthly,
        'discount_percent' => $discount,
        'duration_label'   => $d['label'] ?? $months . ' meses',
        'months'          => $months,
    ];
}
