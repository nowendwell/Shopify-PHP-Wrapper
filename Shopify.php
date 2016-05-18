<?php
/*------------------------------------------------------------------------------
** File:        Shopify.php
** Class:       Shopify
** Description: Shopify API Wrapper written in PHP to handle common  tasks
** Version:     0.0.1
** Updated:     17-May-2016
** Author:      Ben Miller
** Homepage:    https://github.com/nowendwell
**------------------------------------------------------------------------------
** Based on the project https://github.com/uniacid/PHP-Shopify-Plugin
**------------------------------------------------------------------------------ */

/*
 * Define API connection info
 */
define( 'KEY', '' ); // set API Key
define( 'PASSWORD', '' ); // set Password
define( 'SECRET', '' ); // set Shared Secret
define( 'BASE_URL', 'shopname.myshopify.com' ); // url of your store
define( 'DEBUG', false ); // set debug


class Shopify
{
    private $api_key               = KEY;
    private $password              = PASSWORD;
    private $secret                = SECRET;
    private $base_url              = BASE_URL;
    private $debug                 = DEBUG;
    private $method                = null;
    private $request_headers       = null;
    private $query                 = null;
    private $post_data             = array();
    private $api_url               = null;
    private $url                   = null;
    private $endpoint              = '';
    private $last_response_headers = null;
    private $request;
    private $result;


    public function __construct()
    {
        $this->api_url = 'https://'.$this->api_key.':'.$this->password.'@'.$this->base_url; //Set API Info
    }

