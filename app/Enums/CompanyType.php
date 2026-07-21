<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyType: string implements HasLabel
{
    case JointStockPrivate = 'joint_stock_private';       // سهامی خاص
    case JointStockPublic = 'joint_stock_public';         // سهامی عام
    case LimitedLiability = 'limited_liability';          // مسئولیت محدود
    case JointLiability = 'joint_liability';              // تضامنی
    case MixedNonStock = 'mixed_non_stock';               // مختلط غیرسهامی
    case MixedStock = 'mixed_stock';                      // مختلط سهامی
    case ProportionalLiability = 'proportional_liability'; // نسبی
    case ProductionCooperative = 'production_cooperative'; // تعاونی تولیدی
    case ConsumerCooperative = 'consumer_cooperative';    // تعاونی مصرفی

    public function getLabel(): string
    {
        return match ($this) {
            self::JointStockPrivate => 'سهامی خاص',
            self::JointStockPublic => 'سهامی عام',
            self::LimitedLiability => 'مسئولیت محدود',
            self::JointLiability => 'تضامنی',
            self::MixedNonStock => 'مختلط غیرسهامی',
            self::MixedStock => 'مختلط سهامی',
            self::ProportionalLiability => 'نسبی',
            self::ProductionCooperative => 'تعاونی تولیدی',
            self::ConsumerCooperative => 'تعاونی مصرفی',
        };
    }
}
