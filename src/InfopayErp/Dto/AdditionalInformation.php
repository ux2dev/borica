<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class AdditionalInformation
{
    public function __construct(
        public ?string $notes = null,
        public ?string $orderId = null,
        public ?string $newVehicleDescription = null,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        $out = [];
        if ($this->notes !== null) {
            $out['notes'] = $this->notes;
        }
        if ($this->orderId !== null) {
            $out['orderId'] = $this->orderId;
        }
        if ($this->newVehicleDescription !== null) {
            $out['newVehicleDescription'] = $this->newVehicleDescription;
        }
        return $out;
    }
}
