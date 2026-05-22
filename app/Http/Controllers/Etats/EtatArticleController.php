<?php

namespace App\Http\Controllers\Etats;

use App\Http\Controllers\Controller;

use App\Models\Article;

use Barryvdh\DomPDF\Facade\Pdf;

use Maatwebsite\Excel\Facades\Excel;

use App\Exports\Etats\StockParArticleExport;

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
            'stock_par_article.xlsx'
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

        return $pdf->stream(
            'stock_par_article.pdf'
        );
    }
}
