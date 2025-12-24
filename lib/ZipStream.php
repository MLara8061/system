<?php
/**
 * ZipStream Stub - Implementación usando ZipArchive de PHP
 * Compatible con ZipStream\ZipStream v4.x
 */

namespace ZipStream;

use ZipArchive;

/**
 * Stub de ZipStream usando ZipArchive nativo.
 * Implementa los métodos mínimos que PhpSpreadsheet invoca.
 */
class ZipStream
{
    private $fileHandle;
    private $tempFile;
    private $entries = [];

    /**
     * Acepta la firma ZipStream real: (string $name = null, Archive $options = null)
     * o la firma v3: ($name, $sendHttpHeaders, $outputStream, ...)
     */
    public function __construct(
        ?string $name = null,
        $options = null,
        $outputStream = null,
        ?bool $defaultEnableZeroHeader = null,
        ?bool $enableZip64 = null
    ) {
        // Si $options es Archive, extraer valores
        if ($options instanceof \ZipStream\Option\Archive) {
            $this->fileHandle = $options->getOutputStream();
        } else {
            // Firma antigua: segundo argumento es sendHttpHeaders, tercero stream
            $this->fileHandle = $outputStream;
        }

        $this->tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zstream_' . uniqid('', true) . '.zip';
    }

    /**
     * Agrega un archivo al ZIP (firma esperada: addFile(path, content)).
     */
    public function addFile(string $path, string $content): void
    {
        $this->entries[$path] = $content;
    }

    /**
     * Agrega un archivo desde disco.
     */
    public function addFileFromPath(string $path, string $archivePath = ''): void
    {
        if (is_file($path)) {
            $this->entries[$archivePath ?: basename($path)] = file_get_contents($path);
        }
    }

    /**
     * Escribe el ZIP y devuelve el binario.
     */
    public function finish(): string
    {
        $data = '';

        if (extension_loaded('zip')) {
            $zip = new ZipArchive();
            if ($zip->open($this->tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($this->entries as $path => $content) {
                    $zip->addFromString($path, $content);
                }
                $zip->close();
                if (file_exists($this->tempFile)) {
                    $data = file_get_contents($this->tempFile) ?: '';
                }
            }
        }

        if (is_resource($this->fileHandle)) {
            fwrite($this->fileHandle, $data);
        }

        return $data;
    }

    public function __toString(): string
    {
        return '';
    }

    public function __destruct()
    {
        if ($this->tempFile && file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
    }
}

// Alias para compatibilidad global
class_alias('ZipStream\\ZipStream', 'ZipStream');

// Stub de Option\Archive para evitar class_exists true
namespace ZipStream\Option;

class Archive
{
    private bool $enableZip64 = false;
    private $outputStream = null;
    private bool $sendHttpHeaders = false;
    private bool $defaultEnableZeroHeader = false;

    public function setEnableZip64(bool $enable): self
    {
        $this->enableZip64 = $enable;

        return $this;
    }

    public function setOutputStream($stream): self
    {
        $this->outputStream = $stream;

        return $this;
    }

    public function setSendHttpHeaders(bool $send): self
    {
        $this->sendHttpHeaders = $send;

        return $this;
    }

    public function setDefaultEnableZeroHeader(bool $enable): self
    {
        $this->defaultEnableZeroHeader = $enable;

        return $this;
    }

    public function getOutputStream()
    {
        return $this->outputStream;
    }
}
