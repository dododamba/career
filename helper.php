<?php

use Carbon\Carbon;

if (!function_exists('humanDate')) {
    function humanDate($date)
    {
      return Carbon::createFromTimeStamp(strtotime($date))->diffForHumans();
    }
}

/********************************************************
 * 
 * 
 * 
 * 
 * Get IP Adress
 * version 0.0.1
 */








if(!function_exists('base64ToImage')){
    function base64ToImage($base64_string, $output_file) {
        $file = fopen($output_file, "wb");
    
        $data = explode(',', $base64_string);
    
        fwrite($file, base64_decode($data[1]));
        fclose($file);
    
        return $output_file;
    }
}





if (!function_exists('getIpAdress')) {
    function getIpAdress()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
        }
    }
}



if (!function_exists('str_randomize')) {
    function str_randomize($length)
    {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }

}





if (!function_exists('number_randomize')) {
    function number_randomize($length)
    {

        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}

