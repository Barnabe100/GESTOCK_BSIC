<?php

namespace App\Http\Controllers\Etats;

use App\Http\Controllers\Controller;

use App\Models\Article;

use Barryvdh\DomPDF\Facade\Pdf;

use Maatwebsite\Excel\Facades\Excel;

use App\Exports\Etats\StockParArticleExport;
use Carbon\Carbon;

class EtatArticleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | STOCK PAR ARTICLE
    |--------------------------------------------------------------------------
    */

    // 🔥 EXCEL
    public function stockParArticleExcel()
    {
        return Excel::download(
            new StockParArticleExport,
            'stock_par_article_' . Carbon::now()->format('Ymd_His') . '.xlsx'
        );
    }

    // 🔥 PDF
    public function stockParArticlePdf()
    {
        $articles = Article::with([
            'categorie'
        ])
            ->where('archiver', 0)
            ->get();

        $pdf = Pdf::loadView(
            'etats.articles.stock_par_article',
            compact('articles')
        );

        $canvas = $pdf->getDomPDF()->getCanvas();

        $canvas->page_text(
            25,
            820,
            "Généré le : " . now()->format('d/m/Y à H:i:s'),
            null,
            9,
            [0, 0, 0]
        );

        $canvas->page_text(
            500,
            820,
            "Page {PAGE_NUM}/{PAGE_COUNT}",
            null,
            9,
            [0, 0, 0]
        );

        return $pdf->stream(
            'stock_par_article_' . Carbon::now()->format('Ymd_His') . '.pdf'
        );
    }
}
