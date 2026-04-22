<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\ContentType;

final readonly class ContentWithoutVat extends Content
{
    /** @param array<int, ItemWithoutVat> $items */
    public function __construct(
        public array $items,
        public string $reasonNoVat,
        public AmountPayableWithoutVat $amountPayable,
        public ?DiscountWithoutVat $discount = null,
    ) {}

    public function type(): ContentType
    {
        return ContentType::ContentWithoutVat;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'contentType' => $this->type()->value,
            'items' => array_map(fn (ItemWithoutVat $i) => $i->toArray(), $this->items),
            'reasonNoVAT' => $this->reasonNoVat,
            'amountPayable' => $this->amountPayable->toArray(),
        ];
        if ($this->discount !== null) {
            $out['discount'] = $this->discount->toArray();
        }
        return $out;
    }
}
