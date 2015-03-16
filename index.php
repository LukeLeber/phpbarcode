<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Barcode Mania!  Quick Draw Challenge</title>
  <meta name="description" content="Barcode Mania!">
  <meta name="author" content="Luke A. Leber">
</head>
<body>
    
<?php
    include_once('detail/code_128.php');
    $text = 'phpbarcode';
    $test = new com\github\lukeleber\phpbarcode\detail\Code128();
    $encoding = $test->encode($text);
    echo "<center>" . $text . '<br/>';
    echo "<img src=\"render.php?text=". $text . 
                                        "&encoding=". implode($encoding, '') . 
                                        "&width=auto&height=auto
                                        \"></center>";
?>
</body>
</html>
