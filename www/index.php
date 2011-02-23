<!DOCTYPE html>
<?php
    $file = 'extracts' . DIRECTORY_SEPARATOR . 'url.txt';
    $url = null;
    if( file_exists( $file ) ) {
        $handle = fopen($file, 'r');
        $url = fgets($handle);
        fclose($handle);
    }
?>
<html>
<head>
    <title>Image Merger</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="./css/styles.css" />
</head>

<body>

<div id="panel">
    <div id="config-panel">
        <form method="post" action="<?php echo ! is_null($url) ? 'stop' : 'start'; ?>">
            <fieldset>
                <label for="url">Url: </label>
                <input id="url" name="url" type="text" <?php echo ! is_null($url) ? 'disabled="disabled"' : null; ?> value="<?php echo $url; ?>" />
                <input id="submit" type="submit" value="<?php echo ! is_null($url) ? 'stop' : 'start'; ?>" />
            </fieldset>
        </form>
    </div>
    <div id="about-panel" style="display:none;">
    </div>
    <ul id="navigation">
        <li><a href="about-panel">About</a></li>
        <li><a href="config-panel">Config</a></li>
    </ul>
</div>

<img id="image-panel" src="./img/image.jpg" alt="" />
<div id="image-container"></div>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
<script type="text/javascript" src="./js/pixastic.custom.js"></script>
<script type="text/javascript" src="./js/script.js"></script>

</body>

</html> 
