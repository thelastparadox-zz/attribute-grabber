<?php

use Illuminate\Database\Seeder;

class CrawlersTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $home_depot_site_id = DB::table('sites')->insertGetId([
            'site_name' => 'Home Depot',
            'start_url' => 'https://www.homedepot.com/',
            'search_url' => 'https://www.homedepot.com/s/?search={search_term}',
            'search_suggestions_url' => 'https://www.thdws.com/TA2/search?term={search_term}&e=21&callback=termCallback',
            'request_delay' => 3,
            'schedule' => ''
        ]);

        $home_depot_category_page_id = DB::table('sites_pages')->insertGetId([
            'site_id' => $home_depot_site_id,
            'page_name' => 'Home Depot Category Page',
            'page_type' => 'category',
            'url_pattern' => '\b\/b\/\b',
            'example_url' => 'https://www.homedepot.com/b/Tools-Power-Tools-Drills/Cordless/N-5yc1vZc27fZ1z140i3',
            'created_at' => date("Y-m-d H:i:s"),
        ]);     

        DB::table('sites_pages_items')->insert([
            [    
                'site_page_id' => $home_depot_category_page_id,
                'item_name' => 'Number of Products',
                'item_type' => 'integer',
                'identifier' => '#allProdCount',
                'identifier_type' => 'css',
                'db_column_name' => 'total_products',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $home_depot_category_page_id,
                'item_name' => 'Breadcrumbs',
                'item_type' => 'list',
                'identifier' => '.breadcrumb__item',
                'identifier_type' => 'css',
                'db_column_name' => 'breadcrumbs',
                'created_at' => date("Y-m-d H:i:s"),
            ]
        ]);    

        $home_depot_product_page_id = DB::table('sites_pages')->insertGetId([
            'site_id' => $home_depot_site_id,
            'page_name' => 'Home Depot Product Page',
            'page_type' => 'product',
            'url_pattern' => '\b\/p\/\b',
            'example_url' => 'https://www.homedepot.com/p/DEWALT-20-Volt-Max-Lithium-Ion-Premium-XR-Brushless-Cordless-Combo-Kit-7-Tool-with-2-Batteries-5Ah-Charger-and-Bag-DCK694P2DCG413B/303208137',
            'created_at' => date("Y-m-d H:i:s"),
        ]);       

        DB::table('sites_pages_items')->insert([
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Product Price',
                'item_type' => 'price',
                'identifier' => '#ajaxPrice',
                'identifier_type' => 'css',
                'db_column_name' => 'price',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Product Title',
                'item_type' => 'text',
                'identifier' => '.product-title__title',
                'identifier_type' => 'css',
                'db_column_name' => 'title',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Available in Store',
                'item_type' => 'boolean',
                'identifier' => '#availableInLocalStore',
                'identifier_type' => 'css',
                'db_column_name' => 'in_store',
                'created_at' => date("Y-m-d H:i:s"),
            ],           
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Store SKU',
                'item_type' => 'integer',
                'identifier' => '#product_store_sku',
                'identifier_type' => 'css',
                'db_column_name' => 'sku_store',
                'created_at' => date("Y-m-d H:i:s"),
            ],           
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Web SKU',
                'item_type' => 'integer',
                'identifier' => '#product_internet_number',
                'identifier_type' => 'css',
                'db_column_name' => 'sku_web',
                'created_at' => date("Y-m-d H:i:s"),
            ],  
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Main Image',
                'item_type' => 'image',
                'identifier' => '#mainImage',
                'identifier_type' => 'css',
                'db_column_name' => 'image_primary',
                'created_at' => date("Y-m-d H:i:s"),
            ], 
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Model Number',
                'item_type' => 'integer',
                'identifier' => '.modelNo',
                'identifier_type' => 'css',
                'db_column_name' => 'model_number',
                'created_at' => date("Y-m-d H:i:s"),
            ],               
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Product Description',
                'item_type' => 'text',
                'identifier' => '.main_description',
                'identifier_type' => 'css',
                'db_column_name' => 'description',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Product Specifications',
                'item_type' => 'list',
                'identifier' => '.specs__table',
                'identifier_type' => 'css',
                'db_column_name' => 'specifications',
                'created_at' => date("Y-m-d H:i:s"),
            ],  
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Review Rating',
                'item_type' => 'decimal',
                'identifier' => '.BVRRCustomRatingWrapper .BVRRRatingNumber',
                'identifier_type' => 'css',
                'db_column_name' => 'review_rating',
                'created_at' => date("Y-m-d H:i:s"),
            ], 
            [    
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Number of Reviews',
                'item_type' => 'integer',
                'identifier' => '#BVRRCustomRatingCountId',
                'identifier_type' => 'css',
                'db_column_name' => 'number_of_reviews',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $home_depot_product_page_id,
                'item_name' => 'Breadcrumbs',
                'item_type' => 'list',
                'identifier' => '.breadcrumb__item',
                'identifier_type' => 'css',
                'db_column_name' => 'breadcrumbs',
                'created_at' => date("Y-m-d H:i:s"),
            ]                                           
        ]);        

        DB::table('sites_pages_avoid')->insert([
            [
                'site_id' => $home_depot_site_id,
                'page_name' => 'My Account',
                'url_pattern' => '\b\/account\/\b',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_id' => $home_depot_site_id,
                'page_name' => 'Javascript',
                'url_pattern' => '\b\/javascript\/\b',
                'created_at' => date("Y-m-d H:i:s"),
            ]
        ]);


        ///////////////////////
        /// Screwfix
        ///////////////////////

        $screwfix_site_id = DB::table('sites')->insertGetId([
            'site_name' => 'Screwfix',
            'start_url' => 'https://www.screwfix.com/',
            'search_url' => 'https://www.screwfix.com/search?search={search_term}',
            'search_suggestions_url' => 'https://query.published.live1.suggest.eu1.fredhopperservices.com/sfx/json?callback=jQuery111106400341572847486_1511393144046&search=screw&scope=%2F%2Fscrewfix%2Fen_GB%2Favailability%3E%7Bscrewfix%7D&_=1511393144047',
            'request_delay' => 3,
            'schedule' => ''
        ]);

        $screwfix_category_page_id = DB::table('sites_pages')->insertGetId([
            'site_id' => $screwfix_site_id,
            'page_name' => 'Screwfix Category Page',
            'page_type' => 'category',
            'url_pattern' => '\b\/c\/\b',
            'example_url' => 'https://www.screwfix.com/c/outdoor-gardening/fencing/cat840542',
            'created_at' => date("Y-m-d H:i:s"),
        ]);  

        DB::table('sites_pages_items')->insert([
            [    
                'site_page_id' => $screwfix_category_page_id,
                'item_name' => 'Number of Products',
                'item_type' => 'integer',
                'identifier' => '.h1wrapper__title-category-itemfound',
                'identifier_type' => 'css',
                'db_column_name' => 'total_products',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $screwfix_category_page_id,
                'item_name' => 'Breadcrumbs',
                'item_type' => 'list',
                'identifier' => '#breadcrumb_container_top',
                'identifier_type' => 'css',
                'db_column_name' => 'breadcrumbs',
                'created_at' => date("Y-m-d H:i:s"),
            ]
        ]);  
        
        $screwfix_product_page_id = DB::table('sites_pages')->insertGetId([
            'site_id' => $screwfix_site_id,
            'page_name' => 'Screwfix Product Page',
            'page_type' => 'product',
            'url_pattern' => '\b\/p\/\b',
            'example_url' => 'https://www.homedepot.com/p/DEWALT-20-Volt-Max-Lithium-Ion-Premium-XR-Brushless-Cordless-Combo-Kit-7-Tool-with-2-Batteries-5Ah-Charger-and-Bag-DCK694P2DCG413B/303208137',
            'created_at' => date("Y-m-d H:i:s"),
        ]);
        
        DB::table('sites_pages_items')->insert([
            [    
                'site_page_id' => $screwfix_product_page_id,
                'item_name' => 'Product Price',
                'item_type' => 'price',
                'identifier' => '#product_price',
                'identifier_type' => 'css',
                'db_column_name' => 'price',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $screwfix_product_page_id,
                'item_name' => 'Product Title',
                'item_type' => 'text',
                'identifier' => '#product_description',
                'identifier_type' => 'css',
                'db_column_name' => 'title',
                'created_at' => date("Y-m-d H:i:s"),
            ]
        ]);

        ///////////////////////
        /// B&Q
        ///////////////////////

        $bandq_site_id = DB::table('sites')->insertGetId([
            'site_name' => 'B&Q',
            'start_url' => 'http://www.diy.com/',
            'search_url' => 'http://www.diy.com/search?Ntt={search_term}',
            'search_suggestions_url' => '',
            'request_delay' => 3,
            'schedule' => ''
        ]);

        $bandq_category_page_id = DB::table('sites_pages')->insertGetId([
            'site_id' => $bandq_site_id,
            'page_name' => 'B&Q Category Page',
            'page_type' => 'category',
            'url_pattern' => '\b.cat\b',
            'example_url' => 'http://www.diy.com/departments/painting-decorating/paint-wood-treatments/interior-emulsion-paint/wall-ceiling-paint/DIY1620275.cat?icamp=PWD_Int_Emulsion_Depts_Wall_Paint_T',
            'created_at' => date("Y-m-d H:i:s"),
        ]);  

        DB::table('sites_pages_items')->insert([
            [    
                'site_page_id' => $bandq_category_page_id,
                'item_name' => 'Number of Products',
                'item_type' => 'integer',
                'identifier' => '.h1wrapper__title-category-itemfound',
                'identifier_type' => 'css',
                'db_column_name' => 'total_products',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $bandq_category_page_id,
                'item_name' => 'Breadcrumbs',
                'item_type' => 'list',
                'identifier' => '.breadcrumb',
                'identifier_type' => 'css',
                'db_column_name' => 'breadcrumbs',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $bandq_category_page_id,
                'item_name' => 'Category ID',
                'item_type' => 'url',
                'identifier' => '/\b[a-zA-Z]{3}\d{7,9}\b/',
                'identifier_type' => 'regex',
                'db_column_name' => 'category_id',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $bandq_category_page_id,
                'item_name' => 'Category Name',
                'item_type' => 'text',
                'identifier' => '.wrap-main-title',
                'identifier_type' => 'css',
                'db_column_name' => 'category_name',
                'created_at' => date("Y-m-d H:i:s"),
            ]            
        ]);  
        
        $bandq_product_page_id = DB::table('sites_pages')->insertGetId([
            'site_id' => $bandq_site_id,
            'page_name' => 'B&Q Product Page',
            'page_type' => 'product',
            'url_pattern' => '\b.prd\b',
            'example_url' => 'http://www.diy.com/departments/colours-bathroom-light-rain-soft-sheen-emulsion-paint-2-5l/1270091_BQ.prd?PWD_Int_Emulsion_Colours_Grey',
            'created_at' => date("Y-m-d H:i:s"),
        ]);
        
        DB::table('sites_pages_items')->insert([
            [    
                'site_page_id' => $bandq_product_page_id,
                'item_name' => 'Product Price',
                'item_type' => 'price',
                'identifier' => '#product_price',
                'identifier_type' => 'css',
                'db_column_name' => 'price',
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'site_page_id' => $bandq_product_page_id,
                'item_name' => 'Product Title',
                'item_type' => 'text',
                'identifier' => '#product_description',
                'identifier_type' => 'css',
                'db_column_name' => 'title',
                'created_at' => date("Y-m-d H:i:s"),
            ]
        ]);
    }
}
