<?php

class REST_Api_Filter_Fields {

  public function __construct(){
    add_action('rest_api_init',array($this,'init'),20);
  }


/**
 * Register the fields functionality for all posts.
 * Because of the 12 you can also use the filter functionality for custom posts
 */
public function init(){

  // Get all public post types, default includes 'post','page','attachment' and custom types added before 'init', 20
  $post_types = get_post_types(array('public' => true), 'objects');

  foreach ($post_types as $post_type) {

    //Test if this posttype should be shown in the rest api.
    $show_in_rest = ( isset( $post_type->show_in_rest ) && $post_type->show_in_rest ) ? true : false;
    if($show_in_rest) {

      // We need the postname to enable the filter.
      $post_type_name = $post_type->name;

      //die($post_type_name);

      // Add de filter. The api uses eg. 'rest_prepare_post' with 3 parameters.
      add_filter('rest_prepare_'.$post_type_name,array($this,'filter_magic'),20,3);
    }

  }

  $tax_types = get_taxonomies(array('public' => true), 'objects');

  foreach ($tax_types as $tax_type) {

    //Test if this posttype should be shown in the rest api.
    $show_in_rest = ( isset( $tax_type->show_in_rest ) && $tax_type->show_in_rest ) ? true : false;
    if($show_in_rest) {

      // We need the postname to enable the filter.
      $tax_type_name = $tax_type->name;

      //die($post_type_name);

      // Add de filter. The api uses eg. 'rest_prepare_post' with 3 parameters.
      add_filter('rest_prepare_'.$tax_type_name,array($this,'filter_magic'),20,3);
    }

  }

  // Also enable filtering 'categories', 'comments', 'taxonomies', 'terms' and 'users'
  add_filter('rest_prepare_comment',array($this,'filter_magic'),20,3);
  add_filter('rest_prepare_taxonomy',array($this,'filter_magic'),20,3);
  add_filter('rest_prepare_term',array($this,'filter_magic'),20,3);
  add_filter('rest_prepare_category',array($this,'filter_magic'),20,3);
  add_filter('rest_prepare_user',array($this,'filter_magic'),20,3);
}


/**
 * This is where the magic happends.
 * @param WP_REST_Response   $response   The response object.
 * @param WP_Post            $post       Post object.
 * @param WP_REST_Request    $request    Request object.
 * @return object (Either the original or the object with the fields filtered)
 */
public function filter_magic( $response, $post, $request ){
  // Get the parameter from the WP_REST_Request
  // This supports headers, GET/POST variables.
  // and returns 'null' when not exists
  $fields = $request->get_param('fields');
  if($fields){

    // Create a new array
    $filtered_data = array();

    // The original data is in $response object in the property data
    $data = $response->data;

    // If _embed is included in the GET also fetch the _embedded values.
    if(isset( $_GET['_embed'] )){
      // Found in: https://core.trac.wordpress.org/browser/trunk/src/wp-includes/rest-api/endpoints/class-wp-rest-controller.php#L217
      $rest_server = rest_get_server();
      // Code from https://core.trac.wordpress.org/browser/trunk/src/wp-includes/rest-api/class-wp-rest-server.php#L382
      // $result = $this->response_to_data( $result, isset( $_GET['_embed'] ) );
      $data = $rest_server->response_to_data($response,true);
    } else {
      // The links should be included in the first place, so they can be filtered if needed.
      $data['_links'] = $response->get_links();
    }

    // Explode the $fields parameter to an array.
    $filters = explode(',',$fields);

    // If the filter is empty return the original.
    if(empty($filters) || count($filters) == 0)
      return $response;

    $singleFilters = array_filter($filters,array($this,'singleValueFilterArray'));

    // Foreach property inside the data, check if the key is in the filter.
    foreach ($data as $key => $value) {
      // If the key is in the $filters array, add it to the $filtered_data
      if (in_array($key, $singleFilters)) {
        $filtered_data[$key] = $value;
      }
    }

    $childFilters = array_filter($filters,array($this,'childValueFilterArray'));

    // This part should be made better!!
    foreach ($childFilters as $childFilter) {
      $val = $this->array_path_value($data,$childFilter);
      if($val != null){
        $this->set_array_path_value($filtered_data,$childFilter,$val);
      }
    }

  }

  // return the filtered_data if it is set and got fields.
  // return (isset($filtered_data) && count($filtered_data) > 0) ? rest_ensure_response($filtered_data) : $response;
  if (isset($filtered_data) && count($filtered_data) > 0) {
    //$filtered_data['_links'] = $response->get_links();
    $newResp = rest_ensure_response($filtered_data);
    return $newResp;
  }

  // return the response that we got in the first place.
  return $response;
}

// Function to filter the fields array
function singleValueFilterArray($var){
  return (strpos($var,'.') ===false);
}

// Function to filter the fields array
function childValueFilterArray($var){
  return (strpos($var,'.') !=false);
}

// found on http://codeaid.net/php/get-values-of-multi-dimensional-arrays-using-xpath-notation
function array_path_value(array $array, $path, $default = null)
{
    // specify the delimiter
    $delimiter = '.';

    // fail if the path is empty
    if (empty($path)) {
        throw new Exception('Path cannot be empty');
    }

    // remove all leading and trailing slashes
    $path = trim($path, $delimiter);

    // use current array as the initial value
    $value = $array;

    // extract parts of the path
    $parts = explode($delimiter, $path);

    // loop through each part and extract its value
    foreach ($parts as $part) {
        if (isset($value[$part])) {
            // replace current value with the child
            $value = $value[$part];
        } elseif('first' == $part && is_array($value)){
            $value = $value[0];
        } else {
            // key doesn't exist, fail
            return $default;
        }
    }

    return $value;
}

// Function found on http://codeaid.net/php/set-value-of-an-array-using-xpath-notation
function set_array_path_value(array &$array, $path, $value)
{
    // fail if the path is empty
    if (empty($path)) {
        throw new Exception('Path cannot be empty');
    }

    // fail if path is not a string
    if (!is_string($path)) {
        throw new Exception('Path must be a string');
    }

    // specify the delimiter
    $delimiter = '.';

    // remove all leading and trailing slashes
    $path = trim($path, $delimiter);

    // split the path in into separate parts
    $parts = explode($delimiter, $path);

    // initially point to the root of the array
    $pointer =& $array;

    // loop through each part and ensure that the cell is there
    foreach ($parts as $part) {
        // fail if the part is empty
        if (empty($part)) {
            throw new Exception('Invalid path specified: ' . $path);
        }

        // create the cell if it doesn't exist
        if (!isset($pointer[$part])) {
            $pointer[$part] = array();
        }

        // redirect the pointer to the new cell
        $pointer =& $pointer[$part];
    }

    // set value of the target cell
    $pointer = $value;
}
}
new REST_Api_Filter_Fields();