    public function submit()
    {
        $this->result = new stdClass();
        $this->result->success = false;
        $this->result->status = 0; //0 failed 1 success
        $this->result->error = '';
        $this->result->data = null;

        $ch = curl_init(); //Initialize CURL
        if ( !empty($this->query) ) // set url
        {
            if ( is_array($this->query) )
            {
                $this->url = $this->api_url.'/'.$this->endpoint.'?'.http_build_query($this->query);
            } else {
                $this->url = $this->api_url.'/'.$this->endpoint.'?'.$this->query;
            }
        } else {
            $this->url = $this->api_url.'/'.$this->endpoint;
        }

        curl_setopt($ch, CURLOPT_URL, $this->url); //API post url
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);// allow redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //return var
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'shopify-php-api-client');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method); // set method

        // set post_data
        if ( $this->method != 'GET' && !empty($this->post_data) )
        {
            if( in_array($this->method, array('POST','PUT') ) )
            {
                $this->post_data = stripslashes(json_encode($this->post_data));
                $this->request_headers = array("Accept: application/json","Content-Type: application/json; charset=utf-8", 'Expect:');
            }

            if ( is_array($this->post_data) )
            {
                $this->post_data = http_build_query($this->post_data);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_data);
        }

        if ( !empty($this->request_headers) )
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->request_headers);
        }

        // submit request
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ( !empty($response) )
        {
            list($message_headers, $message_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
            $this->result->response_headers = $this->_parseHeaders($message_headers);
            $this->last_response_headers = $this->result->response_headers;

            if ( $this->last_response_headers['http_status_code'] >= '200' && $this->last_response_headers['http_status_code'] <= '206' )   // HTTP Success Code
            {
                $this->result->callLimit = $this->callLimit();
                $this->result->callsMade = $this->callsMade();
                $this->result->callsLeft = $this->callsLeft();
                $this->result->success = true;
                $this->result->status = $this->last_response_headers['http_status_message'];
                $this->result->data = json_decode($message_body, true);
            } else { // HTTP Failed
                $this->result->message = curl_error($ch);
                $this->result->error = curl_error($ch);
                $this->result->error_number = curl_errno($ch);
                $this->result->success = false;
                $this->result->status = $this->last_response_headers['http_status_message'];
                $this->result->error = json_decode($message_body, true);
            }
        }

        curl_close($ch); //Close CURL

        if($this->debug)
        {
            echo '<pre>';
            print_r($info);
            print_r($response);
            echo '</pre>';
        }

        return $this->result;
    }

    /*
     * Get list of all products
     * TODO: add filters
     */
    public function getProducts()
    {
        $this->endpoint = 'admin/products.json';
        $this->method = 'GET';

        return $this->submit();
    }

    /*
     * Get a count of all products of a given collection
     * TODO: add filters
     */
    public function getProductCount()
    {
        $this->endpoint = 'admin/products/count.json';
        $this->method = 'GET';

        return $this->submit();
    }

    /*
     * Get a single product
     * TODO: add filters
     */
    public function getProduct( $productID )
    {
        $this->endpoint = 'admin/products/'.$productID.'.json';
        $this->method = 'GET';

        return $this->submit();
    }

    /*
     * Returns All Customers can be used with filters var to define limits
     * TODO: add filters
     */
    public function getCustomers()
    {
        $this->endpoint = 'admin/customers.json';
        $this->method = 'GET';
        $this->query = '';

        return $this->submit();
    }

    /*
     * Find Customer by Shopify ID
     * @var $customerID (int) - Shopify Customer ID
     * TODO: add filters
     */
    public function getCustomer( $customerID )
    {
        $this->endpoint = 'admin/customers/'.$customerID.'.json';
        $this->method = 'GET';

        return $this->submit();
    }

    /*
     * Create New Shopify Customer
     * @var $customerData (array)
     */
    public function createCustomer( $customerData = null )
    {
        $this->endpoint = 'admin/customers.json';
        $this->method = 'POST';

        if ( !empty($customerData) )
        {
            $this->post_data = $customerData;
        }

        return $this->submit();
    }

    /*
     * Update Customer
     * @var $id (int)
     * @var $customerData (array)
     * TODO: add structure for customer data
     */
    public function updateCustomer($id,$customerData=null)
    {
        $this->endpoint = 'admin/customers/'.$id.'.json';
        $this->method = 'PUT';

        if ( !empty($customerData) )
        {
            $this->post_data = $customerData;
        }

        return $this->submit();
    }

    /*
     * Delete Customer
     * @var $id (int)
     */
    public function deleteCustomer($id)
    {
        $this->endpoint = 'admin/customers/'.$id.'.json';
        $this->method = 'DELETE';

        return $this->submit();
    }

    /*
     * Count Customers
     */
    public function countCustomers()
    {
        $this->endpoint = 'admin/customers/count.json';
        $this->method = 'GET';

        return $this->submit();
    }

    /*
     * Get Customer's Orders
     */
    public function getOrders($customerID)
    {
        $this->endpoint = 'admin/orders.json';
        $this->method = 'GET';
        $this->query = array("customer_id"=>$customerID);

        return $this->submit();
    }

    /*
     * Parse returned headers to be readable and usable
     * @var $message_headers (string)
     */
    private function _parseHeaders($message_headers)
    {
        $header_lines = preg_split("/\r\n|\n|\r/", $message_headers);
        $headers = array();
        list(, $headers['http_status_code'], $headers['http_status_message']) = explode(' ', trim( array_shift($header_lines) ), 3);
        foreach ($header_lines as $header_line)
        {
            list($name, $value) = explode(':', $header_line, 2);
            $name = strtolower($name);
            $headers[$name] = trim($value);
        }

        return $headers;
    }

    /*
     * Return number of API calls made to Shopify
     */
    public function callsMade()
    {
        return $this->shopApiCallLimitParam(0);
    }

    /*
     * Return max limit of API calls from Shopify
     */
    public function callLimit()
    {
        return $this->shopApiCallLimitParam(1);
    }

    /*
     * Return number of API calls remaining
     */
    public function callsLeft()
    {
        return $this->callLimit() - $this->callsMade();
    }

    /*
     * Return number of API calls made @private
     */
    private function shopApiCallLimitParam($index)
    {
        if ($this->last_response_headers == null)
            return 0;

        $params = explode('/', $this->last_response_headers['http_x_shopify_shop_api_call_limit']);
        return (int) $params[$index];
    }

}

?>
