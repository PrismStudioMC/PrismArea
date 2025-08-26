<?php

namespace PrismArea\extensions;

use pocketmine\event\Listener;

class Extension implements Listener
{
    public function __construct(
        protected string $name,
    )
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}