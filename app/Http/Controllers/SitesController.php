<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class SitesController extends Controller
{ 
    public function sites_home(Request $request)
    {
        try {
            $sites = DB::table('sites')->get();
        } catch (Exception $e) {
            
            echo 'DB not connected:'.$e->getMessage()."\n"; exit;
        }
        

        foreach ($sites as $site)
        {
            // Get Total Categories

            $categories = DB::table('categories')->get()->where('site_id', $site->id)->count();
            $products = DB::table('categories')->get()->where('site_id', $site->id)->sum('total_products');

            $site->totalproducts = $products;
            $site->totalcategories = $categories;
        }

        //var_dump($sites); exit;

        return view('sites', ['sites' => $sites]);
    }
}
