<?php

    function measure($encoding)
    {
        $x = 0;
        foreach(str_split($encoding) as $module)
        {
            $x += $module;
        }
        return $x;
    }

    function findStartCenterJustify($width, $encoding, $scale)
    {
        $x = 0;
        foreach(str_split($encoding) as $module)
        {
            $x += $module * $scale;
        }
        return ($width - $x) / 2;
    }

    function error($what)
    {
        die($what);
    }

    function render()
    {
        /// (required) the encoding pattern to render
        if(!isset($_GET['encoding']))
        {
            error('missing encoding');
            return;
        }
        $encoding = $_GET['encoding'];

        foreach(str_split($encoding) as $module)
        {
            if(!is_numeric($module))
            {
                error('bad input');
            }
        }
        
        /// cache the unscaled length of the encoding
        $base_length = measure($encoding);

        /// (optional) width for the barcode - either provided or 'auto' by default
        $width = isset($_GET['width']) && strcmp($_GET['width'], 'auto') ? 
                                                        $_GET['width'] : $base_length;
        
        /// (optional) height for the barcode - either provided or 'auto' by default
        $height = isset($_GET['height']) && strcmp($_GET['height'], 'auto') ? 
                                                        $_GET['height'] : (int)($base_length * 0.15);
         
        /// (optional) background for the barcode - either provided or white by default
        $background_color = isset($_GET['background_color']) ? (int)$_GET['background_color'] : 0xFFFFFF;        
        
        /// (optional) bar color for the barcode - either provided or black by default
        $bar_color = isset($_GET['bar_color']) ? (int)$_GET['bar_color'] : 0x000000;
        
        /// quick validation - we want to display an error image, not just randomly puke
        if(!is_numeric($width) || !is_numeric($height) || !is_numeric($background_color) || !is_numeric($bar_color))
        {
            error('bad input');
            return;
        }
        
        /// everything's cool, let's draw a barcode optimized for reducing bandwidth
        /// So turn off alpha blending and turn on interleave mode
        /// todo: figure out how to crank up the LZMA compression level.
        $im = imagecreatetruecolor($width, $height);
        imagealphablending($im, 0);
        imagesavealpha($im, 0);
        imageinterlace($im, 1);
        
        /// recycle old variable
        $bar_color = imagecolorallocate($im, ($bar_color >> 16) & 0xFF, 
                                             ($bar_color >> 8) & 0xFF, 
                                              $bar_color & 0xFF);

        /// by default, the GD API starts off with a black background...
        /// so we can eliminate a "heavy" call to flood the background if the user wants a black background
        /// I can't think of a practical purpose for doing so other than "art", but meh...whatever.
        if($background_color != 0x000000)
        {
            $background_color = imagecolorallocate($im, ($background_color >> 16) & 0xFF, 
                                                        ($background_color >> 8) & 0xFF, 
                                                         $background_color & 0xFF);
            imagefilledrectangle($im, 0, 0, $width, $height, $background_color);
        }
        
        /// todo: feature -- allow for left / center / right justification?
        $scale = (int)($width / $base_length);
        $x = findStartCenterJustify($width, $encoding, $scale);
        $i = 0;
        
        /// treat each subsequent module as the opposite -- eg 3 2 1 means 3 bars, 2 spaces, 1 bar
        foreach(str_split($encoding) as $module)
        {
            $bar_width = $module * $scale;
            if(($i++ % 2) != 0)
            {
                imagefilledrectangle($im, $x, 1, $x + $bar_width - 1, $height, $bar_color);
            }            
            $x += $bar_width;
        }

        /// draw the image
        header('Content-Type: image/png');        
        imagepng($im);
        
        /// clean up our native memory...
        if(!is_numeric($background_color))
        {
            imagecolordeallocate($im, $background_color);
        }
        imagecolordeallocate($im, $bar_color);
        imagedestroy($im);
    }

    render();
