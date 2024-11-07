<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function trackVisit()
    {
        $visit = Visitor::first();
        if (!$visit) {
            $visit = Visitor::create(['count' => 1]);
        } else {
            $visit->increment('count');
        }
        return response()->json(['count' => $visit->count]);
    }

    public function getVisitCount()
    {
        $visit = Visitor::first();
        return response()->json(['count' => $visit->count ?? 0]);
    }
}
