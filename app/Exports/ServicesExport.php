<?php

namespace App\Exports;

use App\Models\Service;
use App\Models\Departement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServicesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {


        return Service::with(['departement'])
            ->where('archiver', 0)
            ->get()
            ->map(function ($service) {

                return [

                    'code' => $service->code,

                    'nom' => $service->nom,
                    'localisation' => $service->localisation,

                    'departement' => $service->departement->nom ?? '',

                ];
            });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Nom',
            'Localisation',
            'Departement'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
