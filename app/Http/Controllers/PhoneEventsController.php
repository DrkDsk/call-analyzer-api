<?php

namespace App\Http\Controllers;

use App\Imports\PhoneRecordsImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PhoneEventsController extends Controller
{
    public function readFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new PhoneRecordsImport, $request->file('file'));

        return response()->json(['message' => 'File imported successfully']);
    }
}
