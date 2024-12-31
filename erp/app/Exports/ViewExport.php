<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeWriting;

class ViewExport implements FromView, ShouldAutoSize, WithEvents
{
    public $view_file;

    public $view_data;

    public function __construct($view = false, $data = false)
    {
        if ($view) {
            $this->setViewFile($view);
        }
        if ($data) {
            $this->setViewData($data);
        }
    }

    public function setViewFile($view)
    {
        $this->view_file = $view;
    }

    public function setViewData($data)
    {
        $this->view_data = $data;
    }

    public function view(): View
    {
        return view('__app.exports.'.$this->view_file, $this->view_data);
    }

    public function registerEvents(): array
    {
        return
        [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(100);
            },
            BeforeWriting::class => function (BeforeWriting $event) {
                $event->getWriter()
                    ->getDelegate()
                    ->getActiveSheet()
                    ->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $event->getWriter()
                    ->getDelegate()
                    ->getActiveSheet()
                    ->getHeaderFooter()
                    ->setOddHeader('&C&HPlease treat this document as confidential!');
                $event->getWriter()
                    ->getDelegate()
                    ->getActiveSheet()
                    ->getHeaderFooter()
                    ->setOddFooter('&L&B Cloud Telecoms &RPage &P of &N');
            },

        ];
    }
}
