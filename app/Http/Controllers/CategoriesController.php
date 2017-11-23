<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class CategoriesController extends Controller
{
    var $category_name_avoids = array('do not delete', 'search term', 'keywords');
    
    public function process_upload(Request $request)
    {
        $file = $request->file('categories_file');

        $spreadsheet = new Spreadsheet();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathName());       
        $worksheet = $spreadsheet->getActiveSheet();
        
        $worksheet_array = $this->worksheet_to_array($worksheet);
        
        //$category_hierarchy = $this->create_category_hierarchy($worksheet_array);

        $results = $this->save_categories_to_db($worksheet_array);
        
        if (View::exists('upload')) {
            return view('upload_complete', ['results' => $results]);
        }
        else
        {
            echo 'Not there mate';
        }
        
    }

    public function worksheet_to_array($worksheet)
    {
        $rows = [];
        foreach ($worksheet->getRowIterator() AS $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }
            $rows[] = $cells;
        }

        // assume first row is the headers
        unset($rows[0]);

        //print_r($rows);

        return $rows;
    }

    public function save_categories_to_db($array)
    {
        $no_of_inserts = 0;
        $blank_rows = 0;
        $no_of_duplicates = 0;
        $errors = array();

        foreach ($array as $rowNum => $row)
        {
            if ($row[0] == "")
            {
                array_push($errors, array('row_number' => $rowNum, 'row_data' => implode($row), 'reason' => 'Row is blank'));
                $blank_rows++;
            }
            else
            {          
                try 
                {
                    $level2CategoryName = $this->clean_category_name($row[1]);
                    $level3CategoryName = $this->clean_category_name($row[2]);
                    
                    if ($level3CategoryName !== false)
                    { 
                        if (DB::table('categories')->where('level_3_name', $level3CategoryName)->where('level_2_name', $level2CategoryName)->count() == 0)
                        {
                            DB::table('categories')->insert([
                                'level_1_name' => $this->clean_category_name($row[0]),
                                'level_2_name' => $level2CategoryName,
                                'level_3_name' => $level3CategoryName
                            ]);
                            $no_of_inserts++;
                        }
                        else
                        {
                            array_push($errors, array('row_number' => $rowNum, 'row_data' => implode($row, " : "), 'reason' => 'Duplicate ("'.$level3CategoryName.'" already exists in database)'));
                            $no_of_duplicates++;
                        }
                    }
                    else
                    {
                        if (DB::table('categories')->where('level_2_name', $level2CategoryName)->where('level_3_name', $level2CategoryName)->count() == 0)
                        {
                            DB::table('categories')->insert([
                                'level_1_name' => $this->clean_category_name($row[0]),
                                'level_2_name' => $level2CategoryName,
                                'level_3_name' => $level2CategoryName
                            ]);
                            $no_of_inserts++;
                        }
                        else
                        {
                            array_push($errors, array('row_number' => $rowNum, 'row_data' => implode($row, " : "), 'reason' => 'Duplicate ("'.$level2CategoryName.'" already exists in database)'));
                            $no_of_duplicates++;                            
                        }
                    }
                    
                } catch (Exception $e) {
                    report($e);
                    array_push($errors, array('row_number' => $rowNum, 'row_data' => implode($row), 'reason' => $e));
            
                    return false;
                }
            }
        }
       
        return array(
            'total_rows' => count($array),
            'blank_rows' => $blank_rows,
            'duplicate_rows' => $no_of_duplicates,
            'total_inserts' => $no_of_inserts,
        );
    }

    
    public function clean_category_name($category_name)
    {
        if (trim($category_name) !== "")
        {
            if (strpos($category_name, "-"))
            {
                $category_name_array = explode(" -", $category_name);

                foreach ($category_name_array as $no => $potential_name)
                {
                    if (preg_match( "/[a-zA-Z]{3}\d{6}/", $potential_name))
                    {
                        $catIDRemovedName = trim(preg_replace( "/[a-zA-Z]{3}\d{6}/", "", $potential_name));

                        if ($catIDRemovedName == "" || strlen($catIDRemovedName) <= 3)
                        {
                            unset($category_name_array[$no]);
                        }
                        else
                        {
                            $category_name_array[$no] = trim(preg_replace( "/[a-zA-Z]{3}\d{6}/", "", $potential_name));
                        }     
                    }
                    foreach ($this->category_name_avoids as $numb => $stopWord)
                    {
                        if (strpos(strtolower(trim($potential_name)), $stopWord) !== false )
                        {
                            unset($category_name_array[$no]);
                        }
                    }
                }

                $category_name_array = array_values($category_name_array);

                if (count($category_name_array) > 1)
                {
                    return $this->prettyfy_category_name($category_name_array[1]);                 
                }
                else
                {
                    if (array_key_exists(0,$category_name_array))
                    {
                        if ($category_name_array[0] != "")
                        {
                            return $this->prettyfy_category_name($category_name_array[0]); 
                        }
                        else
                        {
                            return false;
                        }   
                    }
                    else
                    {
                        return false;
                    }
                }
            }
            else
            {
                if ($pos = strpos($category_name, 'DIY'))
                {
                    return $this->prettyfy_category_name(substr($category_name, $pos));
                }
                else
                {
                    return $this->prettyfy_category_name($category_name);
                }      
            }
        }
        else
        {
            return false;
        }
    }

    public function prettyfy_category_name($category_name)
    {
        return mb_convert_case(trim($category_name), MB_CASE_TITLE, "UTF-8");
    }

    public function create_category_hierarchy($array)
    {
        $category_hierarchy = array();

        foreach ($array as $rowNum => $row)
        {
            foreach ($row as $columnNo => $categoryValue)
            {
                if ($categoryValue = $this->clean_category_name($categoryValue))
                {
                    if (!in_array($categoryValue, $this->category_name_avoids))
                    {
                        // Level 1 Hierarchy
                        if ($columnNo == 0)
                        {
                            if (!array_key_exists($categoryValue, $category_hierarchy))
                            {
                                $category_hierarchy[$categoryValue] = array();
                            }
                        }
                        // Level 2 Hierarchy
                        if ($columnNo == 1)
                        {
                            if (!in_array($row[$columnNo-1], $this->category_name_avoids))
                            {
                                if (!array_key_exists($categoryValue, $category_hierarchy[$row[$columnNo-1]]))
                                {
                                    $category_hierarchy[$row[$columnNo-1]][$categoryValue] = array();
                                }
                            }
                        }
                        // Level 3 Hierarchy
                        if ($columnNo == 2)
                        {
                            if (!in_array($row[$columnNo-1], $this->category_name_avoids))
                            {
                                if (!array_key_exists($categoryValue, $category_hierarchy[$row[$columnNo-2]][$row[$columnNo-1]]))
                                {
                                    $category_hierarchy[$row[$columnNo-2]][$row[$columnNo-1]][$categoryValue] = array();
                                }
                            }
                        }                    
                    }
                }
            }
        }

        return $array;
    }

    public function view_categories(Request $request)
    {
        $categories = DB::table('categories')->get();
        
        if ($categories->count() > 0)
        {
            return view('categories', ['categories' => $categories]);
        }
        else
        {
            return view('upload');
        }        
    }

    public function refresh_category(Request $request, $category_id)
    {      
        // Get Category Information
        $category = DB::table('categories')->get()->where('id', $category_id)->first();

        // Get Sites List
        $sites = DB::table('sites')->get();
      
        foreach ($sites as $site)
        {
            // Generate List of Search Suggestions (starting with original)
            $suggestions = $this->generate_search_suggestions($category->level_3_name);

            $potentialMatches = array();

            foreach ($suggestions as $suggestion)
            {
                $result = $this->get_search_suggestions($suggestion, strtolower(str_replace(" ","", $site->name)), $site->search_suggestions_uri);

                if ($result)
                {
                    $potentialMatches = array_merge($potentialMatches, $result);
                }             
            }

            return response()->json($potentialMatches);
        }
    }

    public function get_search_suggestions($original_suggestion, $site_config_name, $url)
    {
        // Clean up Suggestion: Remove spaces
        $suggestion = str_replace(" ", "%20", $original_suggestion);
        //echo '<p>Cleaned up Suggestion: '.$suggestion.'</p>';
        // Get site config name
        if ($site_config_name == "homedepot")
        {           
            // Swap out {search_term} for suggestion in URL
            $url = preg_replace("/\s?\{[^}]+\}/", $suggestion, $url);
            
            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', $url);
            $response = $res->getBody();

            // Clean up response
            $end_removed = substr($response, 0, count($response)-3);
            $result = substr($end_removed,17);
            
            // Convert to JSON
            $json = json_decode($result);
            
            if ($json->r == null)
            {
                //echo '<p>No result found for <b>'.$suggestion.'</b></p><br><br><br>';
                return false;
            }
            else
            {
                //echo '<p>These results were found for <b>'.$suggestion.'</b></p>';
                //echo '<pre>';
                //var_dump($json->r);
                //echo '</pre>';
                //echo '<br><br><br>';
                $matchesArray = array();

                foreach ($json->r as $option)
                {
                    //var_dump($option);
                    if (isset($option->v))
                    {
                        foreach ($option->v as $potentialMatch)
                        {
                            $result_array = array(
                                'original_search_term' => $original_suggestion,
                                'category_url' => $potentialMatch->k,
                                'suggested_match' => $potentialMatch->t,
                            );
                            
                            array_push($matchesArray, $result_array);
                        }
                    }
                }

                return $matchesArray;
            }
        }
    }

    public function generate_search_suggestions($original_suggestion)
    {
        $suggestions = array($original_suggestion);

        if (strpos($original_suggestion, " ") !== false)
        {
            // Split words
            $individualWords = explode (" ", $original_suggestion);

            foreach ($individualWords as $no => $word)
            {
                if (strlen($word) > 3)
                {
                    array_push($suggestions, $word);
                }
                else
                {
                    //echo '<p>Word: '.$word.'</p>'; exit;
                    // If the middle word is 'and' remove it and add a futher suggestion of the two words combined
                    if ($word == "and" || $word == "And" || $word == "&")
                    {
                        array_push($suggestions, $individualWords[$no-1].' '.$individualWords[$no+1]);
                    }
                }
            }
        }

        return $suggestions;
    }
}
