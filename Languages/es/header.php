<?php if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = '¡Convierte Cualquier Cosa!!'; 
if (!isset($Font)) $Font = 'Arial'; ?>
<html>
  <head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="Resources/favicon.ico"/>
    <link rel="stylesheet" href="Resources/dropzone.css"/>
    <link rel="stylesheet" href="Resources/HRConvert2.css"/>
    <script type="text/javascript" src="Resources/HRC2-Functions.js"></script>
    <script type="text/javascript">var dropzoneText = 'Haga clic, toque o suelte archivos aquí para cargar.';</script>
    <script type="text/javascript" src="Resources/dropzone.js"></script>
    <style>
      body {
        font-family: <?php echo $Font; ?>; }
        <?php if (isset($ButtonCode)) echo $ButtonCode; ?>
    </style>
    <title><?php echo $ApplicationName; ?> - <?php echo $ApplicationTitle; ?></title>
  </head>
  