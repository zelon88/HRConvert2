[Join the HonestRepair Slack Team!](https://join.slack.com/t/honestrepair/shared_invite/zt-17sa3aydc-Eig9NOOXgjtUjj0l7g87Uw) 
-----------------------------------------------
**[Installation Instructions](https://github.com/zelon88/HRConvert2/blob/master/Documentation/INSTALLATION_INSTRUCTIONS.txt)**
-----------------------------------------------
**[Docker Image by dwaaan](https://github.com/dwaaan/HRConvert2-Docker)**
-----------------------------------------------
# HRConvert2

### A self-hosted drag-and-drop file conversion server & file sharing tool that supports 86 file formats with 4 color schemes & 13 end-user selctable languages. 

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-1.png)

---
### Features
- Converts 86 different file formats.
- Self hosted. Installs on a home server!
- All conversions are performed locally on your server.
- Performs Optical Character Recognition (OCR) on PDFs & images.
- Can scan files for viruses automatically in the background with ClamAV.
- Allows users to scan files for viruses on-demand using ClamAV or [zelon88/scanCore](https://github.com/zelon88/scanCore).
- Allows users to generate temporary links for sharing files.
- Minimalistic drag & drop interface.
- Each user gets their own temporary scratch space!
- End users can switch between 13 languages by appending it to the URL like this: `?language=en`
- Safe enough for public facing environments (when properly implemented).
- No databases. No cookies. No cache files. 
- Installs cleanly alongside other popular software (like WordPress).
- Does not make external connections.
- All JS is locally installed. No bulky frameworks. No analytics. No Google Fonts.
- No tracking capabilities whatsoever.
- Comes with 4 color schemes set in config.php.
- Secure, performant, & compact codebase that's been open-source for years.

---
### Supported Formats
#### Audio Formats
Supports specific bitrate through the API.
- Mp2
- Mp3
- Aac
- Avi
- Flac
- Ogg
- Wav
- Wma
- M4a
- M4p
#### Video Formats
- 3gp
- Mkv
- Avi
- Mp4
- Flv
- Mpeg
- Wmv
- Mov
- M4v
#### Stream Formats
- M3u8
#### Document Formats
- Doc
- Docx
- Txt
- Rtf
- Odt
- Pdf
#### Spreadsheet Formats
- Csv
- Xls
- Xlsx
- Ods
#### Presentation Formats
- Pages
- Pptx
- Ppt
- Xps
- Pot
- Potx
- Potm
- Ppa
- Ppt
- Pptx
- Odp
#### Archive Formats
Can convert between archive formats & disk image formats.
- Zip
- Rar
- Tar
- Bz
- Gz
- Bz2
- 7z
- Iso
- Vhd
- Vdi
- Tar.bz2
- Tar.gz
#### Image Formats
Can convert pictures of documents to document formats.
Supports resize & rotate.
- Jpg
- Jpeg
- Png
- Bmp
- Pdf
- Gif
- Webp
- Cin
- Dds
- Dib
- Flif
- Avif
- Gplt
- Sct
- Xcf
- Heic
- Ico
#### 3D Model Formats
- 3ds
- Obj
- Collada
- Off
- Ply
- Stl
- Gts
- Ptx
- Dxf
- U3d
- X3d
- Vrml
#### Subtitle Formats
- Vtt
- Ssa
- Ass
- Srt
- Dvb
#### Drawing Files
Can convert drawing files to image formats.
- Svg
- Dxf
- Fig
- Vdx
- Dia
- Wpg
#### OCR Support
OCR Operations support the following input formats...
- Jpg
- Jpeg
- Png
- Bmp
- Pdf
- Gif
OCR Operations support the following output formats...
- Doc
- Docx
- Txt
- Rtf
- Odt
- Pdf

---
# Supported Languages
Languages can be forced via policy or dynamically selected by the user by appending `?language=en` to the server URL.
No need install additional language packs to switch languages. Translations are built-in. 
Developers can craft links or redirects to load the correct language for each user or set the language once & forget it.
- English (en)
- French (fr)
- Spanish (es)
- Chinese, Simplified (zh)
- Hindi (hi)
- Arabic (ar)
- Russian (ru)
- Ukranian (uk)
- Bengali (bn)
- German (de)
- Korean (ko)
- Italian (it)
- Portuguese (pt)

---
# Screenshots
![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-10.png)

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-11.png)

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-12.png)

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-13.png)

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-14.png)

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-15.png)

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-16.png)

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-17.png)
