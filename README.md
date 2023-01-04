[Join the HonestRepair Slack Team!](https://join.slack.com/t/honestrepair/shared_invite/zt-17sa3aydc-Eig9NOOXgjtUjj0l7g87Uw) 
-----------------------------------------------
**[Installation Instructions](https://github.com/zelon88/HRConvert2/blob/master/Documentation/INSTALLATION_INSTRUCTIONS.txt)**
-----------------------------------------------
**[Docker Image by dwaaan](https://github.com/dwaaan/HRConvert2-Docker)**
-----------------------------------------------
# HRConvert2

### A self-hosted drag-and-drop file conversion server & file sharing tool that supports 77 file formats with 4 color schemes & 13 end-user selctable languages. 

![HRConvert2](https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/HRConvert2-1.png)

---
### Features
- Converts 76 different file formats.
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
- Avi
- Flac
- Ogg
- Wav
- Wma
#### Video Formats
- 3gp
- Mkv
- Avi
- Mp4
- Flv
- Mpeg
- Wmv
- Mov
#### Stream Formats
- m3u8
#### Document Formats
- Doc
- Docx
- Txt
- Rtf
- Odt
- Pdf
#### Spreadsheet Formats
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
- Ppa
- Ppt
- Pptx
- Odp
#### Archive Formats
Can create, convert, and dearchive any of the following...
- Zip
- Rar
- Tar
- 7z
#### Disk Image Formats
Can extract any of the following or convert to supported archive formats...
- Iso
- Vhd
- Vdi
#### Image Formats
Supports resize & rotate through the GUI and API.
Supports disable maintain aspect ratio through API.
- Jpg
- Jpeg
- Png
- Bmp
- Pdf
- Gif
- Crw
- Cin
- Dcr
- Dds
- Dib
- Flif
- Gplt
- Nef
- Orf
- Ora
- Sct
- Sfw
- Xcf
- Xwd
- Avif
- Ico
#### 3D Model Formats
- 3ds
- Obj
- Collada
- Off
- Ply
- Stl
- Ptx
- Dxf
- U3d
- Vrml
#### Drawing Files
Can output drawing files to image formats.
Can convert between any of the following...
- Svg
- Dxf
- Fig
- Vdx
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
