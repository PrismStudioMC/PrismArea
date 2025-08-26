<?php

namespace PrismArea\lang;

use pocketmine\utils\TextFormat;
use PrismArea\types\Translatable;

class Lang
{
    public function __construct(
        private string $code,
        private string $name,
        private array  $contents = []
    )
    {
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    /**
     * Parses the given Translatable object with optional arguments.
     *
     * @param Translatable|string $translatable
     * @param mixed ...$args
     * @return string
     */
    public function parse(Translatable|string $translatable, mixed ...$args): string
    {
        $text = is_string($translatable) ? $translatable : $translatable->getText();
        $value = $this->contents[$text] ?? $text;
        $value = str_replace(["{LINE}", "{SPACE}"], ["\n", " "], $value);

        if (!empty($args)) {
            $formattedArgs = array_map(function ($arg) {
                return is_float($arg) ? number_format($arg, 2, ',') : strval($arg);
            }, $args);
            $value = vsprintf($value, $formattedArgs);
        }

        return TextFormat::colorize($value);
    }
}