<?php

namespace Dcat\Admin\Grid\Exporters;

use Dcat\Admin\Grid;
use Dcat\Admin\Support\Excel\XlsxWriter;

class ExcelExporter extends AbstractExporter
{
    public function __construct($titles = [])
    {
        parent::__construct($titles);
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        $filename = $this->getFilename().'.'.$this->extension;

        $exporter = XlsxWriter::make();

        if ($this->scope === Grid\Exporter::SCOPE_ALL) {
            $exporter->chunk(function (int $times) {
                return $this->buildData($times);
            });
        } else {
            $data = $this->buildData() ?: [];
            $exporter->data($data === [] ? [] : $data);
        }

        $exporter->headings($this->titles())->download($filename);

        exit;
    }
}
