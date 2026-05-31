<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MouvementStock;


class MouvementStockController extends Controller
{
    public function index()
    {
        $mouvements =
            MouvementStock::with(
                'article',
                'user'
            )
            ->latest()
            ->get();

        return view(
            'mouvements.index',
            compact('mouvements')

        );
    }
}
