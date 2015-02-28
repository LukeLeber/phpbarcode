<?php
    function render()
    {
        $text = htmlspecialchars($_GET["text"]);
        $encoding = $_GET["encoding"];
        $width = (int)$_GET["width"];
        $height = (int)$_GET["height"];

        $im = imagecreatetruecolor($width, $height);
        $text_color = imagecolorallocate($im, 233, 14, 91);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        $colors = array($white, $black);
        $x = 0;
        $i = 0;
        foreach(str_split($encoding) as $module)
        {
            imagefilledrectangle($im, $x, 0, $x + $module, $height, $colors[$i++ % 2]);
            $x += $module;
        }

        header('Content-Type: image/png');
        imagepng($im);
        imagedestroy($im);
    }
    render();