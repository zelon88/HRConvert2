<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/19/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats on
// / a server for users of any web browser without authentication.
// /
// / File Information ...
// / v4.3.0.
// / The ImageMagick decoder shared module, which is not a pipeline & converts nothing on its own.
// / It names the decoder ImageMagick reads an image with, which a command puts before the image's path, as DECODER:path.
// / The Image & OCR pipelines both hand ImageMagick a file a user gave, so neither of them owns it.
// / It is version pinned exactly as a pipeline is, by the table in Resources/Contract/app-engine-config.php.
// / It is loaded once per request, before the first pipeline that declared it.
// / It raises no error of its own. A pipeline whose file is refused raises its own, because it knows what it was converting.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this shared module. Read without executing this file, then matched
// / exactly against the pin in getAcceptedSharedModules().
$SharedModuleVersion = 'v4.3.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to name the decoder ImageMagick reads one image with, from v4.3.0.
// / Accepts the image's path & its extension, in that order.
// / Returns whether a decoder is named, which is FALSE only where the file is refused, & the decoder's name without its colon, in that order.
// / ImageMagick otherwise reads a file by what it holds, so PostScript named png reached Ghostscript & an SVG named png the SVG coder.
// / The policy each pipeline carries refuses those coders in its sandbox, & a host's own policy decides them outside one. A decoder named
// / on the command line is the only one ImageMagick uses, whatever either policy says.
// / The decoder is named by what the file holds, so a photograph named for the wrong format is still read, & by its extension where what
// / it holds says nothing, as a TGA's header does not. A camera raw file holds a TIFF's header, so its own decoder is named from its extension.
// / Nothing a file holds names the PDF decoder, because Ghostscript reads PostScript as readily as a PDF. A file named pdf is read as one
// / only where its first bytes are a PDF's header, as ImageMagick's own test is, & is refused otherwise, which is the one refusal this
// / function makes.
// / A file holding something else under an image's name is handed to that image's decoder, which fails to read it.
function imageMagickDecoder($imagePath, $imageExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DecoderIsNamed = FALSE;
  $ImageDecoder = '';
  $fileHead = $cleanExtension = $contentDecoder = $isoBrand = '';
  $rawDecoders = array('cr2' => 'CR2', 'nef' => 'NEF', 'arw' => 'ARW', 'dng' => 'DNG');
  $extensionDecoders = array('jpg' => 'JPEG', 'jpe' => 'JPEG', 'jpeg' => 'JPEG', 'tif' => 'TIFF', 'tiff' => 'TIFF');
  $cleanExtension = strtolower(trim((string)$imageExtension, '. '));
  // / Every header asked about is in a file's first bytes.
  if (is_file((string)$imagePath) && is_readable((string)$imagePath)) $fileHead = (string)@file_get_contents((string)$imagePath, FALSE, NULL, 0, 64);
  if (strncmp($fileHead, "\xFF\xD8\xFF", 3) === 0) $contentDecoder = 'JPEG';
  else if (strncmp($fileHead, "\x89PNG\r\n\x1A\n", 8) === 0) $contentDecoder = 'PNG';
  else if (strncmp($fileHead, 'GIF87a', 6) === 0 or strncmp($fileHead, 'GIF89a', 6) === 0) $contentDecoder = 'GIF';
  else if (strncmp($fileHead, 'RIFF', 4) === 0 && substr($fileHead, 8, 4) === 'WEBP') $contentDecoder = 'WEBP';
  else if (strncmp($fileHead, 'BM', 2) === 0) $contentDecoder = 'BMP';
  else if (strncmp($fileHead, "II*\x00", 4) === 0 or strncmp($fileHead, "MM\x00*", 4) === 0) $contentDecoder = (isset($rawDecoders[$cleanExtension]) ? $rawDecoders[$cleanExtension] : 'TIFF');
  else if (strncmp($fileHead, "\x00\x00\x01\x00", 4) === 0) $contentDecoder = 'ICO';
  else if (strncmp($fileHead, '8BPS', 4) === 0) $contentDecoder = 'PSD';
  else if (substr($fileHead, 4, 4) === 'ftyp') {
    $isoBrand = substr($fileHead, 8, 4);
    if (in_array($isoBrand, array('avif', 'avis'), TRUE)) $contentDecoder = 'AVIF';
    else if (in_array($isoBrand, array('heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'hevm', 'hevs', 'mif1', 'msf1'), TRUE)) $contentDecoder = ($cleanExtension === 'avif' ? 'AVIF' : 'HEIC'); }
  else if (strncmp($fileHead, "\xFF\x0A", 2) === 0 or strncmp($fileHead, "\x00\x00\x00\x0CJXL \r\n\x87\n", 12) === 0) $contentDecoder = 'JXL';
  else if (strncmp($fileHead, "\x00\x00\x00\x0CjP  \r\n\x87\n", 12) === 0) $contentDecoder = 'JP2';
  else if (strncmp($fileHead, "\xFF\x4F\xFF\x51", 4) === 0) $contentDecoder = 'J2K';
  else if (strncmp($fileHead, "\x76\x2F\x31\x01", 4) === 0) $contentDecoder = 'EXR';
  else if (strncmp($fileHead, '#?RADIANCE', 10) === 0 or strncmp($fileHead, '#?RGBE', 6) === 0) $contentDecoder = 'HDR';
  else if (strncmp($fileHead, 'SDPX', 4) === 0 or strncmp($fileHead, 'XPDS', 4) === 0) $contentDecoder = 'DPX';
  else if (strncmp($fileHead, "\x80\x2A\x5F\xD7", 4) === 0 or strncmp($fileHead, "\xD7\x5F\x2A\x80", 4) === 0) $contentDecoder = 'CIN';
  else if (strncmp($fileHead, 'DDS ', 4) === 0) $contentDecoder = 'DDS';
  else if (preg_match('/^P[1-7]\s/', $fileHead) === 1) $contentDecoder = 'PNM';
  else if (strncmp($fileHead, '/* XPM */', 9) === 0) $contentDecoder = 'XPM';
  else if (preg_match('/^#define\s/', $fileHead) === 1) $contentDecoder = 'XBM';
  // / A PCX header is a single byte & two small numbers, so it is the last thing a file is taken to be.
  else if (strlen($fileHead) > 2 && $fileHead[0] === "\x0A" && in_array(ord($fileHead[1]), array(0, 2, 3, 4, 5), TRUE) && ord($fileHead[2]) === 1) $contentDecoder = 'PCX';
  // / A file named pdf is read as a PDF only where it holds one. Anything that begins as a PDF does under another name is read by its own.
  if ($cleanExtension === 'pdf') $ImageDecoder = ($contentDecoder !== '' ? $contentDecoder : (strncmp($fileHead, '%PDF-', 5) === 0 ? 'PDF' : ''));
  else if ($contentDecoder !== '') $ImageDecoder = $contentDecoder;
  else if (isset($extensionDecoders[$cleanExtension])) $ImageDecoder = $extensionDecoders[$cleanExtension];
  else if (isset($rawDecoders[$cleanExtension])) $ImageDecoder = $rawDecoders[$cleanExtension];
  else if (preg_match('/^[a-z0-9]{1,10}$/', $cleanExtension) === 1) $ImageDecoder = strtoupper($cleanExtension);
  $DecoderIsNamed = ($ImageDecoder !== '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileHead, $cleanExtension, $contentDecoder, $isoBrand, $rawDecoders, $extensionDecoders, $imagePath, $imageExtension);
  return array($DecoderIsNamed, $ImageDecoder); }
// / -----------------------------------------------------------------------------------

?>
