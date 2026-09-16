<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Esta clase nos permite realizar peticiones HTTP a otros servicios

class AccountController extends Controller
{
    private $service = "http://localhost:5000/api/accounts";

    public function index(){
        $response = Http::get($this->service);

        return response()->json($response->json(), $response->status());
    }

    public function show($id){
        $response = Http::get("{$this->service}/{$id}");

        return response()->json($response->json(), $response->status());
    }

    public function store(Request $request){
        $response = Http::post($this->service, $request->all());

        return response()->json($response->json(), $response->status());
    }

    public function update(Request $request, $id){
        $response = Http::put("{$this->service}/{$id}", $request->all());

        return response()->json($response->json(), $response->status());
    }

    public function destroy($id){
        $response = Http::delete("{$this->service}/{$id}");

        return response()->json($response->json(), $response->status());
    }
}
