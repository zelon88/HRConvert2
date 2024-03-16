<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
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
$CoreError = 'ERROR!!! HRConvert2-2, Este arquivo não pode processar sua solicitação! Por favor, envie seu arquivo para convertCore.php!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Converta qualquer coisa!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'um número desconhecido de';
if ($FileCount === 0) $FCPlural1 = 'Você carregou 0 arquivos válidos para o '.$ApplicationName.'.';
if ($FileCount === 1) $FCPlural1 = 'Você carregou 1 arquivo válido para '.$ApplicationName.'.'; 
if ($FileCount === 2) $FCPlural1 = 'Você carregou 2 arquivos válidos para '.$ApplicationName.'.';
if ($FileCount >= 3) $FCPlural1 = 'Você carregou '.$FileCount.' arquivos válidos para o '.$ApplicationName.'.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Clique, toque ou solte os arquivos aqui para fazer upload.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Conversor, Extrator E Compressor De Arquivos On-Line';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' é baseado no aplicativo da web de código aberto <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> de <a href=\'https://github.com/ zelon88\'>Zelon88</a> que converte arquivos sem rastrear usuários na rede ou infringir sua propriedade intelectual.';
// / 'More Info ...'
$Gui1Text3 = 'Mais Informações ...';
// / 'Less Info ...'
$Gui1Text4 = 'Menos Informações ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Todos os dados fornecidos pelo usuário são apagados automaticamente, então você não precisa se preocupar em perder suas informações pessoais ou propriedades ao usar nossos serviços.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Atualmente '.$ApplicationName.' suporta '.$SupportedFormatCount.' diferentes formatos de arquivo, incluindo documentos, planilhas, imagens, mídia, modelos 3D, desenhos CAD, arquivos vetoriais, arquivos, imagens de disco e muito mais.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Ver Formatos Suportados...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Ocultar Formatos Suportados...';
// / 'Supported Formats'
$Gui1Text9 = 'Formatos Suportados';
// / 'Audio Formats'
$Gui1Text10 = 'Formatos de Áudio';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Suporta taxa de bits específica.';
// / 'Video Formats'
$Gui1Text12 = 'Formatos de Vídeo';
// / 'Stream Formats'
$Gui1Text13 = 'Formatos de Fluxo';
// / 'Document Formats'
$Gui1Text14 = 'Formatos de documentos';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Formatos de Planilha';
// / 'Presentation Formats'
$Gui1Text16 = 'Formatos de Apresentação';
// / 'Archive Formats'
$Gui1Text17 = 'Formatos de Arquivo';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Pode converter entre formatos de arquivo selecionados e formatos de imagem de disco.';
// / 'Image Formats'
$Gui1Text19 = 'Formatos de Imagem';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Pode converter imagens de documentos em formatos de documentos.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Suporta redimensionar e girar.';
// / '3D Model Formats'
$Gui1Text22 = 'Formatos de Modelo 3D';
// / 'Drawing Formats'
$Gui1Text23 = 'Formatos de Desenho';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Pode converter formatos de desenho em formatos de imagem.';
// / 'OCR Support'
$Gui1Text25 = 'Suporte Para Reconhecimento Óptico de Caracteres';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'As operações de Reconhecimento Óptico de Caracteres suportam os seguintes formatos de entrada...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'As operações de Reconhecimento Óptico de Caracteres suportam os seguintes formatos de saída...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Selecione os arquivos clicando, tocando ou soltando-os na caixa abaixo.';
// / 'Continue ...'
$Gui1Text29 = 'Continuar ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Pode converter formatos de stream em formatos de vídeo.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Formatos de Legenda';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Opções de Conversão de Arquivos';
// / 'Bulk File Options'
$Gui2Text2 = 'Opções de Arquivos em Massa';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Verifique Todos os Arquivos em Busca de Vírus';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Compactar e Baixar Todos os Arquivos';
// / 'Download'
$Gui2Text5 = 'Download';
// / 'Share'
$Gui2Text6 = 'Compartilhar';
// / 'Close Share Options'
$Gui2Text7 = 'Fechar Opções de Compartilhamento';
// / 'Virus Scan'
$Gui2Text8 = 'Verificação de Vírus';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Fechar Opções de Verificação de Vírus';
// / 'Archive'
$Gui2Text10 = 'Arquivo';
// / 'Close Archive Options'
$Gui2Text11 = 'Fechar Opções de Arquivo';
// / 'OCR'
$Gui2Text12 = 'Reconhecimento Óptico de Caracteres';
// / 'Close OCR Options'
$Gui2Text13 = 'Fechar opções de Reconhecimento Óptico de Caracteres';
// / 'Convert'
$Gui2Text14 = 'Converter';
// / 'Close Convert Options'
$Gui2Text15 = 'Fechar Opções de Conversão';
// / 'Archive This File'
$Gui2Text16 = 'Arquivar este Arquivo';
// / 'Specify Filename: '
$Gui2Text17 = 'Especifique o Nome do Arquivo: ';
// / 'Format'
$Gui2Text18 = 'Formatar';
// / 'Compress & Download'
$Gui2Text19 = 'Compactar e Baixar';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Digitalize com ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Digitalize com ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Digitalizar Tudo';
// / 'Share This File'
$Gui2Text23 = 'Compartilhe este Arquivo';
// / 'Link Status: '
$Gui2Text24 = 'Status do Link: ';
// / 'Not Generated'
$Gui2Text25 = 'Não Gerado';
// / 'Generated'
$Gui2Text26 = 'Gerado';
// / 'Clipboard Status: '
$Gui2Text27 = 'Status da Área de Transferência: ';
// / 'Copied'
$Gui2Text28 = 'Copiada';
// / 'File Link: '
$Gui2Text29 = 'Link do Arquivo: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = $FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Seus arquivos agora estão prontos para serem convertidos usando as opções abaixo.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Gerar Link e Copiar Para a Área de Transferência';
// / 'Generate Link'
$Gui2Text33 = 'Gerar Link';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Verifique este Arquivo em Busca de Vírus';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Digitalizar Arquivo com ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Digitalizar arquivo com ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Digitalize arquivo com ScanCore e ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Execute o reconhecimento óptico de caracteres neste arquivo';
// / 'Method'
$Gui2Text39 = 'Método';
// / 'Simple'
$Gui2Text40 = 'Simples';
// / 'Advanced'
$Gui2Text41 = 'Avançada';
// / 'Convert This Archive'
$Gui2Text42 = 'Converter Este Arquivo';
// / 'Convert This Document'
$Gui2Text43 = 'Converter Este Documento';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Convert This Planilha';
// / 'Convert This Audio'
$Gui2Text45 = 'Convert This Áudio';
// / 'Convert This Video'
$Gui2Text46 = 'Convert This Vídeo';
// / 'Convert This Stream'
$Gui2Text47 = 'Convert This Fluxo';
// / Convert This 3D Model'
$Gui2Text48 = 'Convert This Modelo 3D';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Convert This Desenho Técnico ou Arquivo Vetorial';
// / 'Convert This Image'
$Gui2Text50 = 'Convert This Imagem';
// / 'Archive File'
$Gui2Text51 = 'Arquivo';
// / 'Convert Into Document'
$Gui2Text52 = 'Converter Em documento';
// / 'Archive Files'
$Gui2Text53 = 'Arquivar Arquivos';
// / 'Convert Document'
$Gui2Text54 = 'Converter Documento';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Convert Spreadsheet';
// / 'Convert Presentation'
$Gui2Text56 = 'Converter Apresentação';
// / 'Convert Audio'
$Gui2Text57 = 'Converter Áudio';
// / 'Convert Video'
$Gui2Text58 = 'Convert Video';
// / 'Convert Stream'
$Gui2Text59 = 'Convert Fluxo';
// / 'Convert Model'
$Gui2Text60 = 'Convert Modelo';
// / 'Convert Drawing'
$Gui2Text61 = 'Convert Desenho';
// / 'Convert Image'
$Gui2Text62 = 'Convert Imagem';
// / 'Width & Height'
$Gui2Text64 = 'Largura Altura: ';
// / 'Rotate: '
$Gui2Text65 = 'Girar: ';
// / 'Bitrate: '
$Gui2Text66 = 'Taxa de Bits: ';
// / 'Delete'
$Gui2Text67 = 'Excluir';
// / 'Close Delete Options'
$Gui2Text68 = 'Fechar Opções de Exclusão';
// / 'Delete This File'
$Gui2Text69 = 'Excluir Este Arquivo';
// / 'Confirm Delete'
$Gui2Text70 = 'Confirmar Exclusão';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Não é possível converter este arquivo! Tente mudar o nome.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Não é possível executar uma verificação de vírus neste arquivo!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Link do arquivo copiado para a área de transferência!';
// / 'Operation Failed!'
$Gui2Text74 = 'Operação falhou!';
// / Convert These Subtitles'
$Gui2Text75 = 'Converta Essas Legendas';
// / Convert Subtitles'
$Gui2Text76 = 'Converter Legendas';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Confira nossos <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Termos de Serviço</a> e <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Política de Privacidade';
// / -----------------------------------------------------------------------------------