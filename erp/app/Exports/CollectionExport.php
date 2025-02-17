<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CollectionExport implements FromCollection, WithStyles, WithStrictNullComparison, WithColumnFormatting, WithHeadings, WithEvents, ShouldAutoSize
{
    public $row_colors = [];
    public $total_fields = [];
    public function setStyles($data)
    {
        $this->worksheet_style = $data;
    }

    public function setWidths($data)
    {
        $this->worksheet_widths = $data;
    }

    public function setLastRowBold($bold)
    {
        $this->last_row_bold = $bold;
    }

    public function setTotalFields($arr)
    {
        $this->total_fields = $arr;
    }

    public function setData($data)
    {
        $this->data_for_export = collect($data);
    }

    public function setRightColumns($data)
    {
        $this->right_columns = $data;
    }
    public function setRowColor($index, $color)
    {
        $this->row_colors[] = ['index'=>$index,'color'=>$color];
    }

    public function seCurrencyColumns($data, $currency = 'ZAR')
    {
        $this->right_columns = $data;
        $column_formats = [];
        foreach ($data as $col) {
            if ($currency == 'ZAR') {
                $column_formats[$col] = NumberFormat::FORMAT_NUMBER_00;
            } else {
                $column_formats[$col] = '0.000';
            }
        }
        $this->column_formats = $column_formats;
    }

    public function collection()
    {   
        if(count($this->total_fields) > 0){
            $first_row = $this->data_for_export->first();
            $total_row = [];
      
            foreach($first_row as $k => $v){
                if(!in_array($k,$this->total_fields)){
                   $total_row[$k] = '';
                }else{
                   $total_row[$k] = $this->data_for_export->sum($k); // Calculate the total amount
                }
            }
            
            $this->data_for_export->push($total_row); // Add a row with the total amount
        }
        return $this->data_for_export;
    }

    public function styles($sheet)
    {
         if($this->last_row_bold){
            $sheet->getStyle('A' . $sheet->getHighestRow() . ':'.$sheet->getHighestColumn().$sheet->getHighestRow())->getFont()->setBold(true);
        }
        if(count($this->total_fields) > 0){
            $sheet->getStyle('A' . $sheet->getHighestRow() . ':'.$sheet->getHighestColumn().$sheet->getHighestRow())->getFont()->setBold(true);
        }
        if (!empty($this->worksheet_style)) {
            $styles = array_merge([ 1    => ['font' => ['bold' => true]]], $this->worksheet_style);
            return $styles;
        } else {
            return [ 1    => ['font' => ['bold' => true]]];
        }
  
    }

    public function columnWidths(): array
    {
        if (empty($this->worksheet_widths)) {
            return [];
        }
        return $this->worksheet_widths;
    }

    public function headings(): array
    {
       
        return array_keys((array) $this->data_for_export->first());
    }

    public function columnFormats(): array
    {
        if (empty($this->column_formats)) {
            return [];
        }
        return $this->column_formats;
    }

    public function registerEvents(): array
    {
        if (!empty($this->right_columns) || !empty($this->row_colors)) {
            $settings = [];
            if (!empty($this->right_columns)) {
                $settings['right_columns'] = $this->right_columns;
            }
            if (!empty($this->row_colors)) {
                $settings['row_colors'] = $this->row_colors;
            }

            return [
                AfterSheet::class => function (AfterSheet $event) use ($settings) {
                    foreach ($settings as $key => $setting) {
                        if ($key == 'right_columns') {
                            foreach ($setting as $right_column) {
                                $event->sheet->getDelegate()->getStyle($right_column.':'.$right_column)
                                ->getAlignment()
                                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                            }
                        }
                        if ($key == 'row_colors') {
                            foreach ($setting as $row_color) {
                                $event->sheet->getDelegate()->getStyle($row_color['index'])
                                ->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB($row_color['color']);
                            }
                        }
                    }
                },
            ];
        } else {
            return [];
        }
    }
}
