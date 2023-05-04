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
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
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
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Options De Conversion De Fichiers';
// / 'Bulk File Options'
$Gui2Text2 = 'Options De Fichiers En Masse';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Analyser Tous Les Fichiers Pour Les Virus';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Compresser Et Télécharger Tous Les Fichiers';
// / 'Download'
$Gui2Text5 = 'Télécharger';
// / 'Share'
$Gui2Text6 = 'Partager';
// / 'Close Share Options'
$Gui2Text7 = 'Fermer Les Options De Partage';
// / 'Virus Scan'
$Gui2Text8 = 'Scan De Virus';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Fermer Les Options D\'Analyse Antivirus';
// / 'Archive'
$Gui2Text10 = 'Archive';
// / 'Close Archive Options'
$Gui2Text11 = 'Fermer Les Options D\'Archivage';
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
// / 'Compress & Download'
$Gui2Text19 = 'Compresser Et Télécharger';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Numériser Avec ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Numériser Avec ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Numériser Tout';
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
$Gui2Text33 = 'Generate Link';
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
// / Convert This 3D Model'
$Gui2Text48 = 'Convertir Ce Modèle 3D';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Convertissez Ce Dessin Technique Ou Ce Fichier Vectoriel';
// / 'Convert This Image'
$Gui2Text50 = 'Convertir Cette Image';
// / 'Archive File'
$Gui2Text51 = 'Fichier D\'Archive';
// / 'Convert Into Document'
$Gui2Text52 = 'Convertir En Document';
// / 'Archive Files'
$Gui2Text53 = 'Fichiers D\'Archives';
// / 'Convert Document'
$Gui2Text54 = 'Convertir Le Document';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Convertir Une Feuille De Calcul';
// / 'Convert Presentation'
$Gui2Text56 = 'Convertir La Présentation';
// / 'Convert Audio'
$Gui2Text57 = 'Convertir L\'Audio';
// / 'Convert Video'
$Gui2Text58 = 'Convertir La Vidéo';
// / 'Convert Stream'
$Gui2Text59 = 'Convertir Le Flux';
// / 'Convert Model'
$Gui2Text60 = 'Convertir Le Modèle';
// / 'Convert Drawing'
$Gui2Text61 = 'Convertir Le Dessin';
// / 'Convert Image'
$Gui2Text62 = 'Convertir L\'Image';
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
$Gui2Text72 = 'Impossible d\'effectuer une analyse antivirus sur ce fichier !';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Lien De Fichier Copié Dans Le Presse-Papier!';
// / 'Operation Failed!'
$Gui2Text74 = 'L\'Opération A Échoué!';
// / Convert These Subtitles'
$Gui2Text75 = 'Convertir Ces Sous-Titres';
// / Convert Subtitles'
$Gui2Text76 = 'Convertir Les Sous-Titres';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Consultez nos <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Conditions de Service</a> et nos <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Politique de Confidentialité';
// / -----------------------------------------------------------------------------------