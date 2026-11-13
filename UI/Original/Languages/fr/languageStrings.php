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
$LanguageVersion = 'v4.1.8';
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
$CoreError = 'ERREUR!!! HRConvert2-2, Ce fichier ne peut pas traiter votre demande ! Veuillez soumettre votre fichier à convertCore.php à la place!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Convertissez N\'Importe Quoi!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
$FCPlural1 = 's';
if (!is_numeric($FileCount)) $FileCount = 'un nombre inconnu de';
if ($FileCount == 1) $FCPlural1 = '';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Cliquez, Appuyez Ou Déposez Les Fichiers Ici Pour Les Télécharger.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Langue';
// / 'Color'
$GuiSelectorText2 = 'Couleur';
// / 'Interface'
$GuiSelectorText3 = 'Interface';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Afficher les options de langue, de couleur et d\'interface';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Convertisseur De Fichiers En Ligne, Rxtracteur, Compresseur';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' est basé sur l\'application Web open source <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> par <a href=\'https://github.com /zelon88\'>Zelon88</a> qui convertit les fichiers sans suivre les utilisateurs sur le net ni enfreindre votre propriété intellectuelle.';
// / 'More Info ...'
$Gui1Text3 = 'Plus D\'Informations ...';
// / 'Less Info ...'
$Gui1Text4 = 'Moins D\'Informations ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Toutes les données fournies par l\'utilisateur sont automatiquement effacées, vous n\'avez donc pas à vous soucier de perdre vos informations personnelles ou vos biens lors de l\'utilisation de nos services.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Actuellement '.$ApplicationName.' prend en charge '.$SupportedFormatCount.' différents formats de fichiers, y compris des documents, des feuilles de calcul, des images, des médias, des modèles 3D, des dessins CAO, des fichiers vectoriels, des archives, des images de disque, etc.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Afficher Les Formats Supportés ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Cacher Les Formats Supportés ...';
// / 'Supported Formats'
$Gui1Text9 = 'Formats Pris En Charge';
// / 'Audio Formats'
$Gui1Text10 = 'Formats De Audio';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Prend en charge un débit binaire spécifique.';
// / 'Video Formats'
$Gui1Text12 = 'Formats De Vidéo';
// / 'Stream Formats'
$Gui1Text13 = 'Formats De Flux';
// / 'Document Formats'
$Gui1Text14 = 'Formats De Documents';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Formats De Feuille De Calcul';
// / 'Presentation Formats'
$Gui1Text16 = 'Formats De Présentation';
// / 'Archive Formats'
$Gui1Text17 = 'Formats D\'Archives';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Peut convertir entre certains formats d\'archives et formats d\'image disque.';
// / 'Image Formats'
$Gui1Text19 = 'Formats D\'Images';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Peut convertir des images de documents en formats de document.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Prend en charge le redimensionnement et la rotation.';
// / '3D Model Formats'
$Gui1Text22 = 'Formats De Modèle 3D';
// / 'Drawing Formats'
$Gui1Text23 = 'Formats De Dessin';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Peut convertir des formats de dessin en formats d\'image.';
// / 'OCR Support'
$Gui1Text25 = 'Prise En Charge De La Reconnaissance Optique Des Caractères';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Les opérations de reconnaissance optique de caractères prennent en charge les formats d\'entrée suivants...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'OCR Les opérations de reconnaissance optique de caractères prennent en charge les formats de sortie suivants...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Sélectionnez les fichiers en cliquant, en appuyant ou en les déposant dans la zone ci-dessous.';
// / 'Continue ...'
$Gui1Text29 = 'Continuer ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Peut convertir les formats de flux en formats vidéo.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Formats De Sous-Titres';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Formats OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Convertit le code source OpenSCAD en formats de modèles 3D.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Les références de fichiers contenues dans les sources téléversées sont supprimées, sauf si le serveur autorise leur résolution.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Supprimer tous les fichiers envoyés et démarrer une nouvelle session ?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Recommencer';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Actualiser';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Options De Conversion De Fichiers';
// / 'Download'
$Gui2Text5 = 'Télécharger';
// / 'Share'
$Gui2Text6 = 'Partager';
// / 'Close Share Options'
$Gui2Text7 = 'Fermer Les Options De Partage';
// / 'Virus Scan'
$Gui2Text8 = 'Scan De Virus';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Fermer Les Options D’Analyse Antivirus';
// / 'Archive'
$Gui2Text10 = 'Archive';
// / 'Close Archive Options'
$Gui2Text11 = 'Fermer Les Options D’Archivage';
// / 'OCR'
$Gui2Text12 = 'Reconnaissance Optique De Caractères';
// / 'Close OCR Options'
$Gui2Text13 = 'Fermer Les Options De Reconnaissance Optique De Caractères';
// / 'Convert'
$Gui2Text14 = 'Convertir';
// / 'Close Convert Options'
$Gui2Text15 = 'Fermer Les Options De Conversion';
// / 'Archive This File'
$Gui2Text16 = 'Archiver Ce Fichier';
// / 'Specify Filename: '
$Gui2Text17 = 'Spécifiez Le Nom Du Fichier: ';
// / 'Format'
$Gui2Text18 = 'Format';
// / 'Share This File'
$Gui2Text23 = 'Partager De Fichier';
// / 'Link Status: '
$Gui2Text24 = 'Statut Du Lien: ';
// / 'Not Generated'
$Gui2Text25 = 'Non Généré';
// / 'Generated'
$Gui2Text26 = 'Généré';
// / 'Clipboard Status: '
$Gui2Text27 = 'État Du Presse-Papiers: ';
// / 'Copied'
$Gui2Text28 = 'Copié';
// / 'File Link: '
$Gui2Text29 = 'Lien De Fichier: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'Vous avez téléchargé '.$FileCount.' fichier valide'.$FCPlural1.' à '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Votre fichier'.$FCPlural2.' maintenant prêt à convertir en utilisant les options ci-dessous.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Générer Un Lien Et Copier Dans Le Presse-Papiers';
// / 'Generate Link'
$Gui2Text33 = 'Générer Un Lien';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Analyser Ce Fichier Pour Les Virus';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Analyser Le Fichier Avec ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Analyser Le Fichier Avec ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Numériser Un Fichier Avec ScanCore Et ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Effectuer Une Reconnaissance Optique De Caractères Sur Ce Fichier';
// / 'Method'
$Gui2Text39 = 'Méthode';
// / 'Simple'
$Gui2Text40 = 'Simple';
// / 'Advanced'
$Gui2Text41 = 'Avancée';
// / 'Convert This Archive'
$Gui2Text42 = 'Convertir Cette Archive';
// / 'Convert This Document'
$Gui2Text43 = 'Convertir Ce Document';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Convertir Cette Feuille De Calcul';
// / 'Convert This Audio'
$Gui2Text45 = 'Convertir Cet Audio';
// / 'Convert This Video'
$Gui2Text46 = 'Convertir Cette Vidéo';
// / 'Convert This Stream'
$Gui2Text47 = 'Convertir Ce Flux';
// / 'Convert This 3D Model'
$Gui2Text48 = 'Convertir Ce Modèle 3D';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Convertissez Ce Dessin Technique Ou Ce Fichier Vectoriel';
// / 'Convert This Image'
$Gui2Text50 = 'Convertir Cette Image';
// / 'Archive File'
$Gui2Text51 = 'Fichier D’Archive';
// / 'Convert Into Document'
$Gui2Text52 = 'Convertir En Document';
// / 'Archive Files'
$Gui2Text53 = 'Fichiers D’Archives';
// / 'Convert Document'
$Gui2Text54 = 'Convertir Le Document';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Convertir Une Feuille De Calcul';
// / 'Convert Presentation'
$Gui2Text56 = 'Convertir La Présentation';
// / 'Convert Audio'
$Gui2Text57 = 'Convertir L’Audio';
// / 'Convert Video'
$Gui2Text58 = 'Convertir La Vidéo';
// / 'Convert Stream'
$Gui2Text59 = 'Convertir Le Flux';
// / 'Convert Model'
$Gui2Text60 = 'Convertir Le Modèle';
// / 'Convert Drawing'
$Gui2Text61 = 'Convertir Le Dessin';
// / 'Convert Image'
$Gui2Text62 = 'Convertir L’Image';
// / 'Width & Height'
$Gui2Text64 = 'Largeur Et Hauteur: ';
// / 'Rotate: '
$Gui2Text65 = 'Tourner: ';
// / 'Bitrate: '
$Gui2Text66 = 'Débit: ';
// / 'Delete'
$Gui2Text67 = 'Supprimer';
// / 'Close Delete Options'
$Gui2Text68 = 'Fermer Les Options De Suppression';
// / 'Delete This File'
$Gui2Text69 = 'Supprimer Ce Fichier';
// / 'Confirm Delete'
$Gui2Text70 = 'Confirmation De La Suppression';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Impossible de convertir ce fichier ! Essayez de changer le nom.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Impossible d’effectuer une analyse antivirus sur ce fichier !';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Lien De Fichier Copié Dans Le Presse-Papier!';
// / 'Operation Failed!'
$Gui2Text74 = 'L’Opération A Échoué!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Convertir Ces Sous-Titres';
// / 'Convert Subtitles'
$Gui2Text76 = 'Convertir Les Sous-Titres';
// / 'Convert This Presentation'
$Gui2Text77 = 'Convertir Le Présentation';
// / 'Convert This XPS File'
$Gui2Text78 = 'Convertir Le XPS';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Générer Ce Modèle OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'Générer Le Modèle';
// / 'Convert This E-Book'
$Gui2Text81 = 'Convertir ce livre numérique';
// / 'Convert E-Book'
$Gui2Text82 = 'Convertir le livre numérique';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'URL source de la liste de lecture, requise seulement si elle utilise des chemins relatifs';
// / 'Download A File From A URL'
$Gui2Text84 = 'Télécharger un fichier depuis une URL';
// / 'Fetch'
$Gui2Text85 = 'Récupérer';
// / 'Identify'
$Gui2Text86 = 'Identifier';
// / 'Close Identify Options'
$Gui2Text87 = 'Fermer Les Options D’Identification';
// / 'Identify This File'
$Gui2Text88 = 'Identifier Ce Fichier';
// / 'Size: '
$Gui2Text89 = 'Taille: ';
// / 'Modified: '
$Gui2Text90 = 'Modifié: ';
// / 'Type: '
$Gui2Text91 = 'Type: ';
// / 'MIME Type: '
$Gui2Text92 = 'Type MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'Jeu De Caractères: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'L’Extension Correspond Au Contenu: ';
// / 'Details: '
$Gui2Text95 = 'Détails: ';
// / 'Notes: '
$Gui2Text96 = 'Remarques: ';
// / 'Save report as file'
$Gui2Text97 = 'Enregistrer le rapport dans un fichier';
// / 'Yes'
$Gui2Text100 = 'Oui';
// / 'No'
$Gui2Text101 = 'Non';
// / 'Unknown'
$Gui2Text102 = 'Inconnu';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Impossible d’identifier ce fichier !';
// / 'Sort By: '
$Gui2Text104 = 'Trier Par: ';
// / 'Name'
$Gui2Text105 = 'Nom';
// / 'Size'
$Gui2Text106 = 'Taille';
// / 'Date'
$Gui2Text107 = 'Date';
// / 'Type'
$Gui2Text108 = 'Type';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Sélectionner';
// / 'Select All'
$Gui2Text110 = 'Tout Sélectionner';
// / 'Select None'
$Gui2Text111 = 'Tout Désélectionner';
// / 'Select By Type'
$Gui2Text112 = 'Sélectionner Par Type';
// / 'Selected: '
$Gui2Text113 = 'Sélectionnés: ';
// / 'Documents'
$Gui2Text114 = 'Documents';
// / 'Spreadsheets'
$Gui2Text115 = 'Feuilles De Calcul';
// / 'Presentations'
$Gui2Text116 = 'Présentations';
// / 'Images'
$Gui2Text117 = 'Images';
// / 'Audio'
$Gui2Text118 = 'Audio';
// / 'Video'
$Gui2Text119 = 'Vidéo';
// / 'Archives'
$Gui2Text120 = 'Archives';
// / 'E-Books'
$Gui2Text121 = 'Livres Numériques';
// / '3D Models'
$Gui2Text122 = 'Modèles 3D';
// / 'Drawings'
$Gui2Text123 = 'Dessins';
// / 'Vector Images'
$Gui2Text124 = 'Images Vectorielles';
// / 'Subtitles'
$Gui2Text125 = 'Sous-Titres';
// / 'OpenSCAD Files'
$Gui2Text126 = 'Fichiers OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'Flux Vidéo';
// / 'Workflows'
$Gui2Text128 = 'Flux De Travail';
// / 'Selected Files'
$Gui2Text129 = 'Fichiers Sélectionnés';
// / 'Select files in the list below.'
$Gui2Text130 = 'Sélectionnez des fichiers dans la liste ci-dessous.';
// / 'Select By Name: '
$Gui2Text131 = 'Sélectionner Par Nom: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Utilisez * et ? comme caractères génériques, ou écrivez une expression régulière entre barres obliques.';
// / 'Workflow'
$Gui2Text133 = 'Flux De Travail';
// / 'Choose a workflow'
$Gui2Text134 = 'Choisissez un flux de travail';
// / 'Run'
$Gui2Text135 = 'Exécuter';
// / 'Stop'
$Gui2Text136 = 'Arrêter';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Fichiers sélectionnés utilisables: {fit} sur {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Étape {step} sur {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Fichier {done} sur {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'En attente du serveur.';
// / 'Starting.'
$Gui2Text141 = 'Démarrage.';
// / 'Finished.'
$Gui2Text142 = 'Terminé.';
// / 'Stopping.'
$Gui2Text143 = 'Arrêt en cours.';
// / 'Stopped.'
$Gui2Text144 = 'Arrêté.';
// / 'Files made: '
$Gui2Text145 = 'Fichiers créés: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Les flux de travail ne sont pas disponibles sur ce serveur.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'Aucun flux de travail n’est installé sur ce serveur.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Ce flux de travail n’est plus disponible.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Aucun des fichiers sélectionnés ne peut être utilisé par ce flux de travail.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Trop de fichiers sont sélectionnés pour un seul flux de travail.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'Le serveur est occupé. Réessayez dans quelques minutes.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'Un flux de travail est déjà en cours dans cette session.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'Le flux de travail n’a pas pu démarrer.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Une étape du flux de travail a échoué.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Un virus a été trouvé, le flux de travail s’est donc arrêté.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'Le flux de travail a manqué de temps.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'Le flux de travail a créé trop de fichiers.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'Le serveur était trop occupé pour terminer le flux de travail.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Une opération nécessaire à ce flux de travail n’est pas disponible.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'Le flux de travail s’est arrêté de manière inattendue.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Available Pipelines'
$Gui2Text161 = 'Pipelines Disponibles';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Sélectionnez un pipeline ci-dessus...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Sélectionner Un Pipeline...';
// / 'Select Files...'
$Gui2Text164 = 'Sélectionner Des Fichiers...';
// / 'Add a step'
$Gui2Text165 = 'Ajouter une étape';
// / 'Options'
$Gui2Text166 = 'Options';
// / 'Back'
$Gui2Text167 = 'Retour';
// / 'Take: '
$Gui2Text168 = 'Prendre: ';
// / 'the selected files'
$Gui2Text169 = 'les fichiers sélectionnés';
// / 'the files step {step} made'
$Gui2Text170 = 'les fichiers créés par l’étape {step}';
// / 'each file on its own'
$Gui2Text171 = 'chaque fichier séparément';
// / 'all files together'
$Gui2Text172 = 'tous les fichiers ensemble';
// / 'Only these kinds: '
$Gui2Text173 = 'Seulement ces types: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Seulement ces extensions: ';
// / 'Smallest size: '
$Gui2Text175 = 'Taille minimale: ';
// / 'Largest size: '
$Gui2Text176 = 'Taille maximale: ';
// / 'In the order they arrive'
$Gui2Text177 = 'Dans leur ordre d’arrivée';
// / 'all files'
$Gui2Text178 = 'tous les fichiers';
// / 'all {kind}'
$Gui2Text179 = 'tous les fichiers: {kind}';
// / 'Width: '
$Gui2Text180 = 'Largeur: ';
// / 'Height: '
$Gui2Text181 = 'Hauteur: ';
// / 'My workflow'
$Gui2Text182 = 'Mon flux de travail';
// / 'New'
$Gui2Text183 = 'Nouveau';
// / 'Import'
$Gui2Text184 = 'Importer';
// / 'Export'
$Gui2Text185 = 'Exporter';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Ce serveur ne peut pas exécuter ce flux de travail.';
// / 'The file could not be read.'
$Gui2Text187 = 'Le fichier n’a pas pu être lu.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'L’éditeur de flux de travail n’est pas disponible sur ce serveur.';
// / 'Status'
$Gui2Text189 = 'État';
// / 'A workflow is running.'
$Gui2Text190 = 'Un flux de travail est en cours.';
// / 'No workflow is running.'
$Gui2Text191 = 'Aucun flux de travail en cours.';
// / 'Extract'
$Gui2Text192 = 'Extraire';
// / 'Close Extract Options'
$Gui2Text193 = 'Fermer Les Options D’Extraction';
// / 'Extract This Archive'
$Gui2Text194 = 'Extraire Cette Archive';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = 'Chaque fichier qu’elle contient rejoint vos fichiers, sous son propre nom ou le prochain nom libre.';
// / 'Extract Files'
$Gui2Text196 = 'Extraire Les Fichiers';
// / 'to {format}'
$Gui2Text197 = 'en {format}';
// / 'This workflow deletes files. A deleted file cannot be brought back.'
$Gui2Text198 = 'Ce flux de travail supprime des fichiers. Un fichier supprimé ne peut pas être récupéré.';
// / 'Delete & Run'
$Gui2Text199 = 'Supprimer Et Exécuter';
// / 'Cancel'
$Gui2Text200 = 'Annuler';
// / 'Rename'
$Gui2Text201 = 'Renommer';
// / 'Close Rename Options'
$Gui2Text202 = 'Fermer Les Options De Renommage';
// / 'Rename This File'
$Gui2Text203 = 'Renommer Ce Fichier';
// / 'New Name'
$Gui2Text204 = 'Nouveau Nom';
// / 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.'
$Gui2Text205 = 'Utilisez {name} pour le nom qu\'il porte, {n} pour sa place parmi les fichiers sélectionnés et {date} pour la date du jour. Son extension est conservée.';
// / 'Strip Metadata'
$Gui2Text206 = 'Supprimer Les Métadonnées';
// / 'Close Strip Metadata Options'
$Gui2Text207 = 'Fermer Les Options Des Métadonnées';
// / 'Strip The Metadata From This File'
$Gui2Text208 = 'Supprimer Les Métadonnées De Ce Fichier';
// / 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.'
$Gui2Text209 = 'Une copie est créée à côté, sans ses auteurs, lieux, dates ni le logiciel qui l\'a créé. Ce qu\'il affiche reste tel quel.';
// / 'Merge PDFs'
$Gui2Text210 = 'Fusionner Les PDF';
// / 'Split PDF'
$Gui2Text211 = 'Scinder Le PDF';
// / 'Merge Or Split'
$Gui2Text212 = 'Fusionner Ou Scinder';
// / 'Close Merge & Split Options'
$Gui2Text213 = 'Fermer Les Options De Fusion Et De Scission';
// / 'Merge The Selected PDFs Into One'
$Gui2Text214 = 'Fusionner Les PDF Sélectionnés En Un Seul';
// / 'Their pages follow one another in the order the PDFs were selected.'
$Gui2Text215 = 'Leurs pages se suivent dans l\'ordre où les PDF ont été sélectionnés.';
// / 'Select two or more PDFs to merge them.'
$Gui2Text216 = 'Sélectionnez au moins deux PDF pour les fusionner.';
// / 'Make A PDF Of Each Page'
$Gui2Text217 = 'Créer Un PDF Pour Chaque Page';
// / 'Every selected file is deleted with it: {count} files.'
$Gui2Text218 = 'Chaque fichier sélectionné est supprimé avec lui : {count} fichiers.';
// / 'Every selected file is renamed with it: {count} files.'
$Gui2Text219 = 'Chaque fichier sélectionné est renommé avec lui : {count} fichiers.';
// / 'Library'
$Gui2Text220 = 'Bibliothèque';
// / 'from Selected Files'
$Gui2Text221 = 'depuis Fichiers Sélectionnés';
// / 'from Step {step}'
$Gui2Text222 = 'depuis l’étape {step}';
// / 'Preview'
$Gui2Text223 = 'Aperçu';
// / 'If the workflow ran now, each step would take:'
$Gui2Text224 = 'Si le flux de travail s’exécutait maintenant, chaque étape prendrait:';
// / 'Left out: '
$Gui2Text225 = 'Laissés de côté: ';
// / 'No files.'
$Gui2Text226 = 'Aucun fichier.';
// / 'every file in the list when it runs'
$Gui2Text227 = 'tous les fichiers de la liste au moment où l’étape s’exécute';
// / 'This workflow deletes these files. A deleted file cannot be brought back.'
$Gui2Text228 = 'Ce flux de travail supprime ces fichiers. Un fichier supprimé ne peut pas être récupéré.';
// / 'A step had errors, so the workflow stopped before the step that deletes files.'
$Gui2Text229 = 'Une étape a eu des erreurs, donc le flux de travail s’est arrêté avant l’étape qui supprime des fichiers.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Votre navigateur ne prend pas en charge la copie dans le presse-papiers.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Consultez nos <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Conditions de Service</a> et nos <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Politique de Confidentialité';
// / -----------------------------------------------------------------------------------