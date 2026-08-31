<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;

class SchoolClassesController extends Controller
{
    
    public function index()
    {
        //
    } 

    
    public function CreateSchoolClass(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'course' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'integer', 'between:1,8'],
            'year' => ['required', 'integer', 'between:2000,' . date('Y')],
        ]);

        $SchoolClass = SchoolClass::create($validated);
        
        return response()->json([
            'message' => 'Grupo criado com sucesso.',
            'school_class' => $SchoolClass
        ], 201);

    }

    
    public function show(string $id)
    {
        
    }

    
    public function update(Request $request, string $id)
    {
        //
    }

    
    public function destroy(string $id)
    {
        //
    }
}
