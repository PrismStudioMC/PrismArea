<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\session;

use PrismArea\libs\muqsit\invmenu\InvMenu;
use PrismArea\libs\muqsit\invmenu\type\graphic\InvMenuGraphic;

final class InvMenuInfo
{
    public function __construct(
        public readonly InvMenu $menu,
        public readonly InvMenuGraphic $graphic,
        public readonly ?string $graphic_name
    ) {
    }
}
