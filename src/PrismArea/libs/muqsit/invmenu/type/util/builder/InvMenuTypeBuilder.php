<?php

declare(strict_types=1);

namespace PrismArea\libs\muqsit\invmenu\type\util\builder;

use PrismArea\libs\muqsit\invmenu\type\InvMenuType;

interface InvMenuTypeBuilder{

	public function build() : InvMenuType;
}