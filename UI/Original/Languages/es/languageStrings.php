<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/17/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.8.3.
// / This file contains language specific GUI related text for performing file conversions.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / The authoritative list of dependencies is Resources/Contract/depends.php & nothing else.
// / Run  php convertCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$LanguageStringsLoaded = TRUE;
// / The version of this language pack for compatibility checking.
// / Compatibility check takes place in convertCore.php, buildGui() function.
$LanguageVersion = 'v4.1.7';
$LanguageVersion = ltrim($LanguageVersion, 'vV');
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Which end of a dropdown the arrow sits at, as a percentage across the control.
// / This pack knows its own reading direction & is therefore the right place to decide.
// / A reader finishes at the left of a right to left line, so the arrow belongs there.
// / An arrow pinned to the right edge sits at the START of the box in this language &
// / reads as a stray icon rather than as the thing that opens the list.
$SelectArrowSide = ($GUIAlignment === 'right') ? '4%' : '96%';
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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Idioma';
// / 'Color'
$GuiSelectorText2 = 'Color';
// / 'Interface'
$GuiSelectorText3 = 'Interfaz';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Mostrar opciones de idioma, color e interfaz';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
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
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Formatos OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Convierte el código fuente de OpenSCAD en formatos de modelos 3D.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Las referencias a archivos dentro de las fuentes subidas se eliminan, salvo que el servidor permita resolverlas.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = '¿Eliminar todos los archivos subidos e iniciar una nueva sesión?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Empezar de Nuevo';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Actualizar';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Opciones De Conversión De Aarchivos';
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
// / 'Convert This 3D Model'
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
// / 'Confirm Delete'
$Gui2Text70 = 'Confirmar eliminación';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = '¡No se puede convertir este archivo! Intenta cambiar el nombre.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = '¡No se puede realizar un análisis de virus en este archivo!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = '¡Enlace de archivo copiado al portapapeles!';
// / 'Operation Failed!'
$Gui2Text74 = '¡Operación Fallida!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Convertir Estos Subtítulos';
// / 'Convert Subtitles'
$Gui2Text76 = 'Convertir Subtítulos';
// / 'Convert This Presentation'
$Gui2Text77 = 'Convertir Esta Presentación';
// / 'Convert This XPS File'
$Gui2Text78 = 'Convertir XPS';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Generar Este Modelo De OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'Generar Modelo';
// / 'Convert This E-Book'
$Gui2Text81 = 'Convertir este libro electrónico';
// / 'Convert E-Book'
$Gui2Text82 = 'Convertir libro electrónico';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'URL de origen de la lista, solo necesaria si la lista usa rutas relativas';
// / 'Download A File From A URL'
$Gui2Text84 = 'Descargar un archivo desde una URL';
// / 'Fetch'
$Gui2Text85 = 'Obtener';
// / 'Identify'
$Gui2Text86 = 'Identificar';
// / 'Close Identify Options'
$Gui2Text87 = 'Cerrar Opciones De Identificación';
// / 'Identify This File'
$Gui2Text88 = 'Identificar Este Archivo';
// / 'Size: '
$Gui2Text89 = 'Tamaño: ';
// / 'Modified: '
$Gui2Text90 = 'Modificado: ';
// / 'Type: '
$Gui2Text91 = 'Tipo: ';
// / 'MIME Type: '
$Gui2Text92 = 'Tipo MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'Juego De Caracteres: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'La Extensión Coincide Con El Contenido: ';
// / 'Details: '
$Gui2Text95 = 'Detalles: ';
// / 'Notes: '
$Gui2Text96 = 'Notas: ';
// / 'Save report as file'
$Gui2Text97 = 'Guardar el informe como archivo';
// / 'Yes'
$Gui2Text100 = 'Sí';
// / 'No'
$Gui2Text101 = 'No';
// / 'Unknown'
$Gui2Text102 = 'Desconocido';
// / 'Cannot identify this file!'
$Gui2Text103 = '¡No se puede identificar este archivo!';
// / 'Sort By: '
$Gui2Text104 = 'Ordenar Por: ';
// / 'Name'
$Gui2Text105 = 'Nombre';
// / 'Size'
$Gui2Text106 = 'Tamaño';
// / 'Date'
$Gui2Text107 = 'Fecha';
// / 'Type'
$Gui2Text108 = 'Tipo';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Seleccionar';
// / 'Select All'
$Gui2Text110 = 'Seleccionar Todo';
// / 'Select None'
$Gui2Text111 = 'Deseleccionar Todo';
// / 'Select By Type'
$Gui2Text112 = 'Seleccionar Por Tipo';
// / 'Selected: '
$Gui2Text113 = 'Seleccionados: ';
// / 'Documents'
$Gui2Text114 = 'Documentos';
// / 'Spreadsheets'
$Gui2Text115 = 'Hojas De Cálculo';
// / 'Presentations'
$Gui2Text116 = 'Presentaciones';
// / 'Images'
$Gui2Text117 = 'Imágenes';
// / 'Audio'
$Gui2Text118 = 'Audio';
// / 'Video'
$Gui2Text119 = 'Vídeo';
// / 'Archives'
$Gui2Text120 = 'Archivos Comprimidos';
// / 'E-Books'
$Gui2Text121 = 'Libros Electrónicos';
// / '3D Models'
$Gui2Text122 = 'Modelos 3D';
// / 'Drawings'
$Gui2Text123 = 'Dibujos';
// / 'Vector Images'
$Gui2Text124 = 'Imágenes Vectoriales';
// / 'Subtitles'
$Gui2Text125 = 'Subtítulos';
// / 'OpenSCAD Files'
$Gui2Text126 = 'Archivos OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'Transmisiones';
// / 'Workflows'
$Gui2Text128 = 'Flujos De Trabajo';
// / 'Files'
$Gui2Text129 = 'Archivos';
// / 'Select files in the list below.'
$Gui2Text130 = 'Seleccione archivos en la lista de abajo.';
// / 'Select By Name: '
$Gui2Text131 = 'Seleccionar Por Nombre: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Use * y ? como comodines, o escriba una expresión regular entre barras.';
// / 'Workflow'
$Gui2Text133 = 'Flujo De Trabajo';
// / 'Choose a workflow'
$Gui2Text134 = 'Elija un flujo de trabajo';
// / 'Run'
$Gui2Text135 = 'Ejecutar';
// / 'Stop'
$Gui2Text136 = 'Detener';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Archivos seleccionados que se pueden usar: {fit} de {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Paso {step} de {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Archivo {done} de {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Esperando al servidor.';
// / 'Starting.'
$Gui2Text141 = 'Iniciando.';
// / 'Finished.'
$Gui2Text142 = 'Terminado.';
// / 'Stopping.'
$Gui2Text143 = 'Deteniendo.';
// / 'Stopped.'
$Gui2Text144 = 'Detenido.';
// / 'Files made: '
$Gui2Text145 = 'Archivos creados: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Los flujos de trabajo no están disponibles en este servidor.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'No hay flujos de trabajo instalados en este servidor.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Ese flujo de trabajo ya no está disponible.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Este flujo de trabajo no puede usar ninguno de los archivos seleccionados.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Hay demasiados archivos seleccionados para un flujo de trabajo.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'El servidor está ocupado. Inténtelo de nuevo en unos minutos.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'Ya hay un flujo de trabajo en ejecución en esta sesión.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'No se pudo iniciar el flujo de trabajo.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Falló un paso del flujo de trabajo.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Se encontró un virus, así que el flujo de trabajo se detuvo.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'El flujo de trabajo se quedó sin tiempo.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'El flujo de trabajo creó demasiados archivos.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'El servidor estaba demasiado ocupado para terminar el flujo de trabajo.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Una operación que necesita este flujo de trabajo no está disponible.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'El flujo de trabajo se detuvo inesperadamente.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'Canalizaciones';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Seleccione una canalización arriba...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Seleccionar Canalización...';
// / 'Select Files...'
$Gui2Text164 = 'Seleccionar Archivos...';
// / 'Add a step'
$Gui2Text165 = 'Añadir un paso';
// / 'Options'
$Gui2Text166 = 'Opciones';
// / 'Back'
$Gui2Text167 = 'Atrás';
// / 'Take: '
$Gui2Text168 = 'Tomar: ';
// / 'the ticked files'
$Gui2Text169 = 'los archivos marcados';
// / 'the files step {step} made'
$Gui2Text170 = 'los archivos que creó el paso {step}';
// / 'each file on its own'
$Gui2Text171 = 'cada archivo por separado';
// / 'all files together'
$Gui2Text172 = 'todos los archivos juntos';
// / 'Only these kinds: '
$Gui2Text173 = 'Solo estos tipos: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Solo estas extensiones: ';
// / 'Smallest size: '
$Gui2Text175 = 'Tamaño mínimo: ';
// / 'Largest size: '
$Gui2Text176 = 'Tamaño máximo: ';
// / 'In the order they arrive'
$Gui2Text177 = 'En el orden en que llegan';
// / 'all files'
$Gui2Text178 = 'todos los archivos';
// / 'all {kind}'
$Gui2Text179 = 'todos los archivos: {kind}';
// / 'Width: '
$Gui2Text180 = 'Ancho: ';
// / 'Height: '
$Gui2Text181 = 'Alto: ';
// / 'My workflow'
$Gui2Text182 = 'Mi flujo de trabajo';
// / 'New'
$Gui2Text183 = 'Nuevo';
// / 'Import'
$Gui2Text184 = 'Importar';
// / 'Export'
$Gui2Text185 = 'Exportar';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Este servidor no puede ejecutar este flujo de trabajo.';
// / 'The file could not be read.'
$Gui2Text187 = 'No se pudo leer el archivo.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'El editor de flujos de trabajo no está disponible en este servidor.';
// / 'Status'
$Gui2Text189 = 'Estado';
// / 'A workflow is running.'
$Gui2Text190 = 'Hay un flujo de trabajo en ejecución.';
// / 'No workflow is running.'
$Gui2Text191 = 'No hay ningún flujo de trabajo en ejecución.';
// / 'Extract'
$Gui2Text192 = 'Extraer';
// / 'Close Extract Options'
$Gui2Text193 = 'Cerrar Opciones De Extracción';
// / 'Extract This Archive'
$Gui2Text194 = 'Extraer Este Archivo Comprimido';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = 'Cada archivo que contiene se añade a tus archivos, con su propio nombre o con el siguiente nombre libre.';
// / 'Extract Files'
$Gui2Text196 = 'Extraer Archivos';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Su navegador no admite la copia al portapapeles.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Consulte nuestras <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Condiciones de Servicio</a> y <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Política de Privacidad';
// / -----------------------------------------------------------------------------------