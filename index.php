<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Barcode Mania!  Quick Draw Challenge</title>
  <meta name="description" content="Barcode Mania!">
  <meta name="author" content="Luke A. Leber">
  <link rel="stylesheet" href="css/styles.css?v=1.0">
</head>
<body>
    
    <?php
        include_once('detail/code_128.php');
        $text = '4:23 AM Mission Accomplished';
        $test = new com\github\lukeleber\phpbarcode\detail\Code128();
    echo 'Plaintext: ' . $text . '<br><br><br>';
echo "Encoding: <img src=\"render.php?text=hello&encoding=". implode($test->encode('4:23 AM Mission Accomplished'), '') . "&height=40&width=400\">"
    ?>
</body>
</html>