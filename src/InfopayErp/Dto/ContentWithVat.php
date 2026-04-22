<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\ContentType;

final readonly class ContentWithVat extends Content
{
    /**
     * @param array<int, ItemWithVat>       $items
     * @param array<int, AmountsByVatGroup> $amountsByVatGroup
     */
    public function __construct(
        public array $items,
        public array $amountsByVatGroup,
        public AmountPayable $amountPayable,
    ) {}

    public function type(): ContentType
    {
        return ContentType::ContentWithVat;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contentType' => $this->type()->value,
            'items' => array_map(fn (ItemWithVat $i) => $i->toArray(), $this->items),
            'amountsByVatGroup' => array_map(fn (AmountsByVatGroup $g) => $g->toArray(), $this->amountsByVatGroup),
            'amountPayable' => $this->amountPayable->toArray(),
        ];
    }
}
