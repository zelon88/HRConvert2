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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Idioma';
// / 'Color'
$GuiSelectorText2 = 'Cor';
// / 'Interface'
$GuiSelectorText3 = 'Interface';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Mostrar opções de idioma, cor e interface';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
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
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Formatos OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Converte o código-fonte do OpenSCAD em formatos de modelos 3D.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'As referências a ficheiros dentro dos ficheiros de origem carregados são removidas, a menos que o servidor permita resolvê-las.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Excluir todos os arquivos enviados e iniciar uma nova sessão?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Começar de Novo';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Atualizar';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Opções de Conversão de Arquivos';
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
// / 'Convert This 3D Model'
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
$Gui2Text55 = 'Converter Planilha';
// / 'Convert Presentation'
$Gui2Text56 = 'Converter Apresentação';
// / 'Convert Audio'
$Gui2Text57 = 'Converter Áudio';
// / 'Convert Video'
$Gui2Text58 = 'Converter Vídeo';
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
// / 'Convert These Subtitles'
$Gui2Text75 = 'Converta Essas Legendas';
// / 'Convert Subtitles'
$Gui2Text76 = 'Converter Legendas';
// / 'Convert This Presentation'
$Gui2Text77 = 'Converter Esta Apresentação';
// / 'Convert This XPS File'
$Gui2Text78 = 'Converter Este Arquivo';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Renderizar Este Modelo OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'Renderizar Modelo';
// / 'Convert This E-Book'
$Gui2Text81 = 'Converter este e-book';
// / 'Convert E-Book'
$Gui2Text82 = 'Converter e-book';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'URL de origem da playlist, necessário apenas se usar caminhos relativos';
// / 'Download A File From A URL'
$Gui2Text84 = 'Baixar um arquivo de uma URL';
// / 'Fetch'
$Gui2Text85 = 'Buscar';
// / 'Identify'
$Gui2Text86 = 'Identificar';
// / 'Close Identify Options'
$Gui2Text87 = 'Fechar Opções de Identificação';
// / 'Identify This File'
$Gui2Text88 = 'Identificar este Arquivo';
// / 'Size: '
$Gui2Text89 = 'Tamanho: ';
// / 'Modified: '
$Gui2Text90 = 'Modificado: ';
// / 'Type: '
$Gui2Text91 = 'Tipo: ';
// / 'MIME Type: '
$Gui2Text92 = 'Tipo MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'Conjunto de Caracteres: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'A Extensão Corresponde ao Conteúdo: ';
// / 'Details: '
$Gui2Text95 = 'Detalhes: ';
// / 'Notes: '
$Gui2Text96 = 'Notas: ';
// / 'Save report as file'
$Gui2Text97 = 'Salvar relatório como arquivo';
// / 'Yes'
$Gui2Text100 = 'Sim';
// / 'No'
$Gui2Text101 = 'Não';
// / 'Unknown'
$Gui2Text102 = 'Desconhecido';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Não é possível identificar este arquivo!';
// / 'Sort By: '
$Gui2Text104 = 'Ordenar Por: ';
// / 'Name'
$Gui2Text105 = 'Nome';
// / 'Size'
$Gui2Text106 = 'Tamanho';
// / 'Date'
$Gui2Text107 = 'Data';
// / 'Type'
$Gui2Text108 = 'Tipo';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Selecionar';
// / 'Select All'
$Gui2Text110 = 'Selecionar Tudo';
// / 'Select None'
$Gui2Text111 = 'Desmarcar Tudo';
// / 'Select By Type'
$Gui2Text112 = 'Selecionar Por Tipo';
// / 'Selected: '
$Gui2Text113 = 'Selecionados: ';
// / 'Documents'
$Gui2Text114 = 'Documentos';
// / 'Spreadsheets'
$Gui2Text115 = 'Planilhas';
// / 'Presentations'
$Gui2Text116 = 'Apresentações';
// / 'Images'
$Gui2Text117 = 'Imagens';
// / 'Audio'
$Gui2Text118 = 'Áudio';
// / 'Video'
$Gui2Text119 = 'Vídeo';
// / 'Archives'
$Gui2Text120 = 'Arquivos Compactados';
// / 'E-Books'
$Gui2Text121 = 'E-books';
// / '3D Models'
$Gui2Text122 = 'Modelos 3D';
// / 'Drawings'
$Gui2Text123 = 'Desenhos';
// / 'Vector Images'
$Gui2Text124 = 'Imagens Vetoriais';
// / 'Subtitles'
$Gui2Text125 = 'Legendas';
// / 'OpenSCAD Files'
$Gui2Text126 = 'Arquivos OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'Fluxos';
// / 'Workflows'
$Gui2Text128 = 'Fluxos de Trabalho';
// / 'Selected Files'
$Gui2Text129 = 'Arquivos Selecionados';
// / 'Select files in the list below.'
$Gui2Text130 = 'Selecione arquivos na lista abaixo.';
// / 'Select By Name: '
$Gui2Text131 = 'Selecionar Por Nome: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Use * e ? como curingas, ou escreva uma expressão regular entre barras.';
// / 'Workflow'
$Gui2Text133 = 'Fluxo de Trabalho';
// / 'Choose a workflow'
$Gui2Text134 = 'Escolha um fluxo de trabalho';
// / 'Run'
$Gui2Text135 = 'Executar';
// / 'Stop'
$Gui2Text136 = 'Parar';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Arquivos selecionados que podem ser usados: {fit} de {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Etapa {step} de {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Arquivo {done} de {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Aguardando o servidor.';
// / 'Starting.'
$Gui2Text141 = 'Iniciando.';
// / 'Finished.'
$Gui2Text142 = 'Concluído.';
// / 'Stopping.'
$Gui2Text143 = 'Parando.';
// / 'Stopped.'
$Gui2Text144 = 'Parado.';
// / 'Files made: '
$Gui2Text145 = 'Arquivos criados: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Os fluxos de trabalho não estão disponíveis neste servidor.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'Nenhum fluxo de trabalho está instalado neste servidor.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Esse fluxo de trabalho não está mais disponível.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Nenhum dos arquivos selecionados pode ser usado por este fluxo de trabalho.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Há arquivos demais selecionados para um fluxo de trabalho.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'O servidor está ocupado. Tente novamente em alguns minutos.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'Já existe um fluxo de trabalho em execução nesta sessão.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'Não foi possível iniciar o fluxo de trabalho.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Uma etapa do fluxo de trabalho falhou.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Um vírus foi encontrado, então o fluxo de trabalho parou.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'O fluxo de trabalho excedeu o tempo.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'O fluxo de trabalho criou arquivos demais.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'O servidor estava ocupado demais para concluir o fluxo de trabalho.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Uma operação de que este fluxo de trabalho precisa não está disponível.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'O fluxo de trabalho parou inesperadamente.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Available Pipelines'
$Gui2Text161 = 'Pipelines Disponíveis';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Selecione um pipeline acima...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Selecionar Pipeline...';
// / 'Select Files...'
$Gui2Text164 = 'Selecionar Arquivos...';
// / 'Add a step'
$Gui2Text165 = 'Adicionar uma etapa';
// / 'Options'
$Gui2Text166 = 'Opções';
// / 'Back'
$Gui2Text167 = 'Voltar';
// / 'Take: '
$Gui2Text168 = 'Usar: ';
// / 'the selected files'
$Gui2Text169 = 'os arquivos selecionados';
// / 'the files step {step} made'
$Gui2Text170 = 'os arquivos criados pela etapa {step}';
// / 'each file on its own'
$Gui2Text171 = 'cada arquivo separadamente';
// / 'all files together'
$Gui2Text172 = 'todos os arquivos juntos';
// / 'Only these kinds: '
$Gui2Text173 = 'Somente estes tipos: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Somente estas extensões: ';
// / 'Smallest size: '
$Gui2Text175 = 'Tamanho mínimo: ';
// / 'Largest size: '
$Gui2Text176 = 'Tamanho máximo: ';
// / 'In the order they arrive'
$Gui2Text177 = 'Na ordem em que chegam';
// / 'all files'
$Gui2Text178 = 'todos os arquivos';
// / 'all {kind}'
$Gui2Text179 = 'todos os arquivos: {kind}';
// / 'Width: '
$Gui2Text180 = 'Largura: ';
// / 'Height: '
$Gui2Text181 = 'Altura: ';
// / 'My workflow'
$Gui2Text182 = 'Meu fluxo de trabalho';
// / 'New'
$Gui2Text183 = 'Novo';
// / 'Import'
$Gui2Text184 = 'Importar';
// / 'Export'
$Gui2Text185 = 'Exportar';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Este servidor não pode executar este fluxo de trabalho.';
// / 'The file could not be read.'
$Gui2Text187 = 'Não foi possível ler o arquivo.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'O editor de fluxos de trabalho não está disponível neste servidor.';
// / 'Status'
$Gui2Text189 = 'Estado';
// / 'A workflow is running.'
$Gui2Text190 = 'Há um fluxo de trabalho em execução.';
// / 'No workflow is running.'
$Gui2Text191 = 'Nenhum fluxo de trabalho em execução.';
// / 'Extract'
$Gui2Text192 = 'Extrair';
// / 'Close Extract Options'
$Gui2Text193 = 'Fechar Opções de Extração';
// / 'Extract This Archive'
$Gui2Text194 = 'Extrair este Arquivo Compactado';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = 'Cada arquivo que ele contém é adicionado aos seus arquivos, com o próprio nome ou com o próximo nome livre.';
// / 'Extract Files'
$Gui2Text196 = 'Extrair Arquivos';
// / 'to {format}'
$Gui2Text197 = 'para {format}';
// / 'This workflow deletes files. A deleted file cannot be brought back.'
$Gui2Text198 = 'Este fluxo de trabalho exclui arquivos. Um arquivo excluído não pode ser recuperado.';
// / 'Delete & Run'
$Gui2Text199 = 'Excluir E Executar';
// / 'Cancel'
$Gui2Text200 = 'Cancelar';
// / 'Rename'
$Gui2Text201 = 'Renomear';
// / 'Close Rename Options'
$Gui2Text202 = 'Fechar Opções De Renomear';
// / 'Rename This File'
$Gui2Text203 = 'Renomear Este Arquivo';
// / 'New Name'
$Gui2Text204 = 'Novo Nome';
// / 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.'
$Gui2Text205 = 'Use {name} para o nome que ele tem, {n} para a posição dele entre os arquivos selecionados e {date} para a data de hoje. A extensão é mantida.';
// / 'Strip Metadata'
$Gui2Text206 = 'Remover Metadados';
// / 'Close Strip Metadata Options'
$Gui2Text207 = 'Fechar Opções De Metadados';
// / 'Strip The Metadata From This File'
$Gui2Text208 = 'Remover Os Metadados Deste Arquivo';
// / 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.'
$Gui2Text209 = 'Uma cópia é criada ao lado dele sem autores, locais, datas e o programa que o criou. O que ele mostra continua como estava.';
// / 'Merge PDFs'
$Gui2Text210 = 'Mesclar PDFs';
// / 'Split PDF'
$Gui2Text211 = 'Dividir PDF';
// / 'Merge Or Split'
$Gui2Text212 = 'Mesclar Ou Dividir';
// / 'Close Merge & Split Options'
$Gui2Text213 = 'Fechar Opções De Mesclar E Dividir';
// / 'Merge The Selected PDFs Into One'
$Gui2Text214 = 'Mesclar Os PDFs Selecionados Em Um Só';
// / 'Their pages follow one another in the order the PDFs were selected.'
$Gui2Text215 = 'As páginas seguem umas às outras na ordem em que os PDFs foram selecionados.';
// / 'Select two or more PDFs to merge them.'
$Gui2Text216 = 'Selecione dois ou mais PDFs para mesclá-los.';
// / 'Make A PDF Of Each Page'
$Gui2Text217 = 'Criar Um PDF De Cada Página';
// / 'Every selected file is deleted with it: {count} files.'
$Gui2Text218 = 'Cada arquivo selecionado é excluído com ele: {count} arquivos.';
// / 'Every selected file is renamed with it: {count} files.'
$Gui2Text219 = 'Cada arquivo selecionado é renomeado com ele: {count} arquivos.';
// / 'Library'
$Gui2Text220 = 'Biblioteca';
// / 'from Selected Files'
$Gui2Text221 = 'de Arquivos Selecionados';
// / 'from Step {step}'
$Gui2Text222 = 'da Etapa {step}';
// / 'Preview'
$Gui2Text223 = 'Prévia';
// / 'If the workflow ran now, each step would take:'
$Gui2Text224 = 'Se o fluxo de trabalho fosse executado agora, cada etapa usaria:';
// / 'Left out: '
$Gui2Text225 = 'Deixados de fora: ';
// / 'No files.'
$Gui2Text226 = 'Nenhum arquivo.';
// / 'every file in the list when it runs'
$Gui2Text227 = 'todos os arquivos da lista quando a etapa for executada';
// / 'This workflow deletes these files. A deleted file cannot be brought back.'
$Gui2Text228 = 'Este fluxo de trabalho exclui estes arquivos. Um arquivo excluído não pode ser recuperado.';
// / 'A step had errors, so the workflow stopped before the step that deletes files.'
$Gui2Text229 = 'Uma etapa teve erros, então o fluxo de trabalho parou antes da etapa que exclui arquivos.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'O seu navegador não suporta a cópia para a área de transferência.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Confira nossos <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Termos de Serviço</a> e <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Política de Privacidade';
// / -----------------------------------------------------------------------------------