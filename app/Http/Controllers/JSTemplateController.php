<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class JSTemplateController extends Controller
{
    public function load_template(Request $request)
    {
        if (View::exists('jsviews.'.$request->input('template_name')))
        {
            $suggestions = $request->input('suggestions');

            //print_r($suggestions); exit;

            $view = View::make('jsviews.'.$request->input('template_name'), [
                'title' => $request->input('title'), 
                'suggestions' => $suggestions
            ]);

            return response()->json(['data' => $view->render()]);
        }
        else
        {
            return response()->json(['error' => 'Template file ('.$request->input('template_name').') not found']);
        }
    }
}
