<?php

namespace PrismArea\lang;

use pocketmine\utils\SingletonTrait;

class LangManager
{
    use SingletonTrait;

    public const FALLBACK_LANGUAGE = "en_US";

    /** @var array<string, Lang> $langs */
    private array $langs = [];

    public function __construct()
    {
        self::setInstance($this);
    }

    /**
     * @param string $path
     * @return void
     */
    public function load(string $path): void
    {
        // Load language files from the specified path
        // This method should read language files and initialize the language system
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Language path does not exist: $path");
        }

        $files = scandir($path);
        if ($files === false) {
            throw new \RuntimeException("Failed to read directory: $path");
        }

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'ini') {
                // Load the language file
                $langCode = pathinfo($file, PATHINFO_FILENAME);
                $this->loadLangFile($path . DIRECTORY_SEPARATOR . $file, $langCode);
            }
        }
    }

    /**
     * @param string $filePath
     * @param string $langCode
     * @return void
     */
    private function loadLangFile(string $filePath, string $langCode): void
    {
        // Check if the file exists
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Language file does not exist: $filePath");
        }

        // Check if the file is readable
        if (!is_readable($filePath)) {
            throw new \RuntimeException("Language file is not readable: $filePath");
        }

        // Parse the INI file
        $contents = parse_ini_file($filePath, scanner_mode: INI_SCANNER_RAW);
        if ($contents === false) {
            throw new \RuntimeException("Failed to parse language file: $filePath");
        }

        // Create a new Lang instance and add it to the langs array
        $this->langs[strtolower($langCode)] = new Lang(
            code: strtolower($langCode),
            name: $contents['name'] ?? 'Unknown Language',
            contents: $contents
        );
    }

    /**
     * Get a language by its code.
     *
     * @param string $langCode
     * @return Lang
     */
    public function getLang(string $langCode): Lang
    {
        if(isset($this->langs[strtolower($langCode)])) {
            return $this->langs[strtolower($langCode)];
        }

        $split = explode('_', $langCode);
        $values = array_filter($split, fn($part) => str_starts_with($part, array_shift($split)));
        $fallbackCode = array_shift($values);

        if(isset($this->langs[strtolower($fallbackCode)])) {
            // If the language is not found, return the fallback language
            return $this->langs[strtolower($fallbackCode)];
        }

        // If the language is not found, return the fallback language
        return $this->langs[strtolower(self::FALLBACK_LANGUAGE)];
    }
}