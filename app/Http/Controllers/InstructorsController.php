<?php

namespace App\Http\Controllers;

use App\Models\Instructors;
use Illuminate\Http\Request;

class InstructorsController extends Controller
{
    public function index()
    {
        return view('pages.instructors.index', [
            'instructors' => Instructors::all(),
        ]);
    }
}
