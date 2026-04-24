<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'percentage',
        'status'
    ];

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::ENABLE) {
                $html = '<span class="badge badge--success">' . trans("Enabled") . '</span>';
            } elseif ($this->status == Status::DISABLE) {
                $html = '<span class="badge badge--danger">' . trans("Disabled") . '</span>';
            }
            return $html;
        });
    }
}
