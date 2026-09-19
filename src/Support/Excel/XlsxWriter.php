<?php

namespace Dcat\Admin\Support\Excel;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * 基于 OpenSpout 的 XLSX 导出（替代 dcat/easy-excel / box/spout）.
 */
class XlsxWriter
{
    /**
     * @var array<int, string>|array<string, string>|false
     */
    protected $headings = [];

    /**
     * @var array<int, array<string, mixed>>|null
     */
    protected $rows;

    /**
     * @var callable|null
     */
    protected $chunkCallback;

    public static function make(): self
    {
        return new self();
    }

    /**
     * @param  array<int, string>|array<string, string>|false  $headings
     */
    public function headings($headings): self
    {
        $this->headings = is_array($headings) || $headings === false ? $headings : [];

        return $this;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function data(array $rows): self
    {
        $this->rows = $rows;
        $this->chunkCallback = null;

        return $this;
    }

    /**
     * @param  callable(int): (array|null)  $callback
     */
    public function chunk(callable $callback): self
    {
        $this->chunkCallback = $callback;
        $this->rows = null;

        return $this;
    }

    public function download(string $filename): void
    {
        $writer = new Writer();

        try {
            $writer->openToBrowser($filename);

            $this->writeHeadingsRow($writer);

            if ($this->chunkCallback) {
                $times = 1;

                while ($batch = ($this->chunkCallback)($times)) {
                    $times++;

                    $this->writeBatch($writer, $this->normalizeBatch($batch));
                }
            } else {
                $this->writeBatch($writer, $this->rows ?? []);
            }

            $writer->close();
        } catch (\Throwable $e) {
            if (! headers_sent()) {
                header_remove();
            }

            throw $e;
        }
    }

    protected function writeHeadingsRow(Writer $writer): void
    {
        if ($this->headings === false) {
            return;
        }

        $labels = $this->headingLabels();

        if ($labels) {
            $writer->addRow(Row::fromValues($labels));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function writeBatch(Writer $writer, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $keys = $this->headingKeys($rows);

        if ($this->headings === false && $keys) {
            $writer->addRow(Row::fromValues(array_values($keys)));
        }

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($this->rowValues($row, $keys)));
        }
    }

    /**
     * @return array<int, string>
     */
    protected function headingLabels(): array
    {
        if (! is_array($this->headings) || $this->headings === []) {
            return [];
        }

        if ($this->isAssoc($this->headings)) {
            return array_values($this->headings);
        }

        return $this->headings;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    protected function headingKeys(array $rows): array
    {
        if (is_array($this->headings) && $this->headings !== [] && $this->isAssoc($this->headings)) {
            return array_keys($this->headings);
        }

        if (is_array($this->headings) && $this->headings !== []) {
            return $this->headings;
        }

        $first = $rows[0] ?? [];

        return array_keys($first);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     * @return array<int, mixed>
     */
    protected function rowValues(array $row, array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[] = $row[$key] ?? null;
        }

        return $values;
    }

    /**
     * @param  mixed  $batch
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeBatch($batch): array
    {
        if (empty($batch)) {
            return [];
        }

        if (is_object($batch) && method_exists($batch, 'toArray')) {
            $batch = $batch->toArray();
        }

        if (! is_array($batch)) {
            return [];
        }

        if ($batch !== [] && ! is_array(reset($batch))) {
            return [$batch];
        }

        return $batch;
    }

    protected function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
