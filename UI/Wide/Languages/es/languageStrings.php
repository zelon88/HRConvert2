<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 4/18/2023 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / FILE INFORMATION ...
// / v3.2.6.
// / This file contains language specific GUI related text for performing file conversions.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux (w/3rd Party audio license),
// / Apache 2.4, PHP 8+, LibreOffice, Unoconv, ClamAV, Tesseract, Rar, Unrar, Unzip,
// / 7zipper, FFMPEG, PDFTOTEXT, Dia, PopplerUtils, MeshLab, Mkisofs & ImageMagick.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$LanguageStringsLoaded = TRUE;
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'ERROR!!! HRConvert2-2, ¡Este archivo no puede procesar su solicitud! ¡Envíe su archivo a convertCore.php en su lugar!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = '¡Convierte Cualquier Cosa!'; 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'un número desconocido de';
$FCPlural1 = 's';
$FCPlural2 = 'n';
if ($FileCount == 1) { $FCPlural1 = ''; 
  $fcPlural2 = 'n'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Haga clic, toque o suelte los archivos aquí para cargarlos.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Convertidor de archivos en línea, Extractor, Compresor';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' se basa en la aplicación web de código abierto <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> de <a href=\'https://github.com/ zelon88\'>Zelon88</a> que convierte archivos sin rastrear a los usuarios a través de la red o infringir su propiedad intelectual.';
// / 'More Info ...'
$Gui1Text3 = 'Más Información...';
// / 'Less Info ...'
$Gui1Text4 = 'Menos Información...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Todos los datos proporcionados por el usuario se borran automáticamente, por lo que no debe preocuparse por perder su información personal o propiedad mientras utiliza nuestros servicios.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Actualmente'.$ApplicationName.' admite '.$SupportedFormatCount.' diferentes formatos de archivo, incluidos documentos, hojas de cálculo, imágenes, medios, modelos 3D, dibujos CAD, archivos vectoriales, archivos, imágenes de disco y más.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Ver formatos admitidos...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Ocultar formatos admitidos...';
// / 'Supported Formats'
$Gui1Text9 = 'Formatos Soportados';
// / 'Audio Formats'
$Gui1Text10 = 'Formatos De Audio';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Admite tasa de bits específica.';
// / 'Video Formats'
$Gui1Text12 = 'Formatos De Video';
// / 'Stream Formats'
$Gui1Text13 = 'Formatos De Transmisión';
// / 'Document Formats'
$Gui1Text14 = 'Formatos De Documento';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Formatos De Hoja De Cálculo';
// / 'Presentation Formats'
$Gui1Text16 = 'Formatos De Presentación';
// / 'Archive Formats'
$Gui1Text17 = 'Formatos De Archivo';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Puede convertir entre formatos de archivo seleccionados y formatos de imagen de disco.';
// / 'Image Formats'
$Gui1Text19 = 'Formatos De Imagen';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Puede convertir imágenes de documentos a formatos de documentos.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Soporta cambio de tamaño y rotación.';
// / '3D Model Formats'
$Gui1Text22 = 'Formatos De Modelos 3D';
// / 'Drawing Formats'
$Gui1Text23 = 'Formatos De Dibujo';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Puede convertir formatos de dibujo a formatos de imagen.';
// / 'OCR Support'
$Gui1Text25 = 'Soporte OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Las operaciones de OCR admiten los siguientes formatos de entrada...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Las operaciones de OCR admiten los siguientes formatos de salida...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Seleccione archivos haciendo clic, tocándolos o soltándolos en el cuadro de abajo.';
// / 'Continue ...'
$Gui1Text29 = 'Continuar...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Puede convertir formatos de transmisión a formatos de video.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Formatos de Subtítulos';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Opciones De Conversión De Aarchivos';
// / 'Bulk File Options'
$Gui2Text2 = 'Opciones De Archivo Masivo';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Analizar Todos Los Archivos En Busca De Virus';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Comprimir Y Descargar Todos Los Archivos';
// / 'Download'
$Gui2Text5 = 'Descargar';
// / 'Share'
$Gui2Text6 = 'Compartir';
// / 'Close Share Options'
$Gui2Text7 = 'Cerrar Opciones Para Compartir';
// / 'Virus Scan'
$Gui2Text8 = 'Análisis De Virus';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Cerrar Opciones De Análisis De Virus';
// / 'Archive'
$Gui2Text10 = 'Archivo';
// / 'Close Archive Options'
$Gui2Text11 = 'Cerrar Opciones De Archivo';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Cerrar Opciones De OCR';
// / 'Convert'
$Gui2Text14 = 'Convertir';
// / 'Close Convert Options'
$Gui2Text15 = 'Cerrar Opciones De Conversión';
// / 'Archive This File'
$Gui2Text16 = 'Archivar Este Archivo';
// / 'Specify Filename: '
$Gui2Text17 = 'Especificar Nombre De Archivo: ';
// / 'Format'
$Gui2Text18 = 'Formato';
// / 'Compress & Download'
$Gui2Text19 = 'Comprimir Y Descargar';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Escanear Con ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Escanear Con ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Escanear Todo';
// / 'Share This File'
$Gui2Text23 = 'Compartir Este Archivo';
// / 'Link Status: '
$Gui2Text24 = 'Estado Del Enlace: ';
// / 'Not Generated'
$Gui2Text25 = 'No Generado';
// / 'Generated'
$Gui2Text26 = 'Generado';
// / 'Clipboard Status: '
$Gui2Text27 = 'Estado del Portapapeles: ';
// / 'Copied'
$Gui2Text28 = 'Copiado';
// / 'File Link: '
$Gui2Text29 = 'Enlace De Archivo: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'Has subido '.$FileCount.' archivo válido'.$FCPlural1.' a '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Su archivo'.$FCPlural2.' ahora está listo para convertir usando las opciones a continuación.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Generar Enlace Y Copiar Al Portapapeles';
// / 'Generate Link'
$Gui2Text33 = 'Generar Enlace';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Escanear Este Archivo En Busca De Virus';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Escanear Archivo Con ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Escanear Archivo Con ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Escanear Archivo Con ScanCore Y ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Realizar Reconocimiento Óptico De Caracteres En Este Archivo';
// / 'Method'
$Gui2Text39 = 'Método';
// / 'Simple'
$Gui2Text40 = 'Simple';
// / 'Advanced'
$Gui2Text41 = 'Avanzado';
// / 'Convert This Archive'
$Gui2Text42 = 'Convertir Este Archivo';
// / 'Convert This Document'
$Gui2Text43 = 'Convertir Este Documento';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Convertir esta hoja de cálculo';
// / 'Convertir This Audio'
$Gui2Text45 = 'Convertir Este Audio';
// / 'Convertir This Video'
$Gui2Text46 = 'Convertir Este Video';
// / 'Convert This Stream'
$Gui2Text47 = 'Convertir Este Flujo';
// / Convert This 3D Model'
$Gui2Text48 = 'Convertir Este Modelo 3D';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Convertir Este Dibujo Técnico O Archivo Vectorial';
// / 'Convert This Image'
$Gui2Text50 = 'Convertir Esta Imagen';
// / 'Archive File'
$Gui2Text51 = 'Archivo De Archivo';
// / 'Convert Into Document'
$Gui2Text52 = 'Convertir En Documento';
// / 'Archive Files'
$Gui2Text53 = 'Archivar Archivos';
// / 'Convert Document'
$Gui2Text54 = 'Convertir Documento';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Convertir Hoja De Cálculo';
// / 'Convert Presentation'
$Gui2Text56 = 'Convertir Presentación';
// / 'Convert Audio'
$Gui2Text57 = 'Convertir Audio';
// / 'Convert Video'
$Gui2Text58 = 'Convertir Vídeo';
// / 'Convert Stream'
$Gui2Text59 = 'Convertir Transmisión';
// / 'Convert Model'
$Gui2Text60 = 'Convertir Modelo';
// / 'Convert Drawing'
$Gui2Text61 = 'Convertir Dibujo';
// / 'Convert Image'
$Gui2Text62 = 'Convertir Imagen';
// / 'Width & Height'
$Gui2Text64 = 'Ancho y Alto: ';
// / 'Rotate: '
$Gui2Text65 = 'Rotar: ';
// / 'Bitrate: '
$Gui2Text66 = 'Tasa De Bits: ';
// / 'Delete'
$Gui2Text67 = 'Borrar';
// / 'Close Delete Options'
$Gui2Text68 = 'Cerrar Opciones De Eliminación';
// / 'Delete This File'
$Gui2Text69 = 'Eliminar Este Archivo';
// / 'Confirmar eliminación'
$Gui2Text70 = 'Confirmar eliminación';
// / '¡No se puede convertir este archivo! Prueba a cambiar el nombre.
$Gui2Text71 = '¡No se puede convertir este archivo! Intenta cambiar el nombre.';
// / '¡No se puede realizar un análisis de virus en este archivo!'
$Gui2Text72 = '¡No se puede realizar un análisis de virus en este archivo!';
// / '¡Enlace de archivo copiado al portapapeles!'
$Gui2Text73 = '¡Enlace de archivo copiado al portapapeles!';
// / '¡Operación fallida!'
$Gui2Text74 = '¡Operación fallida!';
// / Convertir estos subtítulos'
$Gui2Text75 = 'Convertir estos subtítulos';
// / Convertir subtítulos'
$Gui2Text76 = 'Convertir subtítulos';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Consulte nuestras <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Condiciones de Servicio</a> y <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Política de Privacidad';
// / -----------------------------------------------------------------------------------